<?php

namespace WHMCS\Module\Registrar\dynadot\controllers;

use WHMCS\Module\Registrar\dynadot\utils\ModuleUtil;

class ContactController extends BaseController
{
    public function handleRequestFromContactDetailsValidation(): array
    {
        $contactData = $this->params['contact'];
        $isRegistration = $this->params['isRegistration'];

        $name = trim($contactData['name'] ?? '');
        if (empty($name) && $isRegistration) {
            return $this->error('CONTACT_VALIDATION', 'You did not enter your name.');
        }
        if (strlen($name) > 255) {
            return $this->error('CONTACT_VALIDATION', 'The name (first name and last name) exceeds the maximum length(255).');
        }

        $phoneNum = trim($contactData['phone_number']);
        if (empty($phoneNum) && $isRegistration) {
            return $this->error('CONTACT_VALIDATION', 'You did not enter your phone number.');
        }
        if (strlen($phoneNum) > 30) {
            return $this->error('CONTACT_VALIDATION', 'The phone number cannot exceed 30 characters.');
        }

        $phoneCc = trim($contactData['phone_cc']);
        if (empty($phoneCc)) {
            return $this->error('CONTACT_VALIDATION', 'You did not select your phone cc.');
        }

        $address1 = trim($contactData['address1']);
        if (empty($address1) && $isRegistration) {
            return $this->error('CONTACT_VALIDATION', 'You did not enter your address1.');
        }

        $address2 = trim($contactData['address2'] ?? '');

        $city = trim($contactData['city']);
        if (empty($city) && $isRegistration) {
            return $this->error('CONTACT_VALIDATION', 'You did not enter your city.');
        }

        $state = trim($contactData['state'] ?? '');
        if (empty($state) && $isRegistration) {
            return $this->error('CONTACT_VALIDATION', 'You did not enter your state.');
        }

        $zip = trim($contactData['zip'] ?? '');
        if (empty($zip) && $isRegistration) {
            return $this->error('CONTACT_VALIDATION', 'You did not enter your postcode.');
        }

        $country = trim($contactData['country']);
        if (empty($country)) {
            return $this->error('CONTACT_VALIDATION', 'You did not select your country.');
        }

        $expectedPhoneCc = (string)ModuleUtil::extractPhoneccByCountryCode($country);
        if ($expectedPhoneCc !== '' && ltrim($expectedPhoneCc, '+') !== ltrim($phoneCc, '+')) {
            return $this->error('CONTACT_VALIDATION', 'The phone cc does not match the country selected.');
        }

        $addressFlatString = $address1 . $address2 . $city . $state . $zip;
        if (strlen($addressFlatString) > 255) {
            return $this->error('CONTACT_VALIDATION', 'The combined length of the address (Address 1, Address 2, City, State/Region, Zip Code) exceeds the 255 byte maximum allowed.');
        }

        return $this->response('CONTACT_VALIDATION', 'success');
    }
}