<div class="gdg-tagout-settings">
    <div class="row" style="padding: 24px 0">
        <div class="col-md-4">
            <h5 class="font-weight-bold">Tag out a .UK Domain</h5>
            <p class="text-muted small mt-3" style="text-align: left">
                To transfer a .UK domain to another registrar, you must change its IPS tag to the new registrar's tag.
                Please note:
            </p>
            <ul class="text-muted small pl-3 mb-4">
                <li>This action is immediate and <strong>irreversible</strong>.</li>
                <li>The domain will be removed from your account.</li>
                <li>Ensure you have the correct IPS tag from your new registrar.</li>
            </ul>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-body p-4">

                    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                        <h4 class="m-0 font-weight-bold text-dark">{$domainName}</h4>
                        <a href="clientarea.php?action=domaindetails&id={$domainid}"
                           class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Details
                        </a>
                    </div>

                    {if $code >= 200 && $code < 300 && $code != 202}
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> Tag-Out Order Accepted!
                        </div>
                    {/if}
                    {if $code === 400 && isset($error_description)}
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <strong>Error:</strong> {$error_description}
                        </div>
                    {/if}

                    <form method="post"
                          action="clientarea.php?action=domaindetails&id={$domainid}&modop=custom&a=TagOut"
                          class="gdg-form">
                        <input type="hidden" name="tagout_action" value="do_release">

                        <div class="form-group mb-4">
                            <label for="new_tag" class="text-muted small mb-2">NEW REGISTRAR'S IPS TAG</label>
                            <input type="text" name="new_tag" id="new_tag"
                                   class="form-control form-control-lg text-uppercase" placeholder="e.g., GLOBALDOMAIN"
                                   required>
                        </div>

                        <div class="alert alert-warning small" style="border-left: 4px solid #ffc107;">
                            <i class="fas fa-exclamation-triangle"></i> By clicking "Release", you acknowledge that this
                            domain will be transferred out of your account immediately.
                        </div>

                        <button type="submit" class="btn btn-danger btn-lg btn-block gdg-submit-btn mt-4"
                                style="border: none; padding: 15px; border-radius: 8px; font-weight: 600;">
                            <span class="btn-text">Release</span>
                            <span class="btn-loading" style="display: none;">
                                <i class="fas fa-spinner fa-spin"></i> Processing...
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelector('.gdg-form')?.addEventListener('submit', function (e) {
        const submitBtn = this.querySelector('.gdg-submit-btn');
        submitBtn.querySelector('.btn-text').style.display = 'none';
        submitBtn.querySelector('.btn-loading').style.display = 'inline';
        submitBtn.disabled = true;
    });
</script>