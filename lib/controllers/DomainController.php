<?php

namespace WHMCS\Module\Registrar\dynadot\controllers;

use stdClass;
use WHMCS\Module\Registrar\dynadot\DynadotApiClient;
use WHMCS\Module\Registrar\dynadot\utils\ModuleUtil;

class DomainController extends BaseController
{
    public function handleRequestFromDnsManagement(): array
    {
        $domainName = ModuleUtil::extractDomainName($this->params);

        $api = new DynadotApiClient($this->params['ApiKey'], $this->params['ApiSecretKey']);
        $saveError = null;

        if (isset($_REQUEST['page-event']) && $_REQUEST['page-event'] === 'saveDnsRecord') {
            $mainRecordTypes = $_REQUEST['dnsRecordType'] ?? [];
            $mainRecordAddresses = $_REQUEST['dnsRecordAddress'] ?? [];
            $mainExtends = $_REQUEST['extend'] ?? [];

            $subDnsNames = $_REQUEST['subDnsName'] ?? [];
            $subRecordTypes = $_REQUEST['subDnsRecordType'] ?? [];
            $subRecordAddresses = $_REQUEST['subDnsRecordAddress'] ?? [];
            $subExtends = $_REQUEST['subExtend'] ?? [];

            $ttl = isset($_REQUEST['glue_dns_ttl']) ? (int)$_REQUEST['glue_dns_ttl'] : 300;

            $dnsMainList = [];
            $dnsSubList = [];

            $mainCount = count($mainRecordTypes);
            for ($i = 0; $i < $mainCount; $i++) {
                $recordType = isset($mainRecordTypes[$i]) ? strtolower(trim($mainRecordTypes[$i])) : null;
                $recordAddress = $mainRecordAddresses[$i] ?? null;
                $extend = $mainExtends[$i] ?? null;

                if (!empty($recordType) && !empty($recordAddress)) {
                    $item = [
                        'record_type' => $recordType,
                        'record_value1' => $recordAddress
                    ];

                    if (in_array($recordType, ['forward', 'mx', 'stealth', 'email'], true)) {
                        if ($extend !== 'N/A' && $extend !== null && $extend !== '') {
                            $item['record_value2'] = (string)$extend;
                        }
                    }
                    $dnsMainList[] = $item;
                }
            }

            $subCount = count($subRecordTypes);
            for ($i = 0; $i < $subCount; $i++) {
                $subHost = $subDnsNames[$i] ?? null;
                $recordType = isset($subRecordTypes[$i]) ? strtolower(trim($subRecordTypes[$i])) : null;
                $recordAddress = $subRecordAddresses[$i] ?? null;
                $extend = $subExtends[$i] ?? null;

                if (!empty($recordType) && !empty($recordAddress)) {
                    $item = [
                        'sub_host' => !empty($subHost) ? $subHost : '@',
                        'record_type' => $recordType,
                        'record_value1' => $recordAddress
                    ];

                    if (in_array($recordType, ['forward', 'mx', 'stealth', 'email'], true)) {
                        if ($extend !== 'N/A' && $extend !== null && $extend !== '') {
                            $item['record_value2'] = (string)$extend;
                        }
                    }
                    $dnsSubList[] = $item;
                }
            }

            $setResult = $api->domainSaveDnsRecords($domainName, $dnsMainList, $dnsSubList, $ttl);

            if (is_array($setResult) && isset($setResult['error'])) {
                $saveError = $setResult['error'];
            } else {
                $actionStatus = "SET_DNS_RECORDS_SUCCESS";
            }
        }

        if ($saveError) {
            $mainDomains = [];
            $mainRecordTypes = $_REQUEST['dnsRecordType'] ?? [];
            $mainRecordAddresses = $_REQUEST['dnsRecordAddress'] ?? [];
            $mainExtends = $_REQUEST['extend'] ?? [];

            foreach ($mainRecordTypes as $i => $type) {
                $addr = $mainRecordAddresses[$i] ?? '';
                $ext = $mainExtends[$i] ?? 'N/A';

                if ($addr === '' && $i === (count($mainRecordTypes) - 1)) {
                    continue;
                }

                $mainDomains[] = (object)[
                    'record_type' => $type,
                    'value' => html_entity_decode($addr, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    'value2' => $ext
                ];
            }

            $subDomains = [];
            $subDnsNames = $_REQUEST['subDnsName'] ?? [];
            $subRecordTypes = $_REQUEST['subDnsRecordType'] ?? [];
            $subRecordAddresses = $_REQUEST['subDnsRecordAddress'] ?? [];
            $subExtends = $_REQUEST['subExtend'] ?? [];

            foreach ($subRecordTypes as $i => $type) {
                $host = $subDnsNames[$i] ?? '';
                $addr = $subRecordAddresses[$i] ?? '';
                $ext = $subExtends[$i] ?? 'N/A';

                if ($addr === '' && $host === '' && $i === (count($subRecordTypes) - 1)) {
                    continue;
                }

                $subDomains[] = (object)[
                    'sub_host' => $host,
                    'record_type' => $type,
                    'value' => html_entity_decode($addr, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    'value2' => $ext
                ];
            }

            $ttlValue = $_REQUEST['glue_dns_ttl'] ?? '300';

            $fallbackData = (object)[
                'domainid' => $this->params['domainid'],
                'name_server_settings' => (object)[
                    'main_domains' => $mainDomains,
                    'sub_domains' => $subDomains,
                    'ttl' => $ttlValue
                ]
            ];

            return [
                'action' => 'SET_DNS_RECORDS',
                'response' => (object)[
                    'code' => 400,
                    'error' => (object)['description' => $saveError],
                    'data' => $fallbackData,
                ]
            ];
        }

        $getResult = $api->domainGetDnsRecords($domainName);
        if (is_array($getResult) && isset($getResult['error'])) {
            return $this->error(isset($actionStatus) ? 'SET_DNS_RECORDS' : 'GET_DNS_RECORDS', $getResult['error']);
        }

        $getResult->domainid = $this->params['domainid'];
        $action = isset($actionStatus) ? 'SET_DNS_RECORDS' : 'GET_DNS_RECORDS';

        return $this->response($action, $getResult);
    }

    public function handleRequestFromEmailForwarding(): array
    {
        $domainName = ModuleUtil::extractDomainName($this->params);

        $api = new DynadotApiClient($this->params['ApiKey'], $this->params['ApiSecretKey']);
        $saveError = null;

        if (isset($_REQUEST['submitType']) && $_REQUEST['submitType'] === 'saveEmailForwarding') {
            $forwardType = $_REQUEST['forwardType'] ?? $_REQUEST['recordType'] ?? 'forward';
            $recordList = [];

            $cleanForwardType = strtoupper($forwardType) === 'MX' ? 'MX' : strtolower($forwardType);

            $requestBody = ['forward_type' => $cleanForwardType];

            if ($cleanForwardType === 'donot') {
                $requestBody['forward_type'] = 'none';

            } elseif ($cleanForwardType === 'MX') {
                $hosts = $_REQUEST['host'] ?? [];
                $distances = $_REQUEST['distance'] ?? [];

                foreach ($hosts as $index => $host) {
                    if (!empty($host)) {
                        $recordList[] = [
                            'host'     => (string)$host,
                            'distance' => (string)($distances[$index] ?? '1')
                        ];
                    }
                }
                $requestBody['mx_record_list'] = $recordList;
            } else {
                $emails = $_REQUEST['email'] ?? [];
                $aliases = $_REQUEST['alias'] ?? [];

                foreach ($emails as $index => $email) {
                    if (!empty($email) && !empty($aliases[$index])) {
                        $recordList[] = [
                            'username' => (string)$aliases[$index],
                            'email'    => (string)$email
                        ];
                    }
                }

                if (empty($recordList)) {
                    $requestBody['forward_type'] = 'donot';
                } else {
                    $requestBody['forward_detail_list'] = $recordList;
                }
            }

            $setResult = $api->domainSaveEmailForwardingRecords($domainName, $requestBody);
            if (is_array($setResult) && isset($setResult['error'])) {
                $saveError = $setResult['error'];
            } else {
                $actionStatus = "SET_EMAIL_FORWARDING_SUCCESS";
            }
        }

        if ($saveError) {
            $fallbackData = new stdClass();

            if ($cleanForwardType === 'MX') {
                $fallbackData->forward_type = 'MX';
                $fallbackData->mx_record_list = [];

                $hosts = $_REQUEST['host'] ?? [];
                $distances = $_REQUEST['distance'] ?? [];
                foreach ($hosts as $index => $host) {
                    if ($host === '' && $index === (count($hosts) - 1)) {
                        continue;
                    }
                    $fallbackData->mx_record_list[] = (object)[
                        'host' => html_entity_decode($host, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                        'distance' => $distances[$index] ?? '1'
                    ];
                }
            } else {
                $fallbackData->forward_type = $cleanForwardType;
                $fallbackData->forward_detail_list = [];

                $emails = $_REQUEST['email'] ?? [];
                $aliases = $_REQUEST['alias'] ?? [];
                foreach ($emails as $index => $email) {
                    $alias = $aliases[$index] ?? '';
                    if ($email === '' && $alias === '' && $index === (count($emails) - 1)) {
                        continue;
                    }
                    $fallbackData->forward_detail_list[] = (object)[
                        'username' => html_entity_decode($alias, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                        'email' => html_entity_decode($email, ENT_QUOTES | ENT_HTML5, 'UTF-8')
                    ];
                }
            }

            return [
                'action' => 'SET_EMAIL_FORWARDING',
                'response' => (object)[
                    'code' => 400,
                    'error' => (object)['description' => $saveError],
                    'data' => $fallbackData,
                    'domainid' => $this->params['domainid']
                ]
            ];
        }

        $infoResult = $api->domainGetDomainInfo($domainName);
        if (is_array($infoResult) && isset($infoResult['error'])) {
            return $this->error('GET_EMAIL_FORWARDING', $infoResult['error']);
        }

        $domainInfo = is_array($infoResult->data->domain_info)
            ? $infoResult->data->domain_info[0]
            : $infoResult->data->domain_info;

        $getResult = new stdClass();
        $getResult->code = 200;
        $getResult->data = $domainInfo->glue_info->name_server_settings->email_forwarding ?? null;
        $getResult->domainid = $this->params['domainid'];

        $action = isset($actionStatus) ? 'SET_EMAIL_FORWARDING' : 'GET_EMAIL_FORWARDING';

        return $this->response($action, $getResult);
    }

    public function handleRequestFromTagOut(): array
    {
        $action = $this->postData['tagout_action'] ?? '';
        if ($this->postData['tagout_action'] !== 'do_release') {
            $getResult = new stdClass();
            $getResult->code = 202;
            $getResult->data = null;
            return $this->response($action, $getResult);
        }

        $domainId = $this->params['domainid'];
        $domainName = ModuleUtil::extractDomainName($this->params);

        $targetTag = trim($this->postData['new_tag'] ?? '');
        if (empty($targetTag)) {
            return $this->error("Tag-Out", "New tag is required.");
        }

        $api = new DynadotApiClient($this->params['ApiKey'], $this->params['ApiSecretKey']);
        $requestBody = [
            'registrar_tag' => $targetTag,
            'customer_id' => $this->params['userid']
        ];

        $apiResponse = $api->domainSaveTagOut($domainName, $requestBody);
        if (is_array($apiResponse) && isset($apiResponse['error'])) {
            return $this->error($action, $apiResponse['error']);
        }

        ModuleUtil::setWhmcsDomainStatus($domainId, 'Transferred Away');
        logActivity(
            "Domain Tag Out request accepted by Dynadot. Status set to Transferred Out. Domain ID: " . $domainId,
            $this->params['userid']
        );

        return $this->response($action, $apiResponse);
    }

    public function handleRequestFromAdminClientDomainsTabFieldsSave(): array{
        $action = "DOMAIN_SET_REGISTRAR_CONTACT_EXTENSION";

        $api = new DynadotApiClient($this->params['ApiKey'], $this->params['ApiSecretKey']);
        $apiResponse = $api->domainSetRegistrarContactExtension($this->params['domain'], $this->params['contact_extension']);
        if (is_array($apiResponse) && isset($apiResponse['error'])) {
            return $this->error($action, $apiResponse['error']);
        }

        return $this->response($action, $apiResponse);
    }
}