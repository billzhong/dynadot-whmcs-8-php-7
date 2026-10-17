{if $code === 200}
    {if $action === "SET_EMAIL_FORWARDING"}
        <div class="alert alert-success text-center">
            <i class="fas fa-check-circle"></i> Changes Saved Successfully!
        </div>
    {/if}
{else}
    <div class="alert alert-danger text-center">
        <i class="fas fa-exclamation-triangle"></i> {$error_description}
    </div>
{/if}

<div class="gdg-note-box">
    <i class="fas fa-info-circle"></i>
    <div>
        Specify email aliases and forward them to already existing email addresses, or configure custom MX records for
        your domain.
        <div class="sub-text">
            Enter <strong>*</strong> as an alias to specify a wildcard (catch-all email forwarding). Due to global DNS
            propagation, changes may take time to take effect.
        </div>
    </div>
</div>

<form id="gdgEmailForm" method="post"
      action="clientarea.php?action=domaindetails&domainid={$domainid}&modop=custom&a=EmailForwarding">
    <input type="hidden" name="submitType" value="saveEmailForwarding">

    <div class="card gdg-card">
        <div class="card-body">
            <h3 class="card-title" style="display: flex; justify-content: space-between; align-items: center;">
                <span>Email Configuration</span>
            </h3>

            <div style="margin-bottom: 24px;">
                <label style="font-weight: 600; color: #3c4043; font-size: 14px; margin-bottom: 8px; display: block;">
                    Forwarding Type
                </label>
                <select name="forwardType" id="forwardTypeSelector" class="form-control form-control-gdg"
                        style="max-width: 300px;" onchange="toggleForwardType()">
                    <option value="donot" {if ($data->type|lower == 'donot' || $data->forward_type|lower == 'donot' || empty($data->type) && empty($data->forward_type))}selected{/if}>Disabled (Do Not Forward)</option>
                    <option value="forward" {if $data->type|lower == 'forward' || $data->forward_type|lower == 'forward'}selected{/if}>Email Forwarding (Alias)</option>
                    <option value="MX" {if $data->type|upper == 'MX' || $data->forward_type|upper == 'MX'}selected{/if}>MX Records</option>
                </select>
            </div>

            <div id="table-container-forward" style="{if $data->type|lower != 'forward' && $data->forward_type|lower != 'forward'}display: none;{/if}">
                <div class="table-responsive">
                    <table class="table table-gdg">
                        <thead>
                        <tr>
                            <th>Alias (Username)</th>
                            <th>Destination Email</th>
                            <th style="text-align: right;">Operation</th>
                        </tr>
                        </thead>
                        <tbody id="forwardRecordBody">
                        {assign var="forwardRecords" value=$data->aliases|default:$data->forward_list}
                        {if isset($forwardRecords)}
                            {foreach item=record from=$forwardRecords}
                                <tr>
                                    <td><input type="text" name="alias[]" value="{$record->username}"
                                               class="form-control form-control-gdg" placeholder="e.g. info"></td>
                                    <td><input type="text" name="email[]"
                                               value="{$record->to_email|default:$record->exist_email}"
                                               class="form-control form-control-gdg" placeholder="e.g. you@gmail.com">
                                    </td>
                                    <td style="text-align: right;">
                                        <button type="button" class="gdg-btn-delete" onclick="deleteRecord(this)">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            {/foreach}
                        {/if}

                        <tr style="display: none" id="forwardRecordHtml">
                            <td><input type="text" name="alias[]" value="" class="form-control form-control-gdg"
                                       placeholder="e.g. info"></td>
                            <td><input type="text" name="email[]" value="" class="form-control form-control-gdg"
                                       placeholder="e.g. you@gmail.com"></td>
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
                    <button type="button" class="btn btn-primary btn-sm gdg-add-btn"
                            onclick="addRecord('forwardRecord')">
                        <i class="fas fa-plus" style="font-size: 12px; margin-right: 4px;"></i> Add Record
                    </button>
                </div>
            </div>

            <div id="table-container-MX" style="{if $data->type|upper != 'MX' && $data->forward_type|upper != 'MX'}display: none;{/if}">
                <div class="table-responsive">
                    <table class="table table-gdg">
                        <thead>
                        <tr>
                            <th>Mail Server (Host)</th>
                            <th>Distance (Priority)</th>
                            <th style="text-align: right;">Operation</th>
                        </tr>
                        </thead>
                        <tbody id="mxRecordBody">
                        {assign var="mxRecords" value=$data->mx_records|default:$data->mx_record_list}
                        {if isset($mxRecords)}
                            {foreach item=record from=$mxRecords}
                                <tr>
                                    <td><input type="text" name="host[]" value="{$record->host}"
                                               class="form-control form-control-gdg"
                                               placeholder="e.g. aspmx.l.google.com"></td>
                                    <td><input type="number" name="distance[]" value="{$record->distance}"
                                               class="form-control form-control-gdg" placeholder="e.g. 1"></td>
                                    <td style="text-align: right;">
                                        <button type="button" class="gdg-btn-delete" onclick="deleteRecord(this)">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            {/foreach}
                        {/if}

                        <tr style="display: none" id="mxRecordHtml">
                            <td><input type="text" name="host[]" value="" class="form-control form-control-gdg"
                                       placeholder="e.g. aspmx.l.google.com"></td>
                            <td><input type="number" name="distance[]" value="" class="form-control form-control-gdg"
                                       placeholder="e.g. 1"></td>
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
                    <button type="button" class="btn btn-primary btn-sm gdg-add-btn" onclick="addRecord('mxRecord')">
                        <i class="fas fa-plus" style="font-size: 12px; margin-right: 4px;"></i> Add Record
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div style="text-align: center; margin-top: 32px; margin-bottom: 24px;">
        <button type="submit" class="btn gdg-submit-btn" form="gdgEmailForm">
            Save Changes
        </button>
    </div>
</form>

<script>
    function toggleForwardType() {
        const type = document.getElementById("forwardTypeSelector").value;
        if (type === "MX") {
            document.getElementById("table-container-forward").style.display = "none";
            document.getElementById("table-container-MX").style.display = "block";
        } else if (type === "donot") {
            document.getElementById("table-container-forward").style.display = "none";
            document.getElementById("table-container-MX").style.display = "none";
        } else {
            document.getElementById("table-container-MX").style.display = "none";
            document.getElementById("table-container-forward").style.display = "block";
        }
    }

    function addRecord(selectorName) {
        const selector = "#" + selectorName + "Html";
        const $skeletonRow = $(selector);

        if ($skeletonRow.length) {
            $skeletonRow.clone(true)
                .insertBefore(selector)
                .removeAttr("id")
                .css("display", "");
        }
    }

    function deleteRecord(element) {
        $(element).closest("tr").remove();
    }

    $(document).ready(function () {
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
        background: #fff !important;
        color: #1a73e8 !important;
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
        cursor: pointer;
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
</style>