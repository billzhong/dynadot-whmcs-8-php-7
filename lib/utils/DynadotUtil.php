<?php

namespace WHMCS\Module\Registrar\dynadot\utils;

use JsonException;
use Exception;
use WHMCS\Module\Registrar\dynadot\RestfulApi;

class DynadotUtil
{
    public const DYNADOT_MODULE_DOWNLOAD_LINK = "https://marketplace.whmcs.com/product/6353-dynadot-domain-registrar-whmcs";

    public const UNEXPECTED_ERROR = "Unexpected error, please contact our support.";

    public static function getDomainExtensionData(string $domainName, array $params): array
    {
        $additionalFields = $params['additionalfields'] ?? [];

        // --- Secure Protocol Domains ---
        if (DomainUtil::isAppDomain($domainName)) {
            return ['tld' => 'app', 'app_agreement_accepted' => ($additionalFields['.APP SSL Agreement'] ?? '') === 'on'];
        }
        //        if (DomainUtil::isDevDomain($domainName)) {
        //            return ['tld' => 'dev', 'dev_agreement_accepted' => ($additionalFields['.DEV SSL Agreement'] ?? '') === 'on'];
        //        }
        //        if (DomainUtil::isBioDomain($domainName)) {
        //            return ['tld' => 'bio', 'bio_agreement_accepted' => ($additionalFields['.BIO SSL Agreement'] ?? '') === 'on'];
        //        }

        // --- North America ---
        if (DomainUtil::isUsDomain($domainName)) {
            $applicationPurpose = $params['additionalfields']['Application Purpose'] ?? '';
            $nexusCategory = $params['additionalfields']['Nexus Category'] ?? '';
            $nexusCountry = $params['additionalfields']['Nexus Country'] ?? '';

            return [
                'tld' => 'us',
                'intended_usage' => ContactUtil::$IntendedUsageMapFromWhmcsToApi[$applicationPurpose] ?? '',
                'nexus_country' => $nexusCountry ?? '',
                'registrant_type' => ContactUtil::$RegistrantTypeMapFromWhmcsToApi[$nexusCategory] ?? '',
            ];
        }
//        if (DomainUtil::isCaDomain($domainName)) {
//            return [
//                'tld' => 'ca',
//                'legal_type' => $additionalFields['Legal Type'] ?? '',
//                'cira_agreement' => ($additionalFields['CIRA Agreement'] ?? '') === 'on',
//                'whois_optout' => ($additionalFields['WHOIS Opt-out'] ?? '') === 'on',
//            ];
//        }
//        if (DomainUtil::isNycDomain($domainName)) {
//            return ['tld' => 'nyc', 'nexus_category' => $additionalFields['nexusCategory'] ?? ''];
//        }
//
//        --- Europe-- -
//        if (DomainUtil::isUkDomain($domainName)){
//            return [
//                'tld' => 'uk',
//                'legal_type' => $additionalFields['Legal Type'] ?? '',
//                'company_id_number' => $additionalFields['Company ID Number'] ?? '',
//                'registrant_name' => $additionalFields['Registrant Name'] ?? '',
//                'whois_optout' => ($additionalFields['WHOIS Opt-out'] ?? '') === 'on',
//            ];
//        }
//        if (DomainUtil::isDeDomain($domainName)) {
//            return [
//                'tld' => 'de',
//                'tax_id' => $additionalFields['Tax ID'] ?? '',
//                'address_confirmed' => ($additionalFields['Address Confirmation'] ?? '') === 'on',
//                'terms_agreed' => ($additionalFields['Agree to DE Terms'] ?? '') === 'on',
//            ];
//        }
        if (DomainUtil::isEuDomain($domainName)) {
            return [
                'tld' => 'eu',
                'entity_type' => $additionalFields['Entity Type'] ?? '',
                'country_of_citizenship' => $additionalFields['EU Country of Citizenship'] ?? '',
            ];
        }
//                if (DomainUtil::isFrDomain($domainName)) {
//                    return [
//                        'tld' => 'fr',
//                        'legal_type' => $additionalFields['Legal Type'] ?? '',
//                        'legal_id' => $additionalFields['Legal ID'] ?? '',
//                        'siret_number' => $additionalFields['SIRET Number'] ?? '',
//                        'vat_number' => $additionalFields['VAT Number'] ?? '',
//                        'trademark_num' => $additionalFields['Trademark Number'] ?? '',
//                    ];
//                }
//                if (DomainUtil::isItDomain($domainName)) {
//                    return [
//                        'tld' => 'it',
//                        'legal_type' => $additionalFields['Legal Type'] ?? '',
//                        'tax_id' => $additionalFields['Tax ID'] ?? '',
//                        'publish_data' => ($additionalFields['Publish Personal Data'] ?? '') === 'on',
//                        'sec3_agreed' => ($additionalFields['Accept Section 3 of .IT registrar contract'] ?? '') === 'on',
//                        'sec5_agreed' => ($additionalFields['Accept Section 5 of .IT registrar contract'] ?? '') === 'on',
//                        'sec6_agreed' => ($additionalFields['Accept Section 6 of .IT registrar contract'] ?? '') === 'on',
//                        'sec7_agreed' => ($additionalFields['Accept Section 7 of .IT registrar contract'] ?? '') === 'on',
//                    ];
//                }
//                if (DomainUtil::isEsDomain($domainName)) {
//                    return [
//                        'tld' => 'es',
//                        'id_form_type' => $additionalFields['ID Form Type'] ?? '',
//                        'id_form_number' => $additionalFields['ID Form Number'] ?? '',
//                        'legal_form' => $additionalFields['Legal Form'] ?? '',
//                    ];
//                }
//        if (DomainUtil::isSeDomain($domainName)) {
//            return [
//                'tld' => 'se',
//                'id_number' => $additionalFields['Identification Number'] ?? '',
//                'vat_number' => $additionalFields['VAT'] ?? '',
//            ];
//        }
//        if (DomainUtil::isNuDomain($domainName)) {
//            return [
//                'tld' => 'nu',
//                'id_number' => $additionalFields['Identification Number'] ?? '',
//                'vat_number' => $additionalFields['VAT Number'] ?? '',
//            ];
//        }
//        if (DomainUtil::isPtDomain($domainName)) {
//            return [
//                'tld' => 'pt',
//                'owner_id' => $additionalFields['Owner Identification'] ?? '',
//                'tech_id' => $additionalFields['Tech Identification'] ?? '',
//            ];
//        }
//                if (DomainUtil::isHuDomain($domainName)) {
//                    return [
//                        'tld' => 'hu',
//                        'trustee_accepted' => ($additionalFields['Accept Trustee Service'] ?? '') === 'on',
//                        'id_number' => $additionalFields['ID Card or Passport Number'] ?? '',
//                        'vat_number' => $additionalFields['VAT Number'] ?? '',
//                    ];
//                }
//        if (DomainUtil::isSwissDomain($domainName)) {
//            return [
//                'tld' => 'swiss',
//                'intended_use' => $additionalFields['Core Intended Use'] ?? '',
//                'enterprise_id' => $additionalFields['Registrant Enterprise ID'] ?? '',
//            ];
//        }
//
//         --- Asia-- -
//        if (DomainUtil::isCnDomain($domainName)){
//            return [
//                'tld' => 'cn',
//                'owner_type' => $additionalFields['Owner Type'] ?? '',
//                'id_type' => $additionalFields['ID Type'] ?? '',
//                'id_number' => $additionalFields['ID Number'] ?? '',
//                'hosting_in_china' => ($additionalFields['cnhosting'] ?? '') === 'on',
//                'agreement_accepted' => ($additionalFields['cnhregisterclause'] ?? '') === 'on',
//            ];
//        }
//        if (DomainUtil::isHkDomain($domainName)) {
//            return [
//                'tld' => 'hk',
//                'registrant_type' => $additionalFields['Registrant Type'] ?? '',
//                'org_name_cn' => $additionalFields['Organizations Name in Chinese'] ?? '',
//                'org_doc_type' => $additionalFields['Organizations Supporting Documentation'] ?? '',
//                'org_doc_number' => $additionalFields['Organizations Document Number'] ?? '',
//                'org_industry_type' => $additionalFields['Organizations Industry Type'] ?? '',
//                'ind_doc_type' => $additionalFields['Individuals Supporting Documentation'] ?? '',
//                'ind_doc_number' => $additionalFields['Individuals Document Number'] ?? '',
//                'ind_under_18' => $additionalFields['Individuals Under 18'] ?? 'No',
//            ];
//        }
//                if (DomainUtil::isSgDomain($domainName)) {
//                    return [
//                        'tld' => 'sg',
//                        'rcb_id' => $additionalFields['RCB Singapore ID'] ?? '',
//                        'registrant_type' => $additionalFields['Registrant Type'] ?? '',
//                        'admin_personal_id' => $additionalFields['Admin Personal ID'] ?? '',
//                    ];
//                }
//        if (DomainUtil::isAsiaDomain($domainName)) {
//            return [
//                'tld' => 'asia',
//                'legal_type' => $additionalFields['Legal Type'] ?? '',
//                'identity_form' => $additionalFields['Identity Form'] ?? '',
//                'identity_number' => $additionalFields['Identity Number'] ?? '',
//            ];
//        }
//
//         --- Oceania-- -
//        if (DomainUtil::isAuDomain($domainName)){
//            return [
//                'tld' => 'au',
//                'registrant_name' => $additionalFields['Registrant Name'] ?? '',
//                'registrant_id' => $additionalFields['Registrant ID'] ?? '',
//                'registrant_id_type' => $additionalFields['Registrant ID Type'] ?? '',
//                'eligibility_type' => $additionalFields['Eligibility Type'] ?? '',
//                'eligibility_reason' => $additionalFields['Eligibility Reason'] ?? '',
//            ];
//        }
//
//         --- South America-- -
//        if (DomainUtil::isBrDomain($domainName)){
//            return [
//                'tld' => 'br',
//                'register_number' => $additionalFields['Register Number'] ?? '',
//            ];
//        }
//
//         --- Russia and Eastern Europe-- -
//        if (DomainUtil::isRuDomain($domainName)){
//            return [
//                'tld' => 'ru',
//                'registrant_type' => $additionalFields['Registrant Type'] ?? '',
//                'ind_birthday' => $additionalFields['Individuals Birthday'] ?? '',
//                'ind_passport_num' => $additionalFields['Individuals Passport Number'] ?? '',
//                'ind_passport_issuer' => $additionalFields['Individuals Passport Issuer'] ?? '',
//                'ind_passport_date' => $additionalFields['Individuals Passport Issue Date'] ?? '',
//                'ind_whois_privacy' => $additionalFields['Individuals: Whois Privacy'] ?? 'No',
//                'org_tax_number_1' => $additionalFields['Russian Organizations Taxpayer Number 1'] ?? '',
//                'org_tax_number_2' => $additionalFields['Russian Organizations Territory-Linked Taxpayer Number 2'] ?? '',
//            ];
//        }
//        if (DomainUtil::isRoDomain($domainName)) {
//            return [
//                'tld' => 'ro',
//                'cnp_fiscal_code' => $additionalFields['CNPFiscalCode'] ?? '',
//                'reg_number' => $additionalFields['Registration Number'] ?? '',
//                'registrant_type' => $additionalFields['Registrant Type'] ?? '',
//            ];
//        }
//
//         --- Special Industry / Restricted Domains-- -
//        if (DomainUtil::isDentistDomain($domainName)){
//            return ['tld' => 'dentist', 'regulatory_data' => $additionalFields['Regulatory Data'] ?? ''];
//        }
//        if (DomainUtil::isProDomain($domainName)) {
//            return [
//                'tld' => 'pro',
//                'profession' => $additionalFields['Profession'] ?? '',
//                'license_number' => $additionalFields['License Number'] ?? '',
//                'authority' => $additionalFields['Authority'] ?? '',
//                'authority_website' => $additionalFields['Authority Website'] ?? '',
//            ];
//        }
        if (DomainUtil::isAeroDomain($domainName)) {
            return [
                'tld' => 'aero',
                'membership_id' => $additionalFields['.AERO ID'] ?? '',
                'membership_auth' => $additionalFields['.AERO Key'] ?? '',
            ];
        }
//                if (DomainUtil::isTravelDomain($domainName)) {
//                    return [
//                        'tld' => 'travel',
//                        'uin_code' => $additionalFields['.TRAVEL UIN Code'] ?? '',
//                        'usage_agreement' => ($additionalFields['.TRAVEL Usage Agreement'] ?? '') === 'on',
//                    ];
//                }
//        if (DomainUtil::isCoopDomain($domainName)) {
//            return [
//                'tld' => 'coop',
//                'contact_name' => $additionalFields['Contact Name'] ?? '',
//                'contact_company' => $additionalFields['Contact Company'] ?? '',
//                'contact_email' => $additionalFields['Contact Email'] ?? '',
//                'address1' => $additionalFields['Address 1'] ?? '',
//                'address2' => $additionalFields['Address 2'] ?? '',
//                'city' => $additionalFields['City'] ?? '',
//                'state' => $additionalFields['State'] ?? '',
//                'zip' => $additionalFields['ZIP Code'] ?? '',
//                'country' => $additionalFields['Country'] ?? '',
//                'phone_cc' => $additionalFields['Phone CC'] ?? '',
//                'phone' => $additionalFields['Phone'] ?? '',
//            ];
//        }
//
//         --- Other Regional Domains-- -
//        if (DomainUtil::isDkDomain($domainName)){
//            return [
//                'tld' => 'dk',
//                'org_type' => $additionalFields['Organization Type'] ?? '',
//                'org_id' => $additionalFields['Organization ID Number'] ?? '',
//                'terms_agreed' => ($additionalFields['.DK Domain Agreement'] ?? '') === 'on',
//            ];
//        }
//        if (DomainUtil::isPlDomain($domainName)) {
//            return ['tld' => 'pl', 'whois_publish' => $additionalFields['Publish Contact in .PL WHOIS'] ?? 'yes'];
//        }
        if (DomainUtil::isTelDomain($domainName)) {
            return [
                'tld' => 'tel',
                'whois_type' => ContactUtil::formatTelWhoisTypeFromWhmcsToApi($additionalFields['Legal Type'], $additionalFields['WHOIS Opt-out']),
            ];
        }
//        if (DomainUtil::isQuebecDomain($domainName)) {
//            return ['tld' => 'quebec', 'intended_use' => $additionalFields['Intended Use'] ?? ''];
//        }
//        if (DomainUtil::isScotDomain($domainName)) {
//            return [
//                'tld' => 'scot',
//                'intended_use' => $additionalFields['Intended Use'] ?? '',
//                'terms_agreed' => ($additionalFields['Terms & Conditions'] ?? '') === 'on',
//            ];
//        }
//        if (DomainUtil::isJobsDomain($domainName)) {
//            return ['tld' => 'jobs', 'website' => $additionalFields['Website'] ?? ''];
//        }
//        if (DomainUtil::isVoteDomain($domainName)) {
//            return ['tld' => 'vote', 'agreement' => ($additionalFields['Agreement'] ?? '') === 'on'];
//        }

        return [];
    }

    public static function formatToWhmcsNameservers($apiData): array
    {
        $whmcsNameservers = array();

        if (empty($apiData)) {
            return $whmcsNameservers;
        }

        $nameserverList = null;
        if (is_object($apiData)) {
            $nameserverList = $apiData->nameserver_list ?? $apiData->name_servers ?? [];
        } elseif (is_array($apiData)) {
            $nameserverList = $apiData['nameserver_list'] ?? $apiData['name_servers'] ?? [];
        }

        if (is_array($nameserverList)) {
            foreach ($nameserverList as $index => $nsItem) {
                $serverName = is_object($nsItem) ? ($nsItem->server_name ?? '') : ($nsItem['server_name'] ?? '');

                $serverName = trim($serverName);
                if (!empty($serverName)) {
                    $whmcsNameservers['ns' . ($index + 1)] = $serverName;
                }
            }
        }

        return $whmcsNameservers;
    }

    public static function retrieveCurrentModuleVersion(): ?string
    {
        $fileName = dirname(__DIR__, 2) . "/version.json";
        $defaultVersion = "1.0.0";

        if (!file_exists($fileName) || !is_readable($fileName)) {
            return $defaultVersion;
        }

        try {
            $content = file_get_contents($fileName);
            $data = json_decode($content, false, 512, JSON_THROW_ON_ERROR);

            return $data->version ?? $defaultVersion;
        } catch (JsonException|Exception $e) {
            ModuleUtil::dynadotModuleLog("tool_exception", $fileName, $e->getMessage());
            return $defaultVersion;
        }
    }

    public static function retrieveLatestModuleVersion(): string
    {
        $currentVersion = self::retrieveCurrentModuleVersion();

        try {
            $apiKey = ModuleUtil::fetchApiKey();
            $endpoint = "/whmcs_module/" . urlencode($currentVersion) . "/update_check";

            $response = RestfulApi::sendRequest($endpoint, 'GET', [], $apiKey);
            if (isset($response->code) && $response->code >= 200 && $response->code < 300) {
                $data = $response->data;
                $hasNew = ($data->has_new_version === "Yes");
                $latest = $data->latest_version ?? null;

                if ($hasNew && $latest) {
                    $downloadLink = self::DYNADOT_MODULE_DOWNLOAD_LINK;
                    $latestVersion = $latest;

                    return <<<EOF
<div class="alert alert-info alert-dismissible" style="padding: 20px 20px 20px 20px; border-color: #f0ad4e; background-color: #fff; position: relative; border-radius: 4px; border-left: 5px solid #f0ad4e; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px;">
    <button type="button" class="close" data-dismiss="alert" style="top: -10px; right: 0;">×</button>
    
    <div style="display: flex; align-items: flex-start;">
        <div style="min-width: 45px; margin-right: 15px;">
            <i class="fas fa-sync-alt fa-3x" style="color: #f0ad4e;"></i>
        </div>
        
        <div style="flex-grow: 1;">
            <strong style="font-size: 1.15em; display: block; margin-bottom: 5px; color: #222;">
                Dynadot Registrar Module Update Available!
            </strong>
            <div style="font-size: 0.95em; line-height: 1.5; color: #666;">
                A new version <span class="label label-warning" style="padding: 2px 6px;">v$latestVersion</span> is ready for download. 
                Keep your module secure and efficient. 
                <span style="display: block; margin-top: 4px; font-size: 0.85em; color: #aaa;">Currently running: v$currentVersion</span>
            </div>
        </div>
    </div>

    <div style="position: absolute; bottom: 20px; right: 20px;">
        <a href="$downloadLink" target="_blank" class="btn btn-info" 
           style="background-color: #5bc0de; border-color: #46b8da; padding: 7px 18px; font-weight: bold; border-radius: 4px; text-decoration: none; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
           <i class="fas fa-external-link-alt" style="font-size: 0.9em; margin-right: 5px;"></i>
            View on Marketplace
        </a>
    </div>
</div>
EOF;
                }
            }
        } catch (Exception $e) {
            ModuleUtil::dynadotModuleLog("tool_exception retrieveLatestModuleVersion", "Version: " . $currentVersion, $e->getMessage());
        }

        return "";
    }

    public static function renderDynadotErrorBox($title, $message): string
    {
        return <<<HTML
<div class="errorbox">
    <strong><span class="title">{$title}</span></strong>
    <br>
    {$message}
</div>
HTML;
    }

    public static function renderApiSecretKeyWarning(): string
    {
        if (!ModuleUtil::isDynadotActivated()) {
            return '';
        }

        if (ModuleUtil::fetchApiSecretKey() !== "" && ModuleUtil::fetchApiKey() !== "") {
            return '';
        }

        $settingsUrl = 'configregistrars.php';

        return <<<EOF
<div class="alert alert-danger alert-dismissible" style="padding: 20px 20px 20px 20px; border-color: #ebccd1; background-color: #f2dede; color: #a94442; position: relative; border-radius: 4px; border-left: 5px solid #a94442; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px;">
    <button type="button" class="close" data-dismiss="alert" style="top:-10px; right: 0;">×</button>
    
    <div style="display: flex; align-items: flex-start;">
        <div style="min-width: 45px; margin-right: 15px;">
            <i class="fas fa-exclamation-triangle fa-3x" style="color: #a94442;"></i>
        </div>
        
        <div style="flex-grow: 1;">
            <strong style="font-size: 1.15em; display: block; margin-bottom: 5px;">
                Action Required: The Dynadot API requires activation for API Production Secret Key.
            </strong>
            <div style="font-size: 0.95em; line-height: 1.5;">
                The Dynadot domain registrar module is activated, but your API Production Secret Key has not yet been successfully activated.
                <br/>
                Dynadot API will fail until this is resolved. Please complete your setup.
            </div>
        </div>
    </div>

    <div style="position: absolute; bottom: 20px; right: 20px;">
        <a href="$settingsUrl" class="btn btn-danger" 
           style="background-color: #d9534f; border-color: #d43f3a; padding: 7px 18px; font-weight: bold; border-radius: 4px; text-decoration: none; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
           <i class="fas fa-cog" style="font-size: 0.9em; margin-right: 5px;"></i>
            Configure Now
        </a>
    </div>
</div>
EOF;
    }
}