<?php

namespace WHMCS\Module\Registrar\dynadot\utils;

class ContactUtil
{
    public static array $IntendedUsageMapFromWhmcsToApi = [
        'Business use for profit' => 'BUSINESS',
        'Non-profit business'     => 'NON_PROFIT',
        'Club'                    => 'NON_PROFIT',
        'Association'             => 'NON_PROFIT',
        'Religious Organization'  => 'NON_PROFIT',
        'Personal Use'            => 'PERSONAL',
        'Educational purposes'    => 'EDUCATION',
        'Government purposes'     => 'GOVERNMENT',
    ];

    public static array $RegistrantTypeMapFromWhmcsToApi = [
        'C11' => 'US_CITIZEN',
        'C12' => 'US_PERMANENT_RESIDENT',
        'C21' => 'US_COMPANY',
        'C31' => 'FOREIGN_COMPANY_WITH_US_BUSINESS',
        'C32' => 'FOREIGN_COMPANY_WITH_US_OFFICE',
    ];

    public static array $IntendedUsageMapFromApiToWhmcs = [
        'BUSINESS'   => 'Business use for profit',
        'NON_PROFIT' => 'Non-profit business',
        'PERSONAL'   => 'Personal Use',
        'EDUCATION'  => 'Educational purposes',
        'GOVERNMENT' => 'Government purposes',
    ];

    public static array $RegistrantTypeMapFromApiToWhmcs = [
        'US_CITIZEN'                       => 'C11',
        'US_PERMANENT_RESIDENT'            => 'C12',
        'US_COMPANY'                       => 'C21',
        'FOREIGN_COMPANY_WITH_US_BUSINESS' => 'C31',
        'FOREIGN_COMPANY_WITH_US_OFFICE'   => 'C32',
    ];

    public static function formatTelWhoisTypeFromWhmcsToApi($telWhoisType, $telWhoisOptOut): string
    {
        if (strcasecmp($telWhoisType, 'Legal Person') === 0) {
            return 'LEGAL_PERSON';
        }

        if (strcasecmp($telWhoisType, 'Natural Person') === 0) {
            if (filter_var($telWhoisOptOut, FILTER_VALIDATE_BOOLEAN)) {
                return 'NATURAL_PERSON_OPT_OUT';
            }

            return 'NATURAL_PERSON_OPT_IN';
        }

        return '';
    }


    public static function formatToWhmcsContact($apiContact): array
    {
        if (!$apiContact) {
            return [];
        }

        $nameParts = explode(' ', ($apiContact->name ?? ''), 2);

        return [
            'First Name' => ($nameParts[0] ?? ''),
            'Last Name' => ($nameParts[1] ?? ''),
            'Company Name' => $apiContact->organization ?? '',
            'Email Address' => ($apiContact->email ?? ''),
            'Address 1' => ($apiContact->address1 ?? ''),
            'Address 2' => ($apiContact->address2 ?? ''),
            'City' => ($apiContact->city ?? ''),
            'State' => ($apiContact->state ?? ''),
            'Postcode' => ($apiContact->zip ?? ''),
            'Country' => ($apiContact->country ?? ''),
            'Phone Country Code' => ($apiContact->phone_cc ?? ''),
            'Phone Number' => ($apiContact->phone_number ?? ''),
            'Fax Country Code' => ($apiContact->fax_cc ?? ''),
            'Fax Number' => ($apiContact->fax_number ?? ''),
        ];
    }

    public static function generateDynadotContact(
        $organization,
        $name,
        $email,
        $phoneNumber,
        $phoneCc,
        $address1,
        $address2,
        $city,
        $state,
        $zip,
        $country,
        $phoneNumContainsPhoneCc = false
    ): array
    {
        $finalCc = (string)$phoneCc;
        $finalPhone = (string)$phoneNumber;

        if ($phoneNumContainsPhoneCc) {
            $phoneData = ModuleUtil::extractPhoneInfoByFullPhonenum($phoneNumber);
            if (empty($phoneData['phonecc']) && !empty($country)){
                $finalCc = ModuleUtil::extractPhoneccByCountryCode($country);
            }

            if (empty($finalCc)) {
                $finalCc = (string)($phoneData['phonecc'] ?? '');
            }
        }

        $finalPhone = preg_replace('/^\+\d+\./', '', $finalPhone);
        $finalPhone = preg_replace('/\D/', '', $finalPhone);

        return [
            'organization' => (string)$organization,
            'name' => (string)$name,
            'email' => (string)$email,
            'phone_number' => $finalPhone,
            'phone_cc'     => $finalCc,
            'fax_number'   => $finalPhone,
            'fax_cc'       => $finalCc,
            'address1' => (string)$address1,
            'address2' => (string)$address2,
            'city' => (string)$city,
            'state' => (string)$state,
            'zip' => (string)$zip,
            'country' => (string)$country
        ];
    }
}