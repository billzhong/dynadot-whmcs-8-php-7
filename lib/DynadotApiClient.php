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

    /** @return array|object */

    private function request(string $method, string $endpoint, array $request = [])
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

    /** @return array|object */

    private function handleRequest(string $method, string $endpoint, object $request)
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

    /** @return array|object */

    public function domainGetTldPrices()
    {
        $endpoint = '/tld/prices';
        return $this->request('GET', $endpoint, ['currency' => 'USD']);
    }

    /** @return array|object */

    public function domainCheck(string $domainName)
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/check_availability';
        return $this->request('GET', $endpoint);
    }

    /** @return array|object */

    public function domainRegister(string $domainName, array $domain, bool $premiumEnabled, string $coupon, int $whmcsOrderId)
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

    /** @return array|object */

    public function domainRenew(string $domainName, int $duration, int $currentYear, string $coupon, int $whmcsOrderId)
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

    /** @return array|object */

    public function domainGraceDelete(string $domainName)
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/grace_delete';
        return $this->request('DELETE', $endpoint);
    }

    /** @return array|object */

    public function domainTagIn(string $domainName, array $domain, int $whmcsOrderId)
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/tag_in';
        $requestBody = [
            'domain' => $domain,
            'whmcs_order_id' => $whmcsOrderId,
        ];
        return $this->request('POST', $endpoint, $requestBody);
    }

    /** @return array|object */

    public function domainSaveTagOut(string $domainName, array $requestBody)
    {
        $endpoint = '/domains/' . urlencode(trim($domainName)) . '/tag_out';
        return $this->request('POST', $endpoint, $requestBody);
    }

    /** @return array|object */

    public function domainTransferIn(string $domainName, array $domain, bool $premiumEnabled, string $coupon, int $whmcsOrderId)
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

    /** @return array|object */

    public function domainGetTagStatus(string $domainName)
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/tag_status';
        return $this->request('GET', $endpoint, ['tag_type' => 'tag_in']);
    }

    /** @return array|object */

    public function domainGetTransferStatus(string $domainName)
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/transfer_status';
        return $this->request('GET', $endpoint, ['transfer_type' => 'transfer_in']);
    }

    /** @return array|object */

    public function domainGetNameservers(string $domainName)
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/nameservers';
        return $this->request('GET', $endpoint);
    }

    /** @return array|object */

    public function domainSaveNameservers(string $domainName, array $nameservers)
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/nameservers';
        $requestBody = [
            'nameserver_list' => $nameservers
        ];
        return $this->request('PUT', $endpoint, $requestBody);
    }

    /** @return array|object */

    public function domainGetContactDetails(string $domainName)
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/contacts';
        return $this->request('GET', $endpoint);
    }

    /** @return array|object */

    public function domainSaveContactDetails(
        string $domainName,
        array $registrantContact,
        array $adminContact,
        array $technicalContact,
        array $billingContact
    ) {
        $endpoint = '/domains/' . urlencode($domainName) . '/contacts';
        $requestBody = [
            'whmcs_registrant_contact' => $registrantContact,
            'whmcs_billing_contact'    => $billingContact,
            'whmcs_admin_contact'      => $adminContact,
            'whmcs_technical_contact'  => $technicalContact
        ];
        return $this->request('PUT', $endpoint, $requestBody);
    }

    /** @return array|object */

    public function domainGetDomainInfo(string $domainName)
    {
        $endpoint = '/domains/' . urlencode($domainName);
        return $this->request('GET', $endpoint);
    }

    /** @return array|object */

    public function domainSaveLockStatus(string $domainName, string $lockStatus)
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/lock_status';
        $requestBody = [
            'lock' => ($lockStatus === 'locked')
        ];
        return $this->request('PUT', $endpoint, $requestBody);
    }

    /** @return array|object */

    public function domainSavePrivacy(string $domainName, bool $enable)
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/privacy';
        $requestBody = [
            'privacy_level' => $enable ? 'full' : 'off'
        ];
        return $this->request('PUT', $endpoint, $requestBody);
    }

    /** @return array|object */

    public function domainGetTransferAuthCode(string $domainName)
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/transfer_auth_code';
        return $this->request('GET', $endpoint);
    }

    /** @return array|object */

    public function domainGetDnsRecords(string $domainName)
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/records';
        return $this->request('GET', $endpoint);
    }

    /** @return array|object */

    public function domainSaveDnsRecords(string $domainName, array $dnsMainList, array $dnsSubList, int $ttl)
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

    /** @return array|object */

    public function domainSaveEmailForwardingRecords(string $domainName, array $requestBody)
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/email_forwarding';
        return $this->request('PUT', $endpoint, $requestBody);
    }

    /** @return array|object */

    public function domainSetRegistrarContactExtension(string $domainName, $extension)
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/registrar_contact_extension';
        $requestBody = new stdClass();
        $requestBody->contact_extension = $extension;
        return $this->handleRequest('PUT', $endpoint, $requestBody);
    }

    /** @return array|object */

    public function domainSaveWhoisVerificationStatus(string $domainName, string $status)
    {
        $endpoint = '/domains/' . urlencode($domainName) . '/whois_verification_status';
        $requestBody = [
            'status' => $status
        ];
        return $this->request('PUT', $endpoint, $requestBody);
    }

    /** @return array|object */

    public function nameserverRegister(string $serverName, string $ip)
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

    /** @return array|object */

    public function nameserverModifyIp(string $serverName, array $ipList)
    {
        $endpoint = '/nameservers/' . urlencode($serverName) . '/set_ip';
        $requestBody = [
            'ip_list' => $ipList
        ];
        return $this->request('PUT', $endpoint, $requestBody);
    }

    /** @return array|object */

    public function nameserverDelete(string $serverName)
    {
        $endpoint = '/nameservers/' . urlencode($serverName);
        return $this->request('DELETE', $endpoint);
    }

    /** @return array|object */

    public function contactCreate(array $contact, int $whmcsClientId, int $whmcsContactId)
    {
        $endpoint = '/contacts/';
        $requestBody = [
            'contact'          => $contact,
            'whmcs_client_id' => $whmcsClientId,
            'whmcs_contact_id' => $whmcsContactId
        ];
        return $this->request('POST', $endpoint, $requestBody);
    }

    /** @return array|object */

    public function contactUpdate(int $whmcsClientId, int $whmcsContactId, array $contact)
    {
        $endpoint = '/clients/' . $whmcsClientId . '/contacts/' . $whmcsContactId . '/';
        $requestBody = [
            'contact' => $contact,
        ];
        return $this->request('PUT', $endpoint, $requestBody);
    }

    /** @return array|object */

    public function contactDelete(int $whmcsClientId, int $whmcsContactId)
    {
        $endpoint = '/clients/' . $whmcsClientId . '/contacts/' . $whmcsContactId . '/';
        return $this->request('DELETE', $endpoint);
    }
}