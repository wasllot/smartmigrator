<div class="panel">
    <h3><i class="icon icon-tags"></i> {l s='Import Shopify CSV' mod='shopifyimporter'}</h3>
    
    {if isset($pending_count) && $pending_count > 0}
        <div class="alert alert-info" id="import_status_box">
            <h4>{l s='Import in progress...' mod='shopifyimporter'}</h4>
            <p>{l s='Please do not close this tab.' mod='shopifyimporter'}</p>
            <p>
                <strong>{l s='Processed:' mod='shopifyimporter'}</strong> <span id="processed_count">{$processed_count}</span> / {$total_count}
            </p>
            <div class="progress">
                <div class="progress-bar progress-bar-active" role="progressbar" id="import_progress_bar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;">
                    0%
                </div>
            </div>
            <div id="import_error_log" style="display:none; margin-top:10px;" class="alert alert-danger">
                <strong>{l s='Errors:' mod='shopifyimporter'}</strong>
                <ul id="error_list"></ul>
            </div>
        </div>

        <script type="text/javascript">
            $(document).ready(function() {
                var importUri = "{$uri}&ajax=1&action=process_batch";
                var processedCount = {$processed_count};
                var totalCount = {$total_count};

                function runBatch() {
                    $.ajax({
                        url: importUri,
                        type: 'POST',
                        dataType: 'json',
                        success: function(response) {
                            if (response.errors && response.errors.length > 0) {
                                $('#import_error_log').show();
                                $.each(response.errors, function(index, error) {
                                    $('#error_list').append('<li>' + error + '</li>');
                                });
                            }

                            if (response.done) {
                                $('#import_progress_bar').css('width', '100%').text('100%');
                                $('#import_status_box').removeClass('alert-info').addClass('alert-success');
                                $('#import_status_box h4').text("{l s='Import Complete!' mod='shopifyimporter'}");
                            } else {
                                var percent = response.percent + '%';
                                $('#import_progress_bar').css('width', percent).text(percent);
                                
                                processedCount += response.processed;
                                $('#processed_count').text(processedCount);

                                // Next Batch
                                runBatch();
                            }
                        },
                        error: function() {
                            $('#error_list').append('<li>Ajax Error - Retrying...</li>');
                            // Retry after delay
                            setTimeout(runBatch, 3000);
                        }
                    });
                }

                // Start
                runBatch();
            });
        </script>
    {else}
        <p>
            {l s='Upload your Shopify exported CSV file here. The module will process products, combinations, and images.' mod='shopifyimporter'}
        </p>
        <div class="alert alert-info">
            {l s='Please ensure your CSV follows the standard Shopify export format.' mod='shopifyimporter'}
        </div>
        <form action="{$uri}" method="post" enctype="multipart/form-data" class="form-horizontal">
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Choose CSV File' mod='shopifyimporter'}</label>
                <div class="col-lg-9">
                    <input type="file" name="shopify_csv" class="form-control" required />
                </div>
            </div>
            <div class="panel-footer">
                <button type="submit" name="submitShopifyImport" class="btn btn-default pull-right">
                    <i class="process-icon-save"></i> {l s='Upload & Start Queue' mod='shopifyimporter'}
                </button>
            </div>
        </form>
    {/if}
</div>
