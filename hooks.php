<?php

use WHMCS\Module\Registrar\dynadot\controllers\DomainController;
use WHMCS\Module\Registrar\dynadot\controllers\ContactController;
use WHMCS\Module\Registrar\dynadot\DynadotApiClient;
use WHMCS\Module\Registrar\dynadot\utils\ContactUtil;
use WHMCS\Module\Registrar\dynadot\utils\DynadotUtil;
use WHMCS\Module\Registrar\dynadot\utils\ModuleUtil;

/**
 * Admin Area - Dynadot Plugin Status and Configuration Check
 */
add_hook("AdminHomepage", 1, function ($vars) {
    $output = DynadotUtil::retrieveLatestModuleVersion();
    $output .= DynadotUtil::renderApiSecretKeyWarning();
    return $output;
});

/**
 * Admin Area - Dynadot customized error template
 */
add_hook('AdminAreaHeaderOutput', 1, function ($vars) {
    if (($vars['filename'] ?? '') === 'clientsdomains' && isset($_GET['error_description'])) {
        return DynadotUtil::renderDynadotErrorBox('Dynadot Error', base64_decode($_GET['error_description']));
    }
    return '';
});

/**
 * Admin Area - Domain Name Special TLD Extension Change Synchronization
 */
add_hook('AdminClientDomainsTabFieldsSave', 1, function ($vars) {
    if ($vars['registrar'] !== 'dynadot') {
        return '';
    }

    $params['ApiKey'] = ModuleUtil::fetchApiKey();
    $params['ApiSecretKey'] = ModuleUtil::fetchApiSecretKey();
    $params['domain'] = $vars['domain'];
    $params['contact_extension'] = ModuleUtil::generateContactExtensionFromHookVars($vars['domain'], $vars);
    $controller = new DomainController($params, $_GET, $_POST);
    $result = $controller->handleRequestFromAdminClientDomainsTabFieldsSave();
    if (isset($result['response']->error)) {
        $errorMsg = base64_encode($result['response']->error->description);
        header("Location: clientsdomains.php?userid=" . $vars['userid'] . "&id=" . $vars['id'] . "&error_description=" . $errorMsg);
        exit;
    }

    return '';
});

/**
 * Admin Area - Admin Domain - TLD Pricing Page .AI Domain Registration Age Reminder
 */
add_hook('AdminAreaFooterOutput', 1, function ($vars) {
    if (isset($vars['filename']) && $vars['filename'] === 'configdomains') {
        return <<<HTML
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    const showAiTldRemind = () => {
        const aiInput = document.querySelector("input[data-tld='.ai']");
        if (!aiInput) return;

        const aiTldRow = aiInput.closest("tr");
        if (!aiTldRow) return;

        const registrarSelect = aiTldRow.querySelector("select[id='registrarsDropDown']");
        if (!registrarSelect) return;

        const selectedText = registrarSelect.options[registrarSelect.selectedIndex]?.text.trim();
        
        let remindRow = document.getElementById("dynadot-ai-remind");

        if (selectedText === 'Dynadot') {
            if (remindRow) return;

            const bannerHtml = `
                <tr id="dynadot-ai-remind">
                    <td colspan="11" style="padding: 6px 15px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 4px; font-weight: bold;">
                        <i class="fas fa-exclamation-triangle" style="margin-right: 8px;"></i>
                        Reminder: .AI domain registrations and renewals require a minimum of 2 years. Orders with less than 2 years will not be accepted.
                    </td>
                </tr>`;
            
            aiTldRow.insertAdjacentHTML('afterend', bannerHtml);
        } else {
            if (remindRow) {
                remindRow.remove();
            }
        }
    };

    showAiTldRemind();

    const aiInput = document.querySelector("input[data-tld='.ai']");
    const registrarSelect = aiInput?.closest("tr")?.querySelector("select[id='registrarsDropDown']");
    if (registrarSelect) {
        registrarSelect.addEventListener('change', showAiTldRemind);
    }
});
</script>
HTML;
    }

    return '';
});

/**
 * Client Area - Domain addon service redirection logic
 */
add_hook('ClientAreaFooterOutput', 1, function ($vars) {
    if ($vars['templatefile'] === 'clientareadomaindetails' && isset($_REQUEST['id'])) {
        $domainId = (int)$_REQUEST['id'];

        $domain = ModuleUtil::fetchWhmcsDomain($domainId);
        if (!$domain || $domain->registrar !== 'dynadot') {
            return '';
        }

        return <<<HTML
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dnsLinks = document.querySelectorAll("#tabAddons a[href*='action=domaindns']");
    const emailLinks = document.querySelectorAll("#tabAddons a[href*='action=domainemailforwarding']");

    dnsLinks.forEach(function(link) {
        link.setAttribute('data-dynadot-href', 'dynadot-dns-management');
        link.removeAttribute('href');
        
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const sidebarDns = document.querySelector(".sidebar .panel-actions a[menuitemname='DNS Management']");
            if (sidebarDns) {
                sidebarDns.click();
            }
        });
    });

    emailLinks.forEach(function(link) {
        link.setAttribute('data-dynadot-href', 'dynadot-email-forwarding');
        link.removeAttribute('href');
        
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const sidebarEmail = document.querySelector(".sidebar .panel-actions a[menuitemname='Email Forwarding']");
            if (sidebarEmail) {
                sidebarEmail.click();
            }
        });
    });
});
</script>
HTML;
    }
    return '';
});

/**
 * Client Area - Contact Details Validation
 */
add_hook('ContactDetailsValidation', 1, function ($vars) {
    $contactTemplate = ContactUtil::generateDynadotContact(
        $_REQUEST['domaincontactcompanyname'] ?? $_REQUEST['companyname'] ?? '',
        trim(($_REQUEST['domaincontactfirstname'] ?? $_REQUEST['firstname'] ?? '') . ' ' . ($_REQUEST['domaincontactlastname'] ?? $_REQUEST['lastname'] ?? '')),
        $_REQUEST['domaincontactemail'] ?? $_REQUEST['email'] ?? '',
        $_REQUEST['domaincontactphonenumber'] ?? $_REQUEST['phonenumber'] ?? '',
        $_REQUEST['country-calling-code-domaincontactphonenumber'] ?? $_REQUEST['country-calling-code-phonenumber'] ?? '',
        $_REQUEST['domaincontactaddress1'] ?? $_REQUEST['address1'] ?? '',
        $_REQUEST['domaincontactaddress2'] ?? $_REQUEST['address2'] ?? '',
        $_REQUEST['domaincontactcity'] ?? $_REQUEST['city'] ?? '',
        $_REQUEST['domaincontactstate'] ?? $_REQUEST['state'] ?? '',
        $_REQUEST['domaincontactpostcode'] ?? $_REQUEST['postcode'] ?? '',
        $_REQUEST['domaincontactcountry'] ?? $_REQUEST['country'] ?? ''
    );

    $params['contact'] = $contactTemplate;
    $params['isRegistration'] = ($_REQUEST['rp'] ?? '') !== '/account/contacts/save' && ($_REQUEST['rp'] ?? '') !== '/account/contacts/new';
    $controller = new ContactController($params, $_GET, $_POST);
    $result = $controller->handleRequestFromContactDetailsValidation();

    if (isset($result['response']->error)) {
        return [$result['response']->error->description];
    }

    return [];
});

/**
 * Client Area - Contact Add
 */
add_hook('ContactAdd', 1, function ($vars) {
    $whmcsClientId = (int)$vars['userid'];
    $whmcsContactId = (int)$vars['contactid'];

    $contact = ContactUtil::generateDynadotContact(
        $_REQUEST['companyname'] ?? '',
        trim(($_REQUEST['firstname'] ?? '') . ' ' . ($_REQUEST['lastname'] ?? '')),
        $_REQUEST['email'] ?? '',
        $_REQUEST['phonenumber'] ?? '',
        $_REQUEST['country-calling-code-phonenumber'] ?? '',
        $_REQUEST['address1'] ?? '',
        $_REQUEST['address2'] ?? '',
        $_REQUEST['city'] ?? '',
        $_REQUEST['state'] ?? '',
        $_REQUEST['postcode'] ?? '',
        $_REQUEST['country'] ?? '',
    );

    $api = new DynadotApiClient(ModuleUtil::fetchApiKey(), ModuleUtil::fetchApiSecretKey());
    $result = $api->contactCreate($contact, $whmcsClientId, $whmcsContactId);
    if (is_array($result) && isset($result['error'])) {
        ModuleUtil::dynadotModuleLog('hook_exception', 'ContactAdd', $result['error']);
    }
});

/**
 * Client Area - Contact Edit
 */
add_hook('ContactEdit', 1, function ($vars) {
    $whmcsClientId = (int)$vars['userid'];
    $whmcsContactId = (int)$vars['contactid'];

    $contactTemplate = ContactUtil::generateDynadotContact(
        $_REQUEST['companyname'] ?? '',
        trim(($_REQUEST['firstname'] ?? '') . ' ' . ($_REQUEST['lastname'] ?? '')),
        $_REQUEST['email'] ?? '',
        $_REQUEST['phonenumber'] ?? '',
        $_REQUEST['country-calling-code-phonenumber'] ?? '',
        $_REQUEST['address1'] ?? '',
        $_REQUEST['address2'] ?? '',
        $_REQUEST['city'] ?? '',
        $_REQUEST['state'] ?? '',
        $_REQUEST['postcode'] ?? '',
        $_REQUEST['country'] ?? ''
    );

    $api = new DynadotApiClient(ModuleUtil::fetchApiKey(), ModuleUtil::fetchApiSecretKey());
    $result = $api->contactUpdate($whmcsClientId, $whmcsContactId, $contactTemplate);
    if (is_array($result) && isset($result['error'])) {
        ModuleUtil::dynadotModuleLog('hook_exception', 'ContactEdit', $result['error']);
    }
});

/**
 * Client Area - Contact Delete
 */
add_hook('ContactDelete', 1, function ($vars) {
    $whmcsClientId = (int)$vars['userid'];
    $whmcsContactId = (int)$vars['contactid'];

    $api = new DynadotApiClient(ModuleUtil::fetchApiKey(), ModuleUtil::fetchApiSecretKey());
    $result = $api->contactDelete($whmcsClientId, $whmcsContactId);
    if (is_array($result) && isset($result['error'])) {
        ModuleUtil::dynadotModuleLog('hook_exception', 'ContactDelete', $result['error']);
    }
});

/**
 * Client Area - Client(default contact) Details Validation
 */
add_hook('ClientDetailsValidation', 1, function ($vars) {
    $contact = ContactUtil::generateDynadotContact(
        $_REQUEST['companyname'] ?? '',
        trim($_REQUEST['firstname'] . ' ' . $_REQUEST['lastname']),
        $_REQUEST['email'] ?? '',
        $_REQUEST['phonenumber'] ?? '',
        $_REQUEST['country-calling-code-phonenumber'] ?? '',
        $_REQUEST['address1'] ?? '',
        $_REQUEST['address2'] ?? '',
        $_REQUEST['city'] ?? '',
        $_REQUEST['state'] ?? '',
        $_REQUEST['postcode'] ?? '',
        $_REQUEST['country'] ?? ''
    );

    $params['contact'] = $contact;
    $params['isRegistration'] = false;
    $controller = new ContactController($params, $_GET, $_POST);
    $result = $controller->handleRequestFromContactDetailsValidation();

    if (isset($result['response']->error)) {
        return [$result['response']->error->description];
    }

    return [];
});

/**
 * Client Area - Client(default contact) Edit
 */
add_hook('ClientEdit', 1, function ($vars) {
    $whmcsClientId = (int)$vars['userid'];

    $contact = ContactUtil::generateDynadotContact(
        $_REQUEST['companyname'] ?? '',
        trim(($_REQUEST['firstname'] ?? '') . ' ' . ($_REQUEST['lastname'] ?? '')),
        $_REQUEST['email'] ?? '',
        $_REQUEST['phonenumber'] ?? '',
        $_REQUEST['country-calling-code-phonenumber'] ?? '',
        $_REQUEST['address1'] ?? '',
        $_REQUEST['address2'] ?? '',
        $_REQUEST['city'] ?? '',
        $_REQUEST['state'] ?? '',
        $_REQUEST['postcode'] ?? '',
        $_REQUEST['country'] ?? ''
    );

    $api = new DynadotApiClient(ModuleUtil::fetchApiKey(), ModuleUtil::fetchApiSecretKey());
    $result = $api->contactUpdate($whmcsClientId, -1, $contact);
    if (is_array($result) && isset($result['error'])) {
        ModuleUtil::dynadotModuleLog('hook_exception', 'ClientEdit', $result['error']);
    }
});