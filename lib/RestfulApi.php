<?php

namespace WHMCS\Module\Registrar\dynadot;

use RuntimeException;
use WHMCS\Module\Registrar\dynadot\utils\DynadotUtil;
use WHMCS\Module\Registrar\dynadot\utils\ModuleUtil;

class RestfulApi
{
    public const REST_API_VERSION = 'v2';
    // production url :
    public const REST_API_URL = 'https://whmcs-api.dynadot.com/restful/' . self::REST_API_VERSION;

    // test url :
    // public const REST_API_URL = 'http://166.88.19.190:8889/restful/' . self::REST_API_VERSION;

    /** @return mixed */
    public static function sendRequest(string $endpoint, string $method, $data, string $apiKey, string $apiSecret = '')
    {
        $url = self::REST_API_URL . $endpoint;
        $method = strtoupper($method);
        $ch = curl_init();

        if (!empty($data)) {
            array_walk_recursive($data, function (&$item) {
                if (is_string($item)) {
                    $item = html_entity_decode($item, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }
            });
        }

        // Prepare request body
        $requestBody = '';
        if (in_array($method, array('POST', 'PUT'))) {
            $requestBody = json_encode($data, JSON_UNESCAPED_UNICODE);
        }

        // Build fullPathAndQuery & Update URL for GET/DELETE
        $fullPathAndQuery = $endpoint;
        if (!empty($data) && in_array($method, array('GET', 'DELETE'))) {
            $queryString = '?' . http_build_query($data);
            $fullPathAndQuery .= $queryString;
            $url .= $queryString;
        }

        // Get current module version
        $currentVersion = DynadotUtil::retrieveCurrentModuleVersion();
        if (empty($currentVersion)) {
            $currentVersion = "Unknown";
        }

        // Get WHMCS version
        $whmcsVersion = ModuleUtil::fetchWhmcsVersion();

        // Set headers
        $headers = array(
            'Accept: application/json',
            'Authorization: ' . $apiKey,
            'Host: whmcs-api.dynadot.com',
            'User-Agent: WHMCS/' . $whmcsVersion . '; Dynadot Module/' . $currentVersion,
            'Content-Type: application/json',
        );

        // Generate X-Signature only if apiSecret is provided
        if (!empty($apiSecret)) {
            $signature = self::createSignature($apiKey, $apiSecret, '/restful/' . self::REST_API_VERSION . $endpoint, '', $requestBody);
            $headers[] = 'X-Signature: ' . $signature;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 100);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Set method
        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            case 'GET':
            default:
                break;
        }

        $responseData = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new RuntimeException('Connection Error: ' . curl_errno($ch) . ' - ' . curl_error($ch));
        }
        curl_close($ch);

        $processedData = json_decode($responseData);

        // Log request and response details with Authorization Masking
        $safeHeaders = $headers;
        foreach ($safeHeaders as $key => $header) {
            if (stripos($header, 'Authorization:') === 0) {
                $safeHeaders[$key] = 'Authorization: **********';
            }
            if (stripos($header, 'X-Signature:') === 0) {
                $safeHeaders[$key] = 'X-Signature: **********';
            }
        }

        $requestDetails = array(
            'method' => $method,
            'url' => $url,
            'headers' => $safeHeaders,
            'body' => $requestBody
        );

        logModuleCall('dynadot', $endpoint, $requestDetails, $processedData, '', '');

        if ($processedData === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Bad response received from API');
        }

        return $processedData;
    }

    private static function createSignature(
        string $apiKey,
        string $apiSecret,
        string $fullPathAndQuery,
        string $xRequestId,
        string $requestBody = ""
    ): string {
        $stringToSign =
            $apiKey . "\n" .
            $fullPathAndQuery . "\n" .
            ($xRequestId ?: "") . "\n" .
            ($requestBody ?: "");

        $rawHmac = hash_hmac(
            "sha256",
            $stringToSign,
            $apiSecret,
            true
        );
        return base64_encode($rawHmac);
    }
}