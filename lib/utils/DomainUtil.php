<?php

namespace WHMCS\Module\Registrar\dynadot\utils;

use WHMCS\Domain\Registrar\Domain;


class DomainUtil
{
    /**
     * Normalize domain/TLD string format
     */
    private static function normalizeDomain(string $domainOrTld): string
    {
        $cleanString = strtolower(trim($domainOrTld));
        if (strpos($cleanString, '.') === false) {
            $cleanString = '.' . $cleanString;
        }
        return $cleanString;
    }

    // --- Secure Protocol Domains ---
    public static function isAppDomain(string $domainOrTld): bool {
        return (bool)preg_match('/\.app$/', self::normalizeDomain($domainOrTld));
    }
    public static function isDevDomain(string $domainOrTld): bool {
        return (bool)preg_match('/\.dev$/', self::normalizeDomain($domainOrTld));
    }
    public static function isBioDomain(string $domainOrTld): bool {
        return (bool)preg_match('/\.bio$/', self::normalizeDomain($domainOrTld));
    }

    // --- North America ---
    public static function isUsDomain(string $domainOrTld): bool {
        return (bool)preg_match('/\.us$/', self::normalizeDomain($domainOrTld));
    }
    public static function isCaDomain(string $domainOrTld): bool {
        return (bool)preg_match('/\.ca$/', self::normalizeDomain($domainOrTld));
    }
    public static function isNycDomain(string $domainOrTld): bool {
        return (bool)preg_match('/\.nyc$/', self::normalizeDomain($domainOrTld));
    }

    // --- Europe ---
    public static function isUkDomain(string $domainOrTld): bool {
        // Matches .uk, .co.uk, .org.uk, .me.uk, .net.uk, .ltd.uk, .plc.uk, etc.
        return (bool)preg_match('/\.(co|org|me|net|ltd|plc)?\.?uk$/', self::normalizeDomain($domainOrTld));
    }
    public static function isDeDomain(string $domainOrTld): bool {
        return (bool)preg_match('/\.de$/', self::normalizeDomain($domainOrTld));
    }
    public static function isEuDomain(string $domainOrTld): bool {
        return (bool)preg_match('/\.eu$/', self::normalizeDomain($domainOrTld));
    }
    public static function isFrDomain(string $domainOrTld): bool {
        // Matches .fr and its overseas territories: .re, .pm, .tf, .wf, .yt
        return (bool)preg_match('/\.(fr|re|pm|tf|wf|yt)$/', self::normalizeDomain($domainOrTld));
    }
    public static function isItDomain(string $domainOrTld): bool {
        return (bool)preg_match('/\.it$/', self::normalizeDomain($domainOrTld));
    }
    public static function isEsDomain(string $domainOrTld): bool {
        // Matches .es, .com.es, .nom.es, .org.es
        return (bool)preg_match('/\.(com|nom|org)?\.?es$/', self::normalizeDomain($domainOrTld));
    }
    public static function isSeDomain(string $domainOrTld): bool {
        // Matches .se, .tm.se, .org.se, .pp.se, .parti.se, .presse.se
        return (bool)preg_match('/\.(tm|org|pp|parti|presse)?\.?se$/', self::normalizeDomain($domainOrTld));
    }
    public static function isNuDomain(string $domainOrTld): bool {
        return (bool)preg_match('/\.nu$/', self::normalizeDomain($domainOrTld));
    }
    public static function isPtDomain(string $domainOrTld): bool {
        // Matches .pt, .com.pt, .co.pt, .edu.pt, .gov.pt, .net.pt, .org.pt
        return (bool)preg_match('/\.(com|co|edu|gov|net|org)?\.?pt$/', self::normalizeDomain($domainOrTld));
    }
    public static function isHuDomain(string $domainOrTld): bool {
        // Matches .hu, .co.hu
        return (bool)preg_match('/\.(co)?\.?hu$/', self::normalizeDomain($domainOrTld));
    }
    public static function isSwissDomain(string $domainOrTld): bool {
        return (bool)preg_match('/\.swiss$/', self::normalizeDomain($domainOrTld));
    }

    // --- Asia ---
    public static function isCnDomain(string $domainOrTld): bool {
        // Matches .cn, .com.cn, .net.cn, .org.cn
        return (bool)preg_match('/\.(com|net|org)?\.?cn$/', self::normalizeDomain($domainOrTld));
    }
    public static function isHkDomain(string $domainOrTld): bool {
        // Matches .hk, .com.hk, .edu.hk, .gov.hk, .idv.hk, .net.hk, .org.hk
        return (bool)preg_match('/\.(com|edu|gov|idv|net|org)?\.?hk$/', self::normalizeDomain($domainOrTld));
    }
    public static function isSgDomain(string $domainOrTld): bool {
        // Matches .sg, .com.sg, .edu.sg, .net.sg, .org.sg, .per.sg
        return (bool)preg_match('/\.(com|edu|net|org|per)?\.?sg$/', self::normalizeDomain($domainOrTld));
    }
    public static function isAsiaDomain(string $domainOrTld): bool {
        return (bool)preg_match('/\.asia$/', self::normalizeDomain($domainOrTld));
    }

    // --- Oceania ---
    public static function isAuDomain(string $domainOrTld): bool {
        // Matches .au, .com.au, .net.au, .org.au, .asn.au, .id.au
        return (bool)preg_match('/\.(com|net|org|asn|id)?\.?au$/', self::normalizeDomain($domainOrTld));
    }

    // --- South America ---
    public static function isBrDomain(string $domainOrTld): bool {
        // Matches .br and its common sub-TLDs like .com.br, .net.br, .org.br, etc.
        return (bool)preg_match('/\.(com|abc|belem|blog|emp|esp|far|floripa|ind|jampa|macapa|net|org|poa|recife|rio|sjc|tur|tv|vix)?\.?br$/', self::normalizeDomain($domainOrTld));
    }

    // --- Russia and Eastern Europe ---
    public static function isRuDomain(string $domainOrTld): bool {
        // Matches .ru and .xn--p1ai (.рф in Punycode)
        return (bool)preg_match('/\.(ru|xn--p1ai)$/', self::normalizeDomain($domainOrTld));
    }
    public static function isRoDomain(string $domainOrTld): bool {
        // Matches .ro and its sub-TLDs
        return (bool)preg_match('/\.(arts|co|com|firm|info|nom|nt|org|rec|ro|store|tm|www)?\.?ro$/', self::normalizeDomain($domainOrTld));
    }

    // --- Special Industry / Restricted Domains ---
    public static function isDentistDomain(string $domainOrTld): bool {
        // Matches .dentist, .attorney, .lawyer
        return (bool)preg_match('/\.(dentist|attorney|lawyer)$/', self::normalizeDomain($domainOrTld));
    }
    public static function isProDomain(string $domainOrTld): bool {
        return (bool)preg_match('/\.pro$/', self::normalizeDomain($domainOrTld));
    }
    public static function isAeroDomain(string $domainOrTld): bool {
        return (bool)preg_match('/\.aero$/', self::normalizeDomain($domainOrTld));
    }
    public static function isTravelDomain(string $domainOrTld): bool {
        return (bool)preg_match('/\.travel$/', self::normalizeDomain($domainOrTld));
    }
    public static function isCoopDomain(string $domainOrTld): bool {
        return (bool)preg_match('/\.coop$/', self::normalizeDomain($domainOrTld));
    }

    // --- Other Regional Domains ---
    public static function isDkDomain(string $domainOrTld): bool {
        return (bool)preg_match('/\.dk$/', self::normalizeDomain($domainOrTld));
    }
    public static function isPlDomain(string $domainOrTld): bool {
        // .pl has many sub-TLDs, match anything ending with .pl
        return (bool)preg_match('/\..*\.?pl$/', self::normalizeDomain($domainOrTld));
    }
    public static function isTelDomain(string $domainOrTld): bool {
        return (bool)preg_match('/\.tel$/', self::normalizeDomain($domainOrTld));
    }
    public static function isQuebecDomain(string $domainOrTld): bool {
        return (bool)preg_match('/\.quebec$/', self::normalizeDomain($domainOrTld));
    }
    public static function isScotDomain(string $domainOrTld): bool {
        return (bool)preg_match('/\.scot$/', self::normalizeDomain($domainOrTld));
    }
    public static function isJobsDomain(string $domainOrTld): bool {
        return (bool)preg_match('/\.jobs$/', self::normalizeDomain($domainOrTld));
    }
    public static function isVoteDomain(string $domainOrTld): bool {
        // Matches .vote and .voto
        return (bool)preg_match('/\.(vote|voto)$/', self::normalizeDomain($domainOrTld));
    }



    public static function mapWhmcsDomainStatus(string $apiDomainStatus): ?string
    {
        $statusMap = [
            'active' => Domain::STATUS_ACTIVE,
            'expired' => Domain::STATUS_EXPIRED,
            'transferaway' => Domain::STATUS_INACTIVE,
            // 'deleted'   => Domain::STATUS_DELETED,
        ];

        $apiDomainStatus = strtolower(trim($apiDomainStatus));
        return $statusMap[$apiDomainStatus] ?? null;
    }

    public static function formatToDynadotDomain(
        $duration,
        $auth_code,
        $registrant_contact_id,
        $admin_contact_id,
        $tech_contact_id,
        $billing_contact_id,
        $registrant_contact,
        $admin_contact,
        $tech_contact,
        $billing_contact,
        $customer_id,
        $name_server_list,
        $privacy
    ): array
    {
        return array(
            'duration' => $duration,
            'auth_code' => $auth_code,
            'registrant_contact_id' => $registrant_contact_id,
            'admin_contact_id' => $admin_contact_id,
            'tech_contact_id' => $tech_contact_id,
            'billing_contact_id' => $billing_contact_id,
            'registrant_contact' => $registrant_contact,
            'admin_contact' => $admin_contact,
            'tech_contact' => $tech_contact,
            'billing_contact' => $billing_contact,
            'customer_id' => $customer_id,
            'name_server_list' => $name_server_list,
            'privacy' => $privacy ? "full" : "off"
        );
    }
}