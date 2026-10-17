<?php

namespace WHMCS\Module\Registrar\dynadot\utils;

use Exception;
use Throwable;
use WHMCS\Database\Capsule;
use WHMCS\Config\Setting;

class ModuleUtil
{
    public static function dynadotModuleLog(string $action, string $request, string $response): void
    {
        logModuleCall("dynadot", $action, $request, $response, '');
    }

    public static function isDynadotActivated(): bool
    {
        try {
            return Capsule::table('tblregistrars')
                ->where('registrar', 'dynadot')
                ->exists();
        } catch (Exception $e) {
            self::dynadotModuleLog('tool_exception', 'isDynadotActivated', $e->getMessage());
            return false;
        }
    }

    public static function extractDomainName($params): string
    {
        $isIdn = $params['is_idn']; // idn domain
        if ($isIdn) {
            return $params['domain_punycode'];
        }

        $keyword = $params['sld'];
        $tld = $params['tld'];
        return $keyword . '.' . $tld;
    }

    public static function extractPhoneInfoByFullPhonenum($fullPhone): array
    {
        $fullPhone = trim($fullPhone);

        if (strpos($fullPhone, '.') !== false) {
            if (preg_match('/^\+?\s*(\d+)\s*\.\s*([\d\s\-]+)\s*$/', $fullPhone, $matches)) {
                return [
                    'phonecc'  => $matches[1],
                    'phonenum' => str_replace([' ', '-'], '', $matches[2])
                ];
            }

            self::dynadotModuleLog("tool_exception", "extractPhoneInfoByFullPhonenum", "Invalid phone number format: " . $fullPhone);
            return [
                'phonecc'  => '',
                'phonenum' => $fullPhone
            ];
        }

        return [
            'phonecc'  => '',
            'phonenum' => $fullPhone
        ];
    }

    public static function extractPhoneccByCountryCode($countryCode)
    {
        $countryCode = strtoupper($countryCode);

        try {
            $baseDir = dirname(__DIR__, 5);
            $baseDir = rtrim($baseDir, '/\\');
            $countriesFile = $baseDir . '/resources/country/dist.countries.json';
            if (file_exists($countriesFile)) {
                $json = json_decode(file_get_contents($countriesFile), true, 512, JSON_THROW_ON_ERROR);
                return $json[$countryCode]['callingCode'] ?? '';
            }
        } catch (Exception $e) {
            return '';
        }

        return '';
    }

    public static function extractNameservers(array $params): array
    {
        if (!empty($params['nameservers'])) {
            $nsList = explode(',', $params['nameservers']);
            return array_values(array_filter(array_map('trim', $nsList)));
        }

        $nsFields = [
            $params['ns1'] ?? null,
            $params['ns2'] ?? null,
            $params['ns3'] ?? null,
            $params['ns4'] ?? null,
            $params['ns5'] ?? null,
        ];

        $nameservers = array_values(array_filter(array_map('trim', $nsFields)));

        return $nameservers ?: ModuleUtil::fetchDefaultNs();
    }

    public static function generateContactExtensionFromHookVars(string $domainName, array $hookVars): ?array
    {
        $domainFields = $hookVars['domainfield'];

        if (DomainUtil::isAeroDomain($domainName)) {
            return [
                'tld' => 'aero',
                'membership_id' => $domainFields[0] ?? '',
                'membership_auth' => $domainFields[1] ?? '',
            ];
        }

        if (DomainUtil::isAppDomain($domainName)) {
            return [
                'tld' => 'app',
                'app_agreement_accepted' => ($domainFields[0] ?? '') === "on"
            ];
        }

        if (DomainUtil::isEuDomain($domainName)) {
            return [
                'tld' => 'eu',
                'entity_type' => $domainFields[0] ?? '',
                'country_of_citizenship' => $domainFields[1] ?? '',
            ];
        }

        if (DomainUtil::isTelDomain($domainName)) {
            return [
                'tld' => 'tel',
                'whois_type' => ContactUtil::formatTelWhoisTypeFromWhmcsToApi($domainFields[0], $domainFields[1]),
            ];
        }

        if (DomainUtil::isUsDomain($domainName)) {
            return [
                'tld'             => 'us',
                'intended_usage'  => ContactUtil::$IntendedUsageMapFromWhmcsToApi[$domainFields[2] ?? ''] ?? '',
                'registrant_type' => ContactUtil::$RegistrantTypeMapFromWhmcsToApi[$domainFields[0] ?? ''] ?? '',
            ];
        }

        return [];
    }

    public static function fetchWhmcsVersion(): string
    {
        try {
            $version = Capsule::table('tblconfiguration')
                ->where('setting', 'Version')
                ->value('value');

            if ($version) {
                return (string) $version;
            }
        } catch (Exception $e) {
            return 'unknown';
        }

        global $CONFIG;
        if (!empty($CONFIG['Version'])) {
            return (string) $CONFIG['Version'];
        }

        return 'unknown';
    }

    public static function fetchApiKey(): string
    {
        try {
            $encryptedKey = Capsule::table('tblregistrars')
                ->where('registrar', 'dynadot')
                ->where('setting', 'ApiKey')
                ->value('value');

            if (empty($encryptedKey)) {
                self::dynadotModuleLog("tool_exception", "fetchApiKey", "Dynadot Registrar API key not found in database.");
                return '';
            }

            return decrypt($encryptedKey);
        } catch (Exception $e) {
            self::dynadotModuleLog("tool_exception", "fetchApiKey", $e->getMessage());
            return '';
        }
    }

    public static function fetchApiSecretKey(): string
    {
        try {
            $encryptedApiSecretKey = Capsule::table('tblregistrars')
                ->where('registrar', 'dynadot')
                ->where('setting', 'ApiSecretKey')
                ->value('value');

            if (empty($encryptedApiSecretKey)) {
                self::dynadotModuleLog("tool_exception", "fetchApiSecretKey", "Dynadot Registrar API Secret key not found.");
                return '';
            }

            return decrypt($encryptedApiSecretKey);
        } catch (Exception $e) {
            self::dynadotModuleLog("tool_exception", "fetchApiSecretKey", $e->getMessage());
            return '';
        }
    }

    public static function fetchDefaultNs(): array
    {
        $systemDefaultNs = [];
        for ($i = 1; $i <= 5; $i++) {
            $ns = Setting::getValue("DefaultNameserver$i");
            $ns = trim($ns);

            if ($ns) {
                $systemDefaultNs[] = $ns;
            }
        }
        return $systemDefaultNs;
    }
    public static function fetchWhmcsDomain(int $domainId): ?object
    {
        try {
            return Capsule::table('tbldomains')
                ->where('id', $domainId)
                ->first();
        } catch (Throwable $e) {
            self::dynadotModuleLog('db_exception', 'fetchWhmcsDomain', $e->getMessage());
            return null;
        }
    }

    public static function fetchDomainWhmcsOrderId(array $params, string $action): int
    {
        $domainId = $params['domainid'] ?? null;
        if (!$domainId) {
            return -1;
        }

        try {
            if (in_array($action, ['register', 'transfer'], true)) {
                $orderId = Capsule::table('tbldomains')
                    ->where('id', $domainId)
                    ->value('orderid');

                return $orderId ? (int) $orderId : -1;
            }

            if ($action === 'renew') {
                $latestRenewOrder = Capsule::table('tblinvoiceitems')
                    ->join('tblinvoices', 'tblinvoiceitems.invoiceid', '=', 'tblinvoices.id')
                    ->join('tblorders', 'tblorders.invoiceid', '=', 'tblinvoices.id')
                    ->where('tblinvoiceitems.type', 'DomainRenew')
                    ->where('tblinvoiceitems.relid', $domainId)
                    ->orderBy('tblorders.id', 'desc')
                    ->value('tblorders.id');

                if ($latestRenewOrder) {
                    return (int) $latestRenewOrder;
                }

                $fallbackOrderId = Capsule::table('tbldomains')
                    ->where('id', $domainId)
                    ->value('orderid');

                return $fallbackOrderId ? (int) $fallbackOrderId : -1;
            }
        } catch (Throwable $e) {
            self::dynadotModuleLog('tool_exception', 'fetchDomainWhmcsOrderId', $e->getMessage());
            return -1;
        }

        return -1;
    }

    public static function syncUsAdditionalFields(int $domainId, object $usContactExtension): void
    {
        $intendedUsageRaw = ContactUtil::$IntendedUsageMapFromApiToWhmcs[strtoupper($usContactExtension->intended_usage)] ?? '';
        $registrantTypeRaw = ContactUtil::$RegistrantTypeMapFromApiToWhmcs[strtoupper($usContactExtension->registrant_type)] ?? '';

        $fieldsToUpdate = [
            'Application Purpose' => $intendedUsageRaw,
            'Nexus Category' => $registrantTypeRaw
        ];

        foreach ($fieldsToUpdate as $fieldName => $fieldValue) {
            Capsule::table('tbldomainsadditionalfields')->updateOrInsert(
                ['domainid' => $domainId, 'name' => $fieldName],
                ['value' => $fieldValue]
            );
        }
    }

    public static function setWhmcsDomainStatus(int $domainId, string $status): void
    {
        try {
            Capsule::table('tbldomains')
                ->where('id', $domainId)
                ->update(['status' => $status]);
        } catch (Throwable $e) {
            self::dynadotModuleLog('db_exception', 'setWhmcsDomainStatus', $e->getMessage());
        }
    }
}