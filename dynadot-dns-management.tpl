{if $code >= 200 && $code < 300}
    {if $action === "SET_DNS_RECORDS"}
        <div class="alert alert-success text-center">
            <i class="fas fa-check-circle"></i> DNS Records saved successfully!
        </div>
    {/if}
{else}
    <div class="alert alert-danger text-center">
        <i class="fas fa-exclamation-triangle"></i> {$error_description|default:"An error occurred while processing your request."}
    </div>
{/if}

<div class="gdg-note-box">
    <i class="fas fa-info-circle"></i>
    <div>
        Configure A, CNAME, CAA, ANAME or URL redirection records to route traffic to your web servers, third-party
        hosting, or
        specific URLs.
        <div class="sub-text">
            Due to global DNS propagation and TTL caching across different internet service providers, changes may take
            from a few minutes up to several hours to take effect worldwide.
        </div>
    </div>
</div>

<form id="gdgDnsForm" method="post"
      action="clientarea.php?action=domaindetails&domainid={$domainid}&modop=custom&a=DnsManagement">
    <input type="hidden" name="page-event" value="saveDnsRecord">

    <div class="card gdg-card">
        <div class="card-body">
            <h3 class="card-title">
                Domain Record <span style="font-size: 12px; color: #d93025; font-weight: normal; margin-left: 6px;">(Required)</span>
            </h3>

            <div class="table-responsive">
                <table class="table table-gdg">
                    <thead>
                    <tr>
                        <th>Record Type</th>
                        <th>IP Address or Target Host / Value</th>
                        <th>Extend</th>
                        <th style="text-align: right;">Operation</th>
                    </tr>
                    </thead>
                    <tbody id="domainRecordBody">
                    {foreach item=mainRecord from=$data->name_server_settings->main_domains}
                        <tr>
                            <td>
                                <select name="dnsRecordType[]" class="form-control form-control-gdg"
                                        onchange="changeInput(this,false)">
                                    <option value="a"
                                            {if $mainRecord->record_type|lower == "a"}selected="selected"{/if}>A
                                    </option>
                                    <option value="aaaa"
                                            {if $mainRecord->record_type|lower == "aaaa"}selected="selected"{/if}>AAAA
                                    </option>
                                    <option value="aname"
                                            {if $mainRecord->record_type|lower == "aname"}selected="selected"{/if}>ANAME
                                    </option>
                                    <option value="caa"
                                            {if $mainRecord->record_type|lower == "caa"}selected="selected"{/if}>CAA
                                    </option>
                                    <option value="cname"
                                            {if $mainRecord->record_type|lower == "cname"}selected="selected"{/if}>CNAME
                                    </option>
                                    <option value="email"
                                            {if $mainRecord->record_type|lower == "email"}selected="selected"{/if}>Email Forward
                                    </option>
                                    <option value="forward"
                                            {if $mainRecord->record_type|lower == "forward"}selected="selected"{/if}>
                                        Forward
                                    </option>
                                    <option value="txt"
                                            {if $mainRecord->record_type|lower == "txt"}selected="selected"{/if}>TXT
                                    </option>
                                    <option value="mx"
                                            {if $mainRecord->record_type|lower == "mx"}selected="selected"{/if}>MX
                                    </option>
                                    <option value="stealth"
                                            {if $mainRecord->record_type|lower == "stealth" || $mainRecord->record_type|lower == "stealth forward"}selected="selected"{/if}>
                                        Stealth Forward
                                    </option>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="dnsRecordAddress[]" value="{$mainRecord->value|escape:'html'}" size="40"
                                       placeholder="{if $mainRecord->record_type|lower == 'caa'}0 issue &quot;ca.example.com&quot;{elseif $mainRecord->record_type|lower == 'txt'}TXT{elseif $mainRecord->record_type|lower == 'email'}user@example.com{else}e.g. 192.0.2.1 or example.com{/if}"
                                       class="form-control form-control-gdg">
                            </td>
                            <td>
                                {if $mainRecord->record_type|lower == "forward"}
                                    <select name="extend[]" class="form-control form-control-gdg">
                                        <option value="1" {if $mainRecord->value2 == "1"}selected="selected"{/if}>301
                                        </option>
                                        <option value="2" {if $mainRecord->value2 == "2"}selected="selected"{/if}>302
                                        </option>
                                    </select>
                                {elseif $mainRecord->record_type|lower == "mx"}
                                    <input type="text" name="extend[]" class="form-control form-control-gdg"
                                           placeholder="Priority (0-65535)" value="{$mainRecord->value2}">
                                {elseif $mainRecord->record_type|lower == "stealth" || $mainRecord->record_type|lower == "stealth forward"}
                                    <input type="text" name="extend[]" class="form-control form-control-gdg"
                                           placeholder="Page Title" value="{$mainRecord->value2}">
                                {elseif $mainRecord->record_type|lower == "email"}
                                    <input type="text" name="extend[]" class="form-control form-control-gdg"
                                           placeholder="Alias" value="{$mainRecord->value2}">
                                {else}
                                    <input type="text" value="N/A" class="form-control form-control-gdg" disabled>
                                    <input type="hidden" name="extend[]" value="N/A">
                                {/if}
                            </td>
                            <td style="text-align: right;">
                                <button type="button" class="gdg-btn-delete" onclick="deleteRecord(this)">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </button>
                            </td>
                        </tr>
                    {/foreach}

                    <tr style="display: none;" id="domainRecordHtml">
                        <td>
                            <select name="dnsRecordType[]" class="form-control form-control-gdg"
                                    onchange="changeInput(this,false)">
                                <option value="a">A</option>
                                <option value="aaaa">AAAA</option>
                                <option value="aname">ANAME</option>
                                <option value="caa">CAA</option>
                                <option value="cname">CNAME</option>
                                <option value="email">Email Forward</option>
                                <option value="forward">Forward</option>
                                <option value="txt">TXT</option>
                                <option value="mx">MX</option>
                                <option value="stealth">Stealth Forward</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" name="dnsRecordAddress[]" value="" size="40"
                                   placeholder="e.g. 47.230.1.8" class="form-control form-control-gdg">
                        </td>
                        <td>
                            <input type="text" value="N/A" class="form-control form-control-gdg" disabled>
                            <input type="hidden" name="extend[]" value="N/A">
                        </td>
                        <td style="text-align: right;">
                            <button type="button" class="gdg-btn-delete" onclick="deleteRecord(this)">
                                <i class="fas fa-trash-alt"></i> Delete
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div style="display: flex;justify-content: flex-end;margin-top: 16px;">
                <button type="button" class="btn btn-primary btn-sm gdg-add-btn" onclick="addRecord('domainRecord')">
                    <i class="fas fa-plus" style="font-size: 12px; margin-right: 4px;"></i> Add Record
                </button>
            </div>
        </div>
    </div>

    <div class="card gdg-card">
        <div class="card-body">
            <h3 class="card-title">
                Subdomain Records <span style="font-size: 12px; color: #70757a; font-weight: normal; margin-left: 6px;">(Optional)</span>
            </h3>

            <div class="table-responsive">
                <table class="table table-gdg">
                    <thead>
                    <tr>
                        <th>Subdomain</th>
                        <th>Record Type</th>
                        <th>IP Address or Target Host / Value</th>
                        <th>Extend</th>
                        <th style="text-align: right;">Operation</th>
                    </tr>
                    </thead>
                    <tbody id="subDomainRecordBody">
                    {foreach item=subRecord from=$data->name_server_settings->sub_domains}
                        <tr>
                            <td>
                                <input type="text" name="subDnsName[]" value="{$subRecord->sub_host}" size="10"
                                       placeholder="e.g. www" class="form-control form-control-gdg">
                            </td>
                            <td>
                                <select name="subDnsRecordType[]" class="form-control form-control-gdg"
                                        onchange="changeInput(this,true)">
                                    <option value="a" {if $subRecord->record_type|lower == "a"}selected="selected"{/if}>
                                        A
                                    </option>
                                    <option value="aaaa"
                                            {if $subRecord->record_type|lower == "aaaa"}selected="selected"{/if}>AAAA
                                    </option>
                                    <option value="caa"
                                            {if $subRecord->record_type|lower == "caa"}selected="selected"{/if}>CAA
                                    </option>
                                    <option value="cname"
                                            {if $subRecord->record_type|lower == "cname"}selected="selected"{/if}>CNAME
                                    </option>
                                    <option value="email"
                                            {if $subRecord->record_type|lower == "email"}selected="selected"{/if}>Email Forward
                                    </option>
                                    <option value="forward"
                                            {if $subRecord->record_type|lower == "forward"}selected="selected"{/if}>
                                        Forward
                                    </option>
                                    <option value="ns"
                                            {if $subRecord->record_type|lower == "ns"}selected="selected"{/if}>NS
                                    </option>
                                    <option value="txt"
                                            {if $subRecord->record_type|lower == "txt"}selected="selected"{/if}>TXT
                                    </option>
                                    <option value="mx"
                                            {if $subRecord->record_type|lower == "mx"}selected="selected"{/if}>MX
                                    </option>
                                    <option value="srv"
                                            {if $subRecord->record_type|lower == "srv"}selected="selected"{/if}>SRV
                                    </option>
                                    <option value="stealth"
                                            {if $subRecord->record_type|lower == "stealth" || $subRecord->record_type|lower == "stealth forward"}selected="selected"{/if}>
                                        Stealth Forward
                                    </option>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="subDnsRecordAddress[]" value="{$subRecord->value|escape:'html'}" size="40"
                                       placeholder="{if $subRecord->record_type|lower == 'caa'}0 issue &quot;ca.example.com&quot;{elseif $subRecord->record_type|lower == 'txt'}TXT{elseif $subRecord->record_type|lower == 'srv'}10 60 5060 sip.example.com{elseif $subRecord->record_type|lower == 'email'}user@example.com{else}e.g. 47.254.84.245{/if}"
                                       class="form-control form-control-gdg">
                            </td>
                            <td>
                                {if $subRecord->record_type|lower == "forward"}
                                    <select name="subExtend[]" class="form-control form-control-gdg">
                                        <option value="1" {if $subRecord->value2 == "1"}selected="selected"{/if}>301
                                        </option>
                                        <option value="2" {if $subRecord->value2 == "2"}selected="selected"{/if}>302
                                        </option>
                                    </select>
                                {elseif $subRecord->record_type|lower == "mx"}
                                    <input type="text" name="subExtend[]" class="form-control form-control-gdg"
                                           placeholder="Priority (0-65535)" value="{$subRecord->value2}">
                                {elseif $subRecord->record_type|lower == "stealth" || $subRecord->record_type|lower == "stealth forward"}
                                    <input type="text" name="subExtend[]" class="form-control form-control-gdg"
                                           placeholder="Page Title" value="{$subRecord->value2}">
                                {elseif $subRecord->record_type|lower == "email"}
                                    <input type="text" name="subExtend[]" class="form-control form-control-gdg"
                                           placeholder="Alias" value="{$subRecord->value2}">
                                {else}
                                    <input type="text" value="N/A" class="form-control form-control-gdg" disabled>
                                    <input type="hidden" name="subExtend[]" value="N/A">
                                {/if}
                            </td>
                            <td style="text-align: right;">
                                <button type="button" class="gdg-btn-delete" onclick="deleteRecord(this)">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </button>
                            </td>
                        </tr>
                    {/foreach}

                    <tr style="display: none;" id="subDomainRecordHtml">
                        <td>
                            <input type="text" name="subDnsName[]" value="" size="10"
                                   placeholder="e.g. www" class="form-control form-control-gdg">
                        </td>
                        <td>
                            <select name="subDnsRecordType[]" class="form-control form-control-gdg"
                                    onchange="changeInput(this,true)">
                                <option value="a">A</option>
                                <option value="aaaa">AAAA</option>
                                <option value="caa">CAA</option>
                                <option value="cname">CNAME</option>
                                <option value="email">Email Forward</option>
                                <option value="forward">Forward</option>
                                <option value="ns">NS</option>
                                <option value="txt">TXT</option>
                                <option value="mx">MX</option>
                                <option value="srv">SRV</option>
                                <option value="stealth">Stealth Forward</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" name="subDnsRecordAddress[]" value="" size="40"
                                   placeholder="e.g. 47.254.84.245" class="form-control form-control-gdg">
                        </td>
                        <td>
                            <input type="text" value="N/A" class="form-control form-control-gdg" disabled>
                            <input type="hidden" name="subExtend[]" value="N/A">
                        </td>
                        <td style="text-align: right;">
                            <button type="button" class="gdg-btn-delete" onclick="deleteRecord(this)">
                                <i class="fas fa-trash-alt"></i> Delete
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div style="display: flex;justify-content: flex-end;margin-top: 16px; margin-bottom: 8px;">
                <button type="button" class="btn btn-primary btn-sm gdg-add-btn" onclick="addRecord('subDomainRecord')">
                    <i class="fas fa-plus" style="font-size: 12px; margin-right: 4px;"></i> Add Record
                </button>
            </div>
        </div>
    </div>

    <div class="card gdg-card">
        <div class="card-body">
            <h3 class="card-title" style="border-bottom: none; margin-bottom: 0; padding-bottom: 0;">
                Time to Live (TTL) <span
                        style="font-size: 13px; color: #70757a; font-weight: normal; margin-left: 4px;">(optional)</span>
                <i class="fas fa-question-circle"
                   style="font-size: 14px; color: #9aa0a6; margin-left: 4px; cursor: pointer;"
                   data-toggle="tooltip"
                   data-placement="top"
                   title="Specify the TTL for all of your DNS records for {$domain|default:'your domain'}. Changes will go into effect immediately if no TTL is set."></i>
            </h3>

            <div style="max-width: 320px; margin-top: 12px;">
                <select name="glue_dns_ttl" size="1" class="form-control form-control-gdg" id="selectTime">
                    <option value="300" {if $data->name_server_settings->ttl == "300"}selected="selected"{/if}>5
                        minutes
                    </option>
                    <option value="600" {if $data->name_server_settings->ttl == "600"}selected="selected"{/if}>10
                        minutes
                    </option>
                    <option value="1800" {if $data->name_server_settings->ttl == "1800"}selected="selected"{/if}>30
                        minutes
                    </option>
                    <option value="3600" {if $data->name_server_settings->ttl == "3600"}selected="selected"{/if}>1
                        hour
                    </option>
                    <option value="7200" {if $data->name_server_settings->ttl == "7200"}selected="selected"{/if}>2
                        hours
                    </option>
                    <option value="14400" {if $data->name_server_settings->ttl == "14400"}selected="selected"{/if}>4
                        hours
                    </option>
                    <option value="28800" {if $data->name_server_settings->ttl == "28800"}selected="selected"{/if}>8
                        hours
                    </option>
                </select>
            </div>

            <div style="margin-top: 8px; color: #70757a; font-size: 13px;">
                Note: Domain propagation can take anywhere from 5 mins to 48 hours.
            </div>
        </div>
    </div>

    <div style="text-align: center; margin-top: 32px; margin-bottom: 24px;">
        <button type="submit" class="btn gdg-submit-btn" form="gdgDnsForm">
            Save Changes
        </button>
    </div>
</form>

<div style="display: none;">
    <div id="forward">
        <select name="extend[]" class="form-control form-control-gdg">
            <option value="1">301</option>
            <option value="2">302</option>
        </select>
    </div>
    <div id="mx">
        <input type="text" name="extend[]" value="" size="10" class="form-control form-control-gdg"
               placeholder="Priority (0-65535)">
    </div>
    <div id="stealth">
        <input type="text" name="extend[]" value="" size="10" class="form-control form-control-gdg"
               placeholder="Page Title">
    </div>
    <div id="email">
        <input type="text" name="extend[]" value="" size="10" class="form-control form-control-gdg"
               placeholder="Alias">
    </div>
    <div id="n_a">
        <input type="text" value="N/A" class="form-control form-control-gdg" disabled>
        <input type="hidden" name="extend[]" value="N/A">
    </div>
</div>

<script>
    function addRecord(selectorName) {
        const selector = "#" + selectorName + "Html";
        $(selector).clone(true).insertBefore(selector).removeAttr("style").removeAttr("id");
    }

    function deleteRecord(e) {
        const tr = $(e).closest("tr");
        tr.remove();
    }

    function changeInput(e, isSubdomain) {
        const tr = $(e).closest("tr");
        const last = tr.children().last();

        const addressInput = tr.find('input[name="dnsRecordAddress[]"], input[name="subDnsRecordAddress[]"]');

        tr.children().eq(-2).remove();
        const td = $("<td></td>");
        const val = $(e).val();
        let html;

        if (val === "forward") {
            html = $("#forward").html();
            addressInput.attr("placeholder", "https://www.myhost.com");
        } else if (val === "mx") {
            html = $("#mx").html();
            addressInput.attr("placeholder", "smtp.myhost.com");
        } else if (val === "stealth") {
            html = $("#stealth").html();
            addressInput.attr("placeholder", "https://www.myhost.com");
        } else if (val === "email") {
            html = $("#email").html();
            addressInput.attr("placeholder", "user@example.com");
        } else {
            html = $("#n_a").html();

            if (val === "caa") {
                addressInput.attr("placeholder", '0 issue "ca.example.com"');
            } else if (val === "txt") {
                addressInput.attr("placeholder", "TXT");
            } else if (val === "srv") {
                addressInput.attr("placeholder", "10 60 5060 sip.example.com");
            } else if (val === "aaaa") {
                addressInput.attr("placeholder", "2001:db8::1");
            } else if (val === "cname" || val === "aname" || val === "ns") {
                addressInput.attr("placeholder", "ftp.myhost.com");
            } else {
                addressInput.attr("placeholder", isSubdomain ? "47.254.84.245" : "47.230.1.8");
            }
        }

        if (isSubdomain) {
            html = html.replaceAll('name="extend[]"', 'name="subExtend[]"');
        }
        td.append(html);
        last.before(td);
    }

    $(document).ready(function () {
        $('[data-toggle="tooltip"]').tooltip();

        if ($('.alert-success').length > 0) {
            setTimeout(function () {
                $('.alert-success').slideUp(500, function () {
                    $(this).remove();
                });
            }, 3000);
        }
    });
</script>

<style>
    .gdg-note-box {
        background-color: #f0f7ff;
        border-left: 4px solid #1a73e8;
        color: #3c4043;
        padding: 16px;
        border-radius: 0 4px 4px 0;
        margin-bottom: 24px;
        font-size: 14px;
        line-height: 1.5;
        display: flex;
        align-items: flex-start;
    }

    .gdg-note-box i {
        color: #1a73e8;
        font-size: 16px;
        margin-right: 12px;
        margin-top: 2px;
    }

    .gdg-note-box .sub-text {
        margin-top: 6px;
        color: #5f6368;
        font-size: 13px;
    }

    .gdg-card {
        border: 1px solid #dadce0 !important;
        border-radius: 8px !important;
        box-shadow: 0 1px 2px 0 rgba(60, 64, 67, 0.1) !important;
        margin-bottom: 24px;
        overflow: hidden;
    }

    .gdg-card .card-body {
        padding: 24px;
    }

    .gdg-card .card-title {
        font-size: 18px;
        font-weight: 600;
        color: #202124;
        margin-bottom: 16px;
        border-bottom: 1px solid #f1f3f4;
        padding-bottom: 12px;
    }

    .table-gdg {
        margin-bottom: 0 !important;
    }

    .table-gdg thead tr {
        color: #5f6368;
        font-size: 13px;
        border-bottom: 2px solid #e8eaed;
    }

    .table-gdg th {
        border-top: none !important;
        font-weight: 600 !important;
        padding: 12px 8px !important;
    }

    .table-gdg tbody tr {
        border-bottom: 1px solid #f1f3f4;
    }

    .table-gdg td {
        padding: 12px 8px !important;
        vertical-align: middle !important;
        border-top: none !important;
    }

    .form-control-gdg {
        border-radius: 4px !important;
        border-color: #dadce0 !important;
        height: auto !important;
        padding: 6px 12px !important;
    }

    .form-control-gdg:disabled {
        background-color: #f8f9fa !important;
        border-color: #dadce0 !important;
        color: #9aa0a6 !important;
    }

    .gdg-add-btn {
        border: 1px solid #dadce0 !important;
        font-weight: 500 !important;
        padding: 6px 16px !important;
        border-radius: 4px !important;
    }

    .gdg-btn-delete {
        color: #d93025 !important;
        padding: 0 !important;
        text-decoration: none !important;
        font-weight: 500 !important;
        background: none;
        border: none;
    }

    .gdg-btn-delete:hover {
        text-decoration: underline !important;
    }

    .gdg-submit-btn {
        background-color: #0c1a30 !important;
        color: #ffffff !important;
        font-weight: 600 !important;
        padding: 12px 36px !important;
        font-size: 15px !important;
        border-radius: 4px !important;
        border: none !important;
        box-shadow: 0 4px 6px rgba(12, 26, 48, 0.2) !important;
        transition: all 0.2s ease;
    }

    .gdg-submit-btn:hover {
        background-color: #162a4a !important;
        box-shadow: 0 6px 12px rgba(12, 26, 48, 0.3) !important;
        color: #ffffff !important;
    }

    .alert-success {
        background-color: #e6f4ea;
        color: #137333;
        border: 1px solid #ceead6;
        border-radius: 4px;
        padding: 12px;
        margin-bottom: 20px;
    }

    .alert-danger {
        background-color: #fce8e6;
        color: #c5221f;
        border: 1px solid #fad2cf;
        border-radius: 4px;
        padding: 12px;
        margin-bottom: 20px;
    }

    .tooltip {
        pointer-events: none !important;
    }

    .tooltip-inner {
        max-width: 350px !important;
        width: max-content !important;
        text-align: left !important;
        padding: 10px 14px !important;
        white-space: normal !important;
    }
</style>