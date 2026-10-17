<?php

namespace WHMCS\Module\Registrar\dynadot;

use Exception;
use stdClass;
use WHMCS\Module\Registrar\dynadot\utils\DomainUtil;
use WHMCS\Module\Registrar\dynadot\utils\DynadotUtil;

class DynadotApiClient
{
    private string $apiKey;
    private string $apiSecretKey;

    public function __construct(string $apiKey, string $apiSecretKey)
    {
        $this->apiKey = $apiKey;
        $this->apiSecretKey = $apiSecretKey;
    }

    private function request(string $method, string $endpoint, array $request = []): array|object
    {
        try {
            $response = RestfulApi::sendRequest($endpoint, $method, $request, $this->apiKey, $this->apiSecretKey);

            if (is_object($response) && isset($response->code) && $response->code >= 200 && $response->code < 300) {
                return $response;
            }

            $errorDescription = $response->error->description ?? DynadotUtil::UNEXPECTED_ERROR;
            return ['error' => $errorDescription];

        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function handleRequest(string $method, string $endpoint, object $request): array|object
    {
        try {
            $response = RestfulApi::sendRequest($endpoint, $method, $request, $this->apiKey, $this->apiSecretKey);

            if (is_object($response) && isset($response->code) && $response->code >= 200 && $response->code < 300) {
                return $response;
            }

            $errorDescription = $response->error->description ?? DynadotUtil::UNEXPECTED_ERROR;
            return ['error' => $errorDescription];

        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function domainGetTldPrices(): array|object
    {
        $endpoint = '/tld/prices';
        return $this->request('GET', $endpoint, ['currency' => 'USD']);
    }

    public function domainCheck(string $domainName): array|object
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/check_availability';
        return $this->request('GET', $endpoint);
    }

    public function domainRegister(string $domainName, array $domain, bool $premiumEnabled, string $coupon, int $whmcsOrderId): array|object
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/register';
        $requestBody = [
            'domain'           => $domain,
            'register_premium' => $premiumEnabled,
            'coupon_code'      => $coupon,
            'whmcs_order_id'   => $whmcsOrderId,
        ];

        return $this->request('POST', $endpoint, $requestBody);
    }

    public function domainRenew(string $domainName, int $duration, int $currentYear, string $coupon, int $whmcsOrderId): array|object
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/renew';
        $requestBody = [
            'duration'    => $duration,
            'year'        => $currentYear,
            'coupon' => $coupon,
            'whmcs_order_id' => $whmcsOrderId,
        ];
        return $this->request('POST', $endpoint, $requestBody);
    }

    public function domainGraceDelete(string $domainName): array|object
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/grace_delete';
        return $this->request('DELETE', $endpoint);
    }

    public function domainTagIn(string $domainName, array $domain, int $whmcsOrderId): array|object
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/tag_in';
        $requestBody = [
            'domain' => $domain,
            'whmcs_order_id' => $whmcsOrderId,
        ];
        return $this->request('POST', $endpoint, $requestBody);
    }

    public function domainSaveTagOut(string $domainName, array $requestBody): array|object
    {
        $endpoint = '/domains/' . urlencode(trim($domainName)) . '/tag_out';
        return $this->request('POST', $endpoint, $requestBody);
    }

    public function domainTransferIn(string $domainName, array $domain, bool $premiumEnabled, string $coupon, int $whmcsOrderId): array|object
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/transfer_in';
        $requestBody = [
            'domain'            => $domain,
            'transfer_premium'  => $premiumEnabled,
            'coupon_code'       => $coupon,
            'whmcs_order_id'    => $whmcsOrderId,
        ];
        return $this->request('POST', $endpoint, $requestBody);
    }

    public function domainGetTagStatus(string $domainName): array|object
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/tag_status';
        return $this->request('GET', $endpoint, ['tag_type' => 'tag_in']);
    }

    public function domainGetTransferStatus(string $domainName): array|object
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/transfer_status';
        return $this->request('GET', $endpoint, ['transfer_type' => 'transfer_in']);
    }

    public function domainGetNameservers(string $domainName): array|object
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/nameservers';
        return $this->request('GET', $endpoint);
    }

    public function domainSaveNameservers(string $domainName, array $nameservers): array|object
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/nameservers';
        $requestBody = [
            'nameserver_list' => $nameservers
        ];
        return $this->request('PUT', $endpoint, $requestBody);
    }

    public function domainGetContactDetails(string $domainName): array|object
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/contacts';
        return $this->request('GET', $endpoint);
    }

    public function domainSaveContactDetails(
        string $domainName,
        array $registrantContact,
        array $adminContact,
        array $technicalContact,
        array $billingContact
    ): array|object {
        $endpoint = '/domains/' . urlencode($domainName) . '/contacts';
        $requestBody = [
            'whmcs_registrant_contact' => $registrantContact,
            'whmcs_billing_contact'    => $billingContact,
            'whmcs_admin_contact'      => $adminContact,
            'whmcs_technical_contact'  => $technicalContact
        ];
        return $this->request('PUT', $endpoint, $requestBody);
    }

    public function domainGetDomainInfo(string $domainName): array|object
    {
        $endpoint = '/domains/' . urlencode($domainName);
        return $this->request('GET', $endpoint);
    }

    public function domainSaveLockStatus(string $domainName, string $lockStatus): array|object
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/lock_status';
        $requestBody = [
            'lock' => ($lockStatus === 'locked')
        ];
        return $this->request('PUT', $endpoint, $requestBody);
    }

    public function domainSavePrivacy(string $domainName, bool $enable): array|object
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/privacy';
        $requestBody = [
            'privacy_level' => $enable ? 'full' : 'off'
        ];
        return $this->request('PUT', $endpoint, $requestBody);
    }

    public function domainGetTransferAuthCode(string $domainName): array|object
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/transfer_auth_code';
        return $this->request('GET', $endpoint);
    }

    public function domainGetDnsRecords(string $domainName): array|object
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/records';
        return $this->request('GET', $endpoint);
    }

    public function domainSaveDnsRecords(string $domainName, array $dnsMainList, array $dnsSubList, int $ttl): array|object
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/records';
        $requestBody = [
            'dns_main_list'              => $dnsMainList,
            'dns_sub_list'               => $dnsSubList,
            'ttl'                        => $ttl,
            'add_dns_to_current_setting' => false
        ];
        return $this->request('PUT', $endpoint, $requestBody);
    }

    public function domainSaveEmailForwardingRecords(string $domainName, array $requestBody): array|object
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/email_forwarding';
        return $this->request('PUT', $endpoint, $requestBody);
    }

    public function domainSetRegistrarContactExtension(string $domainName, $extension): array|object
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/registrar_contact_extension';
        $requestBody = new stdClass();
        $requestBody->contact_extension = $extension;
        return $this->handleRequest('PUT', $endpoint, $requestBody);
    }

    public function domainSaveWhoisVerificationStatus(string $domainName, string $status): array|object
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/whois_verification_status';
        $requestBody = [
            'status' => $status
        ];
        return $this->request('PUT', $endpoint, $requestBody);
    }

    public function nameserverRegister(string $serverName, string $ip): array|object
    {
        $endpoint = '/nameservers/register';
        $requestBody = [
            'nameserver' => [
                'server_name' => $serverName,
                'ip'          => $ip
            ]
        ];
        return $this->request('POST', $endpoint, $requestBody);
    }

    public function nameserverModifyIp(string $serverName, array $ipList): array|object
    {
        $endpoint = '/nameservers/' . urlencode($serverName) . '/set_ip';
        $requestBody = [
            'ip_list' => $ipList
        ];
        return $this->request('PUT', $endpoint, $requestBody);
    }

    public function nameserverDelete(string $serverName): array|object
    {
        $endpoint = '/nameservers/' . urlencode($serverName);
        return $this->request('DELETE', $endpoint);
    }

    public function contactCreate(array $contact, int $whmcsClientId, int $whmcsContactId): array|object
    {
        $endpoint = '/contacts/';
        $requestBody = [
            'contact'          => $contact,
            'whmcs_client_id' => $whmcsClientId,
            'whmcs_contact_id' => $whmcsContactId
        ];
        return $this->request('POST', $endpoint, $requestBody);
    }

    public function contactUpdate(int $whmcsClientId, int $whmcsContactId, array $contact): array|object
    {
        $endpoint = '/clients/' . $whmcsClientId . '/contacts/' . $whmcsContactId . '/';
        $requestBody = [
            'contact' => $contact,
        ];
        return $this->request('PUT', $endpoint, $requestBody);
    }

    public function contactDelete(int $whmcsClientId, int $whmcsContactId): array|object
    {
        $endpoint = '/clients/' . $whmcsClientId . '/contacts/' . $whmcsContactId . '/';
        return $this->request('DELETE', $endpoint);
    }
}