<?php

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Carbon;
use WHMCS\Domain\Registrar\Domain;
use WHMCS\Domain\TopLevel\ImportItem;
use WHMCS\Domains\DomainLookup\ResultsList;
use WHMCS\Domains\DomainLookup\SearchResult;
use WHMCS\Exception\Module\InvalidConfiguration;
use WHMCS\Module\Registrar\dynadot\controllers\DomainController;
use WHMCS\Module\Registrar\dynadot\DynadotApiClient;
use WHMCS\Module\Registrar\dynadot\RestfulApi;
use WHMCS\Module\Registrar\dynadot\utils\ContactUtil;
use WHMCS\Module\Registrar\dynadot\utils\DomainUtil;
use WHMCS\Module\Registrar\dynadot\utils\DynadotUtil;
use WHMCS\Module\Registrar\dynadot\utils\ModuleUtil;

function dynadot_MetaData(): array
{
    return array(
        "DisplayName" => "Dynadot",
        "APIVersion" => "1.1",
    );
}

function dynadot_getConfigArray(): array
{
    return array(
        "FriendlyName" => array(
            "Type" => "System",
            "Value" => "Dynadot Registrar Module for WHMCS",
        ),
        "Description" => array(
            "Type" => "System",
            "Value" => "Don't have a Dynadot Account yet? Get one here: <a target='_blank' href='https://www.dynadot.com/create_account.html'>www.dynadot.com</a>",
        ),
        "Username" => array(
            "FriendlyName" => "Username",
            "Type" => "text",
            "Description" => "Enter your Dynadot Reseller Account Username here",
        ),
        "ApiKey" => array(
            "FriendlyName" => "API Production Key",
            "Type" => "password",
            "Description" => "Enter your Dynadot API Production Key. You can generate and manage your keys in the <a target='_blank' href='https://www.dynadot.com/account/domain/setting/api.html'>Dynadot API Settings</a>.",
        ),
        "ApiSecretKey" => array(
            "FriendlyName" => "API Production Secret Key",
            "Type" => "password",
            "Description" => "Enter your Dynadot API Secret Key. You can generate and manage your keys in the <a target='_blank' href='https://www.dynadot.com/account/domain/setting/api.html'>Dynadot API Settings</a>.",
        ),
        "Coupon" => array(
            "FriendlyName" => "Coupon",
            "Type" => "textarea",
            "Description" => "Enter your Dynadot Coupons, one coupon per line (Split by new line).
            Only the first valid coupon will be used for the order. If no valid coupon found for the order, the order will be processed without any coupon.",
        ),
        "PreserveNameserversOnTransfer" => array(
            "FriendlyName" => "Preserve Nameservers During Transfer",
            "Type" => "yesno",
            "Description" => "Enable this to allow customers to keep their existing nameservers when transferring domains.",
        ),
    );
}

function dynadot_config_validate($params): void
{
    $apiKey = $params['ApiKey'];
    $apiSecretKey = $params['ApiSecretKey'];
    $userName = $params['Username'];
    $coupon = $params['Coupon'];
    try {
        $endpointPath = '/whmcs_active';
        $requestBody = [
            'username' => $userName,
            'coupon' => $coupon
        ];
        $response = RestfulApi::sendRequest($endpointPath, 'POST', $requestBody, $apiKey, $apiSecretKey);
        if ($response->code !== 200) {
            throw new InvalidConfiguration($response->error->description);
        }
    } catch (Exception $e) {
        throw new InvalidConfiguration($e->getMessage());
    }
}

function dynadot_RegisterDomain(array $params): array
{
    $apiKey = $params['ApiKey'];
    $apiSecretKey = $params['ApiSecretKey'];
    $coupon = $params['Coupon'] ?? '';
    $userid = $params['userid'];

    $domainName = ModuleUtil::extractDomainName($params);
    $registrationPeriod = $params['regperiod'];

    $registrantContact = ContactUtil::generateDynadotContact(
        $params["companyname"],
        $params["firstname"] . ' ' . $params["lastname"],
        $params["email"],
        $params["phonenumber"],
        $params["phonecc"],
        $params["address1"],
        $params["address2"],
        $params["city"],
        $params["fullstate"],
        $params["postcode"],
        $params["countrycode"]
    );
    $registrantExtension = DynadotUtil::getDomainExtensionData($domainName, $params);
    if (!empty($registrantExtension)){
        $registrantContact["contact_extension"] = $registrantExtension;
    }

    $adminContact = ContactUtil::generateDynadotContact(
        $params["admincompanyname"],
        $params["adminfirstname"] . ' ' . $params["adminlastname"],
        $params["adminemail"],
        $params["adminphonenumber"],
        $params["adminphonecc"],
        $params["adminaddress1"],
        $params["adminaddress2"],
        $params["admincity"],
        $params["adminfullstate"],
        $params["adminpostcode"],
        $params["admincountry"]
    );

    $nameservers = ModuleUtil::extractNameservers($params);

    // domain addon purchase status
    // $enableDnsManagement = (bool) $params['dnsmanagement'];
    // $enableEmailForwarding = (bool) $params['emailforwarding'];
    $enableIdProtection = (bool)$params['idprotection'];

    $api = new DynadotApiClient($apiKey, $apiSecretKey);

    // Domain premium check
    $registerPremium = false;
    $premiumDomainsEnabled = (bool)$params['premiumEnabled'];
    $premiumDomainsCost = $params['premiumCost'];
    if ($premiumDomainsEnabled && $premiumDomainsCost) {
        $checkDomainResult = $api->domainCheck($domainName);
        if (is_array($checkDomainResult) && isset($checkDomainResult['error'])) {
            return ['error' => $checkDomainResult['error']];
        }

        $domainStatus = $checkDomainResult->data->domain_status;
        if ($domainStatus !== 'available') {
            return ['error' => 'The domain is not available'];
        }

        $isPremiumDomain = $checkDomainResult->data->is_premium_domain === 'Yes';
        if ($isPremiumDomain) {
            $registerPremium = true;
        }
    }

    $domain = DomainUtil::formatToDynadotDomain(
        $registrationPeriod,
        "",
        -1,
        -1,
        -1,
        -1,
        $registrantContact,
        $adminContact,
        $registrantContact,
        $registrantContact,
        $userid,
        $nameservers,
        $enableIdProtection
    );

    $whmcsOrderId = ModuleUtil::fetchDomainWhmcsOrderId($params, 'register');

    $registerResult = $api->domainRegister($domainName, $domain, $registerPremium, $coupon, $whmcsOrderId);
    if (is_array($registerResult) && isset($registerResult['error'])) {
        return ['error' => $registerResult['error']];
    }

    return ['success' => true];
}

function dynadot_TransferDomain(array $params): array
{
    $apiKey = $params['ApiKey'];
    $apiSecretKey = $params['ApiSecretKey'];
    $coupon = $params['Coupon'] ?? '';
    $userid = $params['userid'];
    $isPreserveNameserversOnTransfer = $params['PreserveNameserversOnTransfer'];

    $domainName = ModuleUtil::extractDomainName($params);
    $registrationPeriod = $params['regperiod'];
    $eppCode = $params['eppcode'];

    $nameservers = [];
    if (!$isPreserveNameserversOnTransfer){
        $nameservers = ModuleUtil::extractNameservers($params);
    }

    $registrantContact = ContactUtil::generateDynadotContact(
        $params["companyname"],
        $params["firstname"] . ' ' . $params["lastname"],
        $params["email"],
        $params["phonenumber"],
        $params["phonecc"],
        $params["address1"],
        $params["address2"],
        $params["city"],
        $params["fullstate"],
        $params["postcode"],
        $params["countrycode"]
    );
    $registrantExtension = DynadotUtil::getDomainExtensionData($domainName, $params);
    if (!empty($registrantExtension)){
        $registrantContact["contact_extension"] = $registrantExtension;
    }

    $adminContact = ContactUtil::generateDynadotContact(
        $params["admincompanyname"],
        $params["adminfirstname"] . ' ' . $params["adminlastname"],
        $params["adminemail"],
        $params["adminphonenumber"],
        $params["adminphonecc"],
        $params["adminaddress1"],
        $params["adminaddress2"],
        $params["admincity"],
        $params["adminfullstate"],
        $params["adminpostcode"],
        $params["admincountry"]
    );

    $enableIdProtection = (bool)$params['idprotection'];

    $api = new DynadotApiClient($apiKey, $apiSecretKey);

    $transferPremium = false;
    $premiumDomainsEnabled = (bool)$params['premiumEnabled'];
    $premiumDomainsCost = $params['premiumCost'];

    if ($premiumDomainsEnabled && $premiumDomainsCost) {
        $checkDomainResult = $api->domainCheck($domainName);
        if (is_array($checkDomainResult) && isset($checkDomainResult['error'])) {
            return ['error' => $checkDomainResult['error']];
        }

        $isPremiumDomain = $checkDomainResult->data->is_premium_domain === 'Yes';
        if ($isPremiumDomain) {
            $transferPremium = true;
        }
    }

    $domain = DomainUtil::formatToDynadotDomain(
        $registrationPeriod,
        $eppCode,
        -1,
        -1,
        -1,
        -1,
        $registrantContact,
        $adminContact,
        $registrantContact,
        $registrantContact,
        $userid,
        $nameservers,
        $enableIdProtection
    );

    $whmcsOrderId = ModuleUtil::fetchDomainWhmcsOrderId($params, 'transfer');

    if (DomainUtil::isUkDomain($domainName)) {
        $transferResult = $api->domainTagIn($domainName, $domain, $whmcsOrderId);
    } else {
        $transferResult = $api->domainTransferIn($domainName, $domain, $transferPremium, $coupon, $whmcsOrderId);
    }

    if (is_array($transferResult) && isset($transferResult['error'])) {
        return ['error' => $transferResult['error']];
    }

    return ['success' => true];
}

function dynadot_RenewDomain(array $params): array
{
    $apiKey = $params['ApiKey'];
    $apiSecretKey = $params['ApiSecretKey'];
    $coupon = $params['Coupon'] ?? '';
    $domainName = ModuleUtil::extractDomainName($params);

    $duration = (int)$params['regperiod'];
    $expiryDate = $params['expiryDate'] ?? null;
    $currentYear = (int)date('Y');
    if (!empty($expiryDate)) {
        if (is_object($expiryDate) && method_exists($expiryDate, 'format')) {
            $currentYear = (int)$expiryDate->format('Y');
        } else {
            $yearStr = substr(trim((string)$expiryDate), 0, 4);
            if (is_numeric($yearStr) && (int)$yearStr > 1970) {
                $currentYear = (int)$yearStr;
            }
        }
    }

    $whmcsOrderId = ModuleUtil::fetchDomainWhmcsOrderId($params, 'renew');

    $api = new DynadotApiClient($apiKey, $apiSecretKey);
    $renewResult = $api->domainRenew($domainName, $duration, $currentYear, $coupon, $whmcsOrderId);
    if (is_array($renewResult) && isset($renewResult['error'])) {
        return ['error' => $renewResult['error']];
    }

    return ['success' => true];
}

function dynadot_GetNameservers(array $params): array
{
    $apiKey = $params['ApiKey'];
    $apiSecretKey = $params['ApiSecretKey'];
    $domainName = ModuleUtil::extractDomainName($params);

    $api = new DynadotApiClient($apiKey, $apiSecretKey);
    $nsResult = $api->domainGetNameservers($domainName);
    if (is_array($nsResult) && isset($nsResult['error'])) {
        return ['error' => $nsResult['error']];
    }

    return DynadotUtil::formatToWhmcsNameservers($nsResult->data);
}

function dynadot_SaveNameservers(array $params): array
{
    $apiKey = $params['ApiKey'];
    $apiSecretKey = $params['ApiSecretKey'];
    $domainName = ModuleUtil::extractDomainName($params);

    $nameservers = array();
    for ($i = 1; $i <= 5; $i++) {
        $ns = trim($params['ns' . $i] ?? '');
        if (!empty($ns)) {
            $nameservers[] = $ns;
        }
    }

    $api = new DynadotApiClient($apiKey, $apiSecretKey);
    $saveResult = $api->domainSaveNameservers($domainName, $nameservers);
    if (is_array($saveResult) && isset($saveResult['error'])) {
        return ['error' => $saveResult['error']];
    }

    return ['success' => true];
}

function dynadot_GetContactDetails(array $params): array
{
    $apiKey = $params['ApiKey'];
    $apiSecretKey = $params['ApiSecretKey'];
    $domainName = ModuleUtil::extractDomainName($params);

    $api = new DynadotApiClient($apiKey, $apiSecretKey);
    $contactResult = $api->domainGetContactDetails($domainName);
    if (is_array($contactResult) && isset($contactResult['error'])) {
        return ['error' => $contactResult['error']];
    }

    return [
        'Registrant' => ContactUtil::formatToWhmcsContact($contactResult->data->registrant_contact),
        'Admin'      => ContactUtil::formatToWhmcsContact($contactResult->data->admin_contact),
        'Technical'  => ContactUtil::formatToWhmcsContact($contactResult->data->technical_contact),
        'Billing'    => ContactUtil::formatToWhmcsContact($contactResult->data->billing_contact),
    ];
}

function dynadot_SaveContactDetails(array $params): array
{
    $apiKey = $params['ApiKey'];
    $apiSecretKey = $params['ApiSecretKey'];
    $domainName = ModuleUtil::extractDomainName($params);
    $contactDetails = $params['contactdetails'];

    $whmcsRegistrant = $contactDetails['Registrant'];
    $registrant = ContactUtil::generateDynadotContact(
        $whmcsRegistrant['Company Name'],
        $whmcsRegistrant['First Name'] . ' ' . $whmcsRegistrant['Last Name'],
        $whmcsRegistrant['Email Address'],
        $whmcsRegistrant['Phone Number'],
        $whmcsRegistrant['Phone Country Code'],
        $whmcsRegistrant['Address 1'],
        $whmcsRegistrant['Address 2'],
        $whmcsRegistrant['City'],
        $whmcsRegistrant['State'],
        $whmcsRegistrant['Postcode'],
        $whmcsRegistrant['Country'],
        true
    );
    $registrantExtension = DynadotUtil::getDomainExtensionData($domainName, $params);
    if (!empty($registrantExtension)){
        $registrant["contact_extension"] = $registrantExtension;
    }

    $whmcsAdmin = $contactDetails['Admin'];
    $admin = ContactUtil::generateDynadotContact(
        $whmcsAdmin['Company Name'],
        $whmcsAdmin['First Name'] . ' ' . $whmcsAdmin['Last Name'],
        $whmcsAdmin['Email Address'],
        $whmcsAdmin['Phone Number'],
        $whmcsAdmin['Phone Country Code'],
        $whmcsAdmin['Address 1'],
        $whmcsAdmin['Address 2'],
        $whmcsAdmin['City'],
        $whmcsAdmin['State'],
        $whmcsAdmin['Postcode'],
        $whmcsAdmin['Country'],
        true
    );

    $whmcsTechnical = $contactDetails['Technical'];
    $technical = ContactUtil::generateDynadotContact(
        $whmcsTechnical['Company Name'],
        $whmcsTechnical['First Name'] . ' ' . $whmcsTechnical['Last Name'],
        $whmcsTechnical['Email Address'],
        $whmcsTechnical['Phone Number'],
        $whmcsTechnical['Phone Country Code'],
        $whmcsTechnical['Address 1'],
        $whmcsTechnical['Address 2'],
        $whmcsTechnical['City'],
        $whmcsTechnical['State'],
        $whmcsTechnical['Postcode'],
        $whmcsTechnical['Country'],
        true
    );

    $whmcsBilling = $contactDetails['Billing'];
    $billing = ContactUtil::generateDynadotContact(
        $whmcsBilling['Company Name'],
        $whmcsBilling['First Name'] . ' ' . $whmcsBilling['Last Name'],
        $whmcsBilling['Email Address'],
        $whmcsBilling['Phone Number'],
        $whmcsBilling['Phone Country Code'],
        $whmcsBilling['Address 1'],
        $whmcsBilling['Address 2'],
        $whmcsBilling['City'],
        $whmcsBilling['State'],
        $whmcsBilling['Postcode'],
        $whmcsBilling['Country'],
        true
    );

    $api = new DynadotApiClient($apiKey, $apiSecretKey);
    $saveResult = $api->domainSaveContactDetails($domainName, $registrant, $admin, $technical, $billing);
    if (is_array($saveResult) && isset($saveResult['error'])) {
        return ['error' => $saveResult['error']];
    }

    return ['success' => true];
}

function dynadot_CheckAvailability(array $params): ResultsList
{
    $apiKey = $params['ApiKey'];
    $apiSecretKey = $params['ApiSecretKey'];

    $searchTerm = $params['searchTerm'];
    $punyCodeSearchTerm = $params['punyCodeSearchTerm'];
    $tldsToInclude = $params['tldsToInclude'];
    $isIdnDomain = (bool)$params['isIdnDomain'];
    $premiumEnabled = (bool)$params['premiumEnabled'];

    $searchDomainWord = $isIdnDomain ? $punyCodeSearchTerm : $searchTerm;

    try {
        $api = new DynadotApiClient($apiKey, $apiSecretKey);
        $results = new ResultsList();

        foreach ($tldsToInclude as $tld) {
            $asciiTld = idn_to_ascii($tld, IDNA_DEFAULT);
            if ($asciiTld === false) {
                $asciiTld = $tld;
            }
            $domainName = $searchDomainWord . $asciiTld;

            $apiResult = $api->domainCheck($domainName);
            if (is_array($apiResult) && isset($apiResult['error'])) {
                ModuleUtil::dynadotModuleLog('Dynadot_CheckAvailability Error', $domainName, $apiResult['error']);
                continue;
            }

            $sldPart = $searchDomainWord;
            $tldPart = ltrim($tld, '.');

            $searchResult = new SearchResult($sldPart, $tldPart);

            $domainStatus = $apiResult->data->domain_status ?? '';

            if ($domainStatus === 'available') {
                $status = SearchResult::STATUS_NOT_REGISTERED;
            } elseif ($domainStatus === 'registered') {
                $status = SearchResult::STATUS_REGISTERED;
            } elseif ($domainStatus === 'reserved') {
                $status = SearchResult::STATUS_RESERVED;
            } else {
                $status = SearchResult::STATUS_TLD_NOT_SUPPORTED;
            }
            $searchResult->setStatus($status);

            $isPremium = $apiResult->data->is_premium_domain === 'Yes';
            if ($isPremium && $premiumEnabled) {
                $searchResult->setPremiumDomain(true);
                $searchResult->setPremiumCostPricing(
                    array(
                        'register' => $apiResult->data->registration_price,
                        'renew' => $apiResult->data->renew_price,
                        'CurrencyCode' => $apiResult->data->currency,
                    )
                );
            }

            $results->append($searchResult);
        }

        return $results;
    } catch (Exception $e) {
        ModuleUtil::dynadotModuleLog('Dynadot_CheckAvailability Error', $searchDomainWord, $e->getMessage());
        return new ResultsList();
    }
}

function dynadot_GetDomainSuggestions(array $params): ResultsList
{
    return new ResultsList();
}

/** @return array|string */
function dynadot_GetRegistrarLock(array $params)
{
    $apiKey = $params['ApiKey'];
    $apiSecretKey = $params['ApiSecretKey'];
    $domainName = ModuleUtil::extractDomainName($params);

    $api = new DynadotApiClient($apiKey, $apiSecretKey);
    $infoResult = $api->domainGetDomainInfo($domainName);
    if (is_array($infoResult) && isset($infoResult['error'])) {
        return ['error' => $infoResult['error']];
    }

    $domainInfo = is_array($infoResult->data->domain_info)
        ? $infoResult->data->domain_info[0]
        : $infoResult->data->domain_info;

    $lockStatus = strtolower((string)($domainInfo->locked ?? ''));

    return ($lockStatus === 'yes') ? 'locked' : 'unlocked';
}

function dynadot_SaveRegistrarLock(array $params): array
{
    $apiKey = $params['ApiKey'];
    $apiSecretKey = $params['ApiSecretKey'];
    $domainName = ModuleUtil::extractDomainName($params);
    $lockStatus = $params['lockenabled'];

    $api = new DynadotApiClient($apiKey, $apiSecretKey);
    $saveResult = $api->domainSaveLockStatus($domainName, $lockStatus);
    if (is_array($saveResult) && isset($saveResult['error'])) {
        return ['error' => $saveResult['error']];
    }

    return ['success' => true];
}

function dynadot_IDProtectToggle(array $params): array
{
    $apiKey = $params['ApiKey'];
    $apiSecretKey = $params['ApiSecretKey'];
    $domainName = ModuleUtil::extractDomainName($params);
    $protectEnable = (bool)$params['protectenable'];

    $api = new DynadotApiClient($apiKey, $apiSecretKey);
    $privacyResult = $api->domainSavePrivacy($domainName, $protectEnable);
    if (is_array($privacyResult) && isset($privacyResult['error'])) {
        return ['error' => $privacyResult['error']];
    }

    return ['success' => 'success'];
}

function dynadot_GetEPPCode(array $params): array
{
    $apiKey = $params['ApiKey'];
    $apiSecretKey = $params['ApiSecretKey'];
    $domainName = ModuleUtil::extractDomainName($params);

    $api = new DynadotApiClient($apiKey, $apiSecretKey);
    $authResult = $api->domainGetTransferAuthCode($domainName);
    if (is_array($authResult) && isset($authResult['error'])) {
        return ['error' => $authResult['error']];
    }

    $authCode = $authResult->data->auth_code ?? '';
    if (!empty($authCode)) {
        return ['eppcode' => $authCode];
    }

    return ['success' => 'success'];
}

function dynadot_RequestDelete(array $params): array
{
    $apiKey = $params['ApiKey'];
    $apiSecretKey = $params['ApiSecretKey'];
    $domainName = ModuleUtil::extractDomainName($params);

    $api = new DynadotApiClient($apiKey, $apiSecretKey);
    $deleteResult = $api->domainGraceDelete($domainName);
    if (is_array($deleteResult) && isset($deleteResult['error'])) {
        return ['error' => $deleteResult['error']];
    }

    return ['success' => 'success'];
}

function dynadot_RegisterNameserver(array $params): array
{
    $apiKey = $params['ApiKey'];
    $apiSecretKey = $params['ApiSecretKey'];
    $nameserver = $params['nameserver'];
    $ipAddress = $params['ipaddress'];

    $api = new DynadotApiClient($apiKey, $apiSecretKey);
    $result = $api->nameserverRegister($nameserver, $ipAddress);
    if (is_array($result) && isset($result['error'])) {
        return ['error' => $result['error']];
    }

    return ['success' => 'success'];
}

function dynadot_ModifyNameserver(array $params): array
{
    $apiKey = $params['ApiKey'];
    $apiSecretKey = $params['ApiSecretKey'];
    $nameserver = $params['nameserver'];
    $newIpAddress = $params['newipaddress'];
    $ipList = [$newIpAddress];

    $api = new DynadotApiClient($apiKey, $apiSecretKey);
    $result = $api->nameserverModifyIp($nameserver, $ipList);
    if (is_array($result) && isset($result['error'])) {
        return ['error' => $result['error']];
    }

    return ['success' => 'success'];
}

function dynadot_DeleteNameserver(array $params): array
{
    $apiKey = $params['ApiKey'];
    $apiSecretKey = $params['ApiSecretKey'];
    $nameserver = $params['nameserver'];

    $api = new DynadotApiClient($apiKey, $apiSecretKey);
    $result = $api->nameserverDelete($nameserver);
    if (is_array($result) && isset($result['error'])) {
        return ['error' => $result['error']];
    }

    return ['success' => 'success'];
}

function dynadot_GetDomainInformation($params)
{
    $apiKey = $params['ApiKey'];
    $apiSecretKey = $params['ApiSecretKey'];
    $domainName = ModuleUtil::extractDomainName($params);

    $api = new DynadotApiClient($apiKey, $apiSecretKey);
    $infoResult = $api->domainGetDomainInfo($domainName);
    if (is_array($infoResult) && isset($infoResult['error'])) {
        return ['error' => $infoResult['error']];
    }

    $domainInfo = is_array($infoResult->data->domain_info)
        ? $infoResult->data->domain_info[0]
        : $infoResult->data->domain_info;

    $domain = (new Domain)
        ->setDomain($domainInfo->domain_name ?? $domainName)
        ->setNameservers(DynadotUtil::formatToWhmcsNameservers($domainInfo->glue_info->name_server_settings ?? []))
        ->setRegistrationStatus(DomainUtil::mapWhmcsDomainStatus($domainInfo->status ?? ''))
        ->setTransferLock(($domainInfo->locked ?? '') === 'Yes')
        ->setIdProtectionStatus(($domainInfo->privacy ?? '') === 'Full Privacy');

    if (isset($domainInfo->expiration) && $domainInfo->expiration > 0) {
        $domain->setExpiryDate(Carbon::createFromTimestampMs((int)$domainInfo->expiration));
    }

    if ($params['tld'] === 'us' && isset($domainInfo->contact_extension)) {
        try {
            ModuleUtil::syncUsAdditionalFields($params['domainid'], $domainInfo->contact_extension);
        } catch (\Exception $e) {
            ModuleUtil::dynadotModuleLog('dynadot_GetDomainInformation Error', 'Sync US Additional Fields', $e->getMessage());
        }
    }

    return $domain;
}

function dynadot_Sync(array $params): array
{
    $apiKey = $params['ApiKey'];
    $apiSecretKey = $params['ApiSecretKey'];
    $domainName = ModuleUtil::extractDomainName($params);

    $api = new DynadotApiClient($apiKey, $apiSecretKey);
    $infoResult = $api->domainGetDomainInfo($domainName);
    if (is_array($infoResult) && isset($infoResult['error'])) {
        return ['error' => $infoResult['error']];
    }

    $domainInfo = is_array($infoResult->data->domain_info)
        ? $infoResult->data->domain_info[0]
        : $infoResult->data->domain_info;

    $expiryDate = '';
    if (isset($domainInfo->expiration) && $domainInfo->expiration > 0) {
        $expiryDate = Carbon::createFromTimestampMs((int)$domainInfo->expiration)->format('Y-m-d');
    }

    $status = strtolower($domainInfo->status ?? '');

    return [
        'expirydate'      => $expiryDate, // YYYY-MM-DD
        'active'          => ($status === 'active'),
        'transferredAway' => ($status === 'transferaway'),
    ];
}

function dynadot_TransferSync(array $params): array
{
    $apiKey = $params['ApiKey'];
    $apiSecretKey = $params['ApiSecretKey'];
    $domainName = ModuleUtil::extractDomainName($params);

    $api = new DynadotApiClient($apiKey, $apiSecretKey);

    if (DomainUtil::isUkDomain($domainName)) {
        $syncResult = $api->domainGetTagStatus($domainName);
    } else {
        $syncResult = $api->domainGetTransferStatus($domainName);
    }

    if (is_array($syncResult) && isset($syncResult['error'])) {
        return ['error' => $syncResult['error']];
    }

    if (DomainUtil::isUkDomain($domainName)) {
        $tagList = (array) $syncResult->data->domain_tag_status_list;
        if (empty($tagList)) {
            return [];
        }

        usort($tagList, static function($a, $b) {
            return $b->order_created_date - $a->order_created_date;
        });

        $latestData = $tagList[0];
        $status = $latestData->tag_status;

        if ($status === 'Acquired') {
            $result = ['completed' => true];
            if ($latestData->order_completed_date > 0) {
                $result['expirydate'] = Carbon::createFromTimestampMs((int)$latestData->order_completed_date)->format('Y-m-d');
            }
            return $result;
        }

        if (in_array($status, ['Cancelled', 'Deleted', 'Problem'], true)) {
            return ['failed' => true, 'reason' => "Tag-In $status"];
        }
    } else {
        $statusList = (array) $syncResult->data->domain_transfer_status_list;
        if (empty($statusList)) {
            return [];
        }

        usort($statusList, static function($a, $b) {
            return $b->order_created_date - $a->order_created_date;
        });

        $latestData = $statusList[0];
        $status = $latestData->transfer_status ?? '';

        if ($status === 'Transferred') {
            $result = ['completed' => true];
            if (isset($latestData->order_completed_date) && $latestData->order_completed_date > 0) {
                $result['expirydate'] = Carbon::createFromTimestampMs((int)$latestData->order_completed_date)->format('Y-m-d');
            }
            return $result;
        }

        if (in_array($status, ['Failed', 'Problem', 'Cancelled', 'Denied', 'Problem Timeout', 'Problem Starting', 'Ineligible', 'Pre Sale Domain'], true)) {
            return ['failed' => true, 'reason' => "Transfer $status"];
        }
    }

    return [];
}

/** @return array|ResultsList */
function dynadot_GetTldPricing($params)
{
    $apiKey = $params['ApiKey'];
    $apiSecretKey = $params['ApiSecretKey'];
    $results = new ResultsList;

    $api = new DynadotApiClient($apiKey, $apiSecretKey);
    $priceResult = $api->domainGetTldPrices();
    if (is_array($priceResult) && isset($priceResult['error'])) {
        return $priceResult;
    }

    $tldPriceArray = $priceResult->data->tld_price_list ?? [];
    $currency = $priceResult->data->currency;

    foreach ($tldPriceArray as $tldPrice) {
        $registerPrice = (float)$tldPrice->all_years_register_price[0];
        $renewPrice    = (float)$tldPrice->all_years_renew_price[0];
        $transferPrice = (float)$tldPrice->transfer_price;
        $restorePrice  = (float)$tldPrice->restore_price;
        $gracePeriodDays   = (int)$tldPrice->renew_grace_period;
        $restorePeriodDays = (int)$tldPrice->restore_period;
        $redemptionFeePrice = $restorePrice - $renewPrice;

        $item = (new ImportItem)
            ->setExtension($tldPrice->tld)
            ->setMinYears((int)($tldPrice->min_duration))
            ->setMaxYears((int)($tldPrice->max_duration))
            ->setRegisterPrice($registerPrice)
            ->setRenewPrice($renewPrice)
            ->setTransferPrice($transferPrice)
            ->setGraceFeeDays($gracePeriodDays)
            ->setGraceFeePrice((float)($tldPrice->grace_fee_price))
            ->setRedemptionFeeDays($restorePeriodDays)
            ->setRedemptionFeePrice($redemptionFeePrice)
            ->setCurrency($currency);

        $results[] = $item;
    }

    return $results;
}

/**
 * Do not define the `dynadot_Get/SaveDNS` function; use the built-in DNS management page instead.
 * Update DNS Host Records.
 * @param array $params common module parameters
 * @return array
 */
//function dynadot_GetDNS(array $params): array{ return []; }
//function dynadot_SaveDNS(array $params): array{ return []; }
function dynadot_DnsManagement(array $params): array
{
    try {
        $controller = new DomainController($params, $_GET, $_POST);
        $handlerResult = $controller->handleRequestFromDnsManagement();

        $action           = $handlerResult['action'];
        $code             = $handlerResult['response']->code;
        $errorDescription = $handlerResult['response']->error->description ?? '';
        $data             = $handlerResult['response']->data;
    } catch (Exception $e) {
        $action           = "GET_DNS_RECORDS";
        $code             = 400;
        $errorDescription = $e->getMessage();
        $data             = null;

        try {
            $readController = new DomainController($params, $_GET, []);
            $readResult = $readController->handleRequestFromDnsManagement();
            if (!empty($readResult['response']->data)) {
                $data = $readResult['response']->data;
            }
        } catch (Exception $e) {
        }
    }

    $queryString = http_build_query([
        'action'   => 'domaindetails',
        'domainid' => $params['domainid'],
        'modop'    => 'custom',
        'a'        => 'DnsManagement',
    ]);

    return [
        'templatefile' => 'dynadot-dns-management',
        'breadcrumb' => [
            'clientarea.php?' . $queryString => 'Domain DNS Management',
        ],
        'vars' => [
            'action'            => $action,
            'code'              => $code,
            'error_description' => $errorDescription,
            'data'              => $data,
            'domainid'          => $params['domainid'],
        ]
    ];
}

/**
 * Do not define the `dynadot_Get/SaveEmailForwarding` function; use the built-in Email Forwarding management page instead.
 * Update DNS Host Records.
 * @param array $params common module parameters
 * @return array
 */
//function dynadot_GetEmailForwarding(array $params): array{ return []; }
//function dynadot_SaveEmailForwarding(array $params): array{ return []; }
function dynadot_EmailForwarding(array $params): array
{
    try {
        $controller = new DomainController($params, $_GET, $_POST);
        $handlerResult = $controller->handleRequestFromEmailForwarding();

        $action           = $handlerResult['action'];
        $code             = $handlerResult['response']->code;
        $errorDescription = $handlerResult['response']->error->description ?? '';
        $data             = $handlerResult['response']->data;
    } catch (Exception $e) {
        $action           = "GET_EMAIL_FORWARDING";
        $code             = 400;
        $errorDescription = $e->getMessage();
        $data             = null;
    }

    $queryString = http_build_query([
        'action'   => 'domaindetails',
        'domainid' => $params['domainid'],
        'modop'    => 'custom',
        'a'        => 'EmailForwarding',
    ]);

    return [
        'templatefile' => 'dynadot-email-forwarding',
        'breadcrumb' => [
            'clientarea.php?' . $queryString => 'Domain Email Forwarding',
        ],
        'vars' => [
            'action'            => $action,
            'code'              => $code,
            'error_description' => $errorDescription,
            'data'              => $data,
            'domainid'          => $params['domainid'],
        ]
    ];
}

function dynadot_TagOut(array $params): array
{
    try {
        $controller = new DomainController($params, $_GET, $_POST);
        $handlerResult = $controller->handleRequestFromTagOut();

        $action           = $handlerResult['action'];
        $code             = $handlerResult['response']->code;
        $errorDescription = $handlerResult['response']->error->description ?? '';
        $data             = $handlerResult['response']->data;
    } catch (Exception $e) {
        $action           = "TAG_OUT";
        $code             = 400;
        $errorDescription = $e->getMessage();
        $data             = [];
    }

    $queryString = http_build_query([
        'action'   => 'domaindetails',
        'domainid' => $params['domainid'],
        'modop'    => 'custom',
        'a'        => 'TagOut',
    ]);

    return [
        'templatefile' => 'dynadot-tag-out',
        'breadcrumb' => [
            'clientarea.php?' . $queryString => 'Domain Tag Out',
        ],
        'vars' => [
            'action'            => $action,
            'code'              => $code,
            'error_description' => $errorDescription,
            'data'              => $data,
            'domainid'          => $params['domainid'],
        ]
    ];
}

function dynadot_MarkContactAsVerified($params): array
{
    $apiKey = $params['ApiKey'];
    $apiSecretKey = $params['ApiSecretKey'];
    $domainName = ModuleUtil::extractDomainName($params);

    $api = new DynadotApiClient($apiKey, $apiSecretKey);
    $result = $api->domainSaveWhoisVerificationStatus($domainName, 'verified');
    if (is_array($result) && isset($result['error'])) {
        return ['error' => $result['error']];
    }

    return [
        'success' => true,
        'message' => 'The contact verification status was successfully changed at the registrar'
    ];
}

function dynadot_ClientAreaAllowedFunctions(array $params): array
{
    $functions = [];

    if (!empty($params['dnsmanagement'])) {
        $functions[] = 'DnsManagement';
    }

    if (!empty($params['emailforwarding'])) {
        $functions[] = 'EmailForwarding';
    }

    return $functions;
}

function dynadot_ClientAreaCustomButtonArray(array $params): array
{
    $buttons = [];

    // If DNS management is enabled for this domain
    if (!empty($params['dnsmanagement'])) {
        $buttons["DNS Management"] = "DnsManagement";
    }

    // If Email Forwarding is enabled for this domain
    if (!empty($params['emailforwarding'])) {
        $buttons["Email Forwarding"] = "EmailForwarding";
    }

    // Dynamic Check: Display the IPS Tag modification button only if the domain is a .UK domain.
    $domainName = $params['domain'] ?? '';
    if (DomainUtil::isUkDomain($domainName)) {
        $buttons["Release Registrar Tag"] = "TagOut";
    }

    return $buttons;
}

function dynadot_AdminCustomButtonArray(array $params): array
{
    $registrar = $params['registrar'] ?? '';
    if ($registrar !== 'dynadot') {
        return [];
    }

    return [
        'Mark Contact As Verified' => 'MarkContactAsVerified',
    ];
}