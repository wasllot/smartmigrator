<div class="panel">
    <h3><i class="icon icon-tags"></i> {l s='Smart Product Migrator' mod='smartmigrator'}</h3>
    <p>
        {l s='Intelligent Import from ANY CSV. Auto-detects columns (Title, SKU, Handle, etc) and groups variants.' mod='smartmigrator'}
    </p>

    <div class="row" style="margin-bottom:20px;">
        <div class="col-md-12">
            <button id="import-all-btn" class="btn btn-primary btn-lg" onclick="runBatchImport()">
                <i class="icon-cloud-download"></i> {l s='Import All Ready Products' mod='shopifyimporter'}
            </button>
            <form action="{$base_url}" method="post" class="pull-right" style="display:inline; margin-left: 10px;">
                <button type="submit" name="delete_imported" class="btn btn-danger" onclick="return confirm('{l s='ARE YOU SURE? This will delete all imported products from the store. A backup will be saved.' mod='shopifyimporter'}');">
                    <i class="icon-archive"></i> {l s='Undo/Delete All Imports' mod='shopifyimporter'}
                </button>
            </form>
            <form action="{$base_url}" method="post" class="pull-right" style="display:inline;">
                <button type="submit" name="clear_queue" class="btn btn-default" onclick="return confirm('{l s='Clear the queue? This will remove items from the list below.' mod='shopifyimporter'}');">
                    <i class="icon-trash"></i> {l s='Clear Queue' mod='shopifyimporter'}
                </button>
            </form>
        </div>
    </div>

    <!-- Progress Bar -->
    <div id="import-progress" style="display:none; margin-bottom: 20px;">
        <h4>{l s='Importing...' mod='shopifyimporter'} <span id="progress-text"></span></h4>
        <div class="progress">
            <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                <span id="progress-percent">0%</span>
            </div>
        </div>
        <div id="import-log" style="max-height: 200px; overflow-y: scroll; border: 1px solid #ccc; padding: 10px; background: #f9f9f9; font-family: monospace;"></div>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>{l s='Image' mod='shopifyimporter'}</th>
                <th>{l s='Handle' mod='shopifyimporter'}</th>
                <th>{l s='Product Name' mod='shopifyimporter'}</th>
                <th>{l s='Variants' mod='shopifyimporter'}</th>
                <th>{l s='SKU Preview' mod='shopifyimporter'}</th>
                <th>{l s='Actions' mod='shopifyimporter'}</th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$analyzed_items item=item}
            <tr>
                <td>
                    {if $item.image}
                        <img src="{$item.image}" style="max-width: 50px; max-height: 50px;">
                    {else}
                        <span class="label label-default">No Image</span>
                    {/if}
                </td>
                <td>{$item.handle}</td>
                <td><strong>{$item.name}</strong></td>
                <td><span class="badge">{$item.variants_count}</span></td>
                <td>
                    <code>{$item.generated_sku}</code>
                </td>
                <td>
                    <form action="{$base_url}" method="post" style="display:inline;">
                        <input type="hidden" name="id_queue" value="{$item.id_queue}">
                        <button type="submit" name="delete_queue_item" class="btn btn-default btn-xs" title="{l s='Remove' mod='shopifyimporter'}">
                            <i class="icon-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
            {/foreach}
        </tbody>
    </table>
    
    <script type="text/javascript">
        var importUri = '{$import_url nofilter}';
        // Calculate total for progress bar based on rows in table
        var totalItems = {$analyzed_items|@count};
        var processedCount = 0;

        function runBatchImport() {
            $('#import-all-btn').prop('disabled', true);
            $('#import-progress').show();
            $('#import-log').append('Starting import...<br>');
            processNextBatch();
        }

        function processNextBatch() {
            $.ajax({
                url: importUri,
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    if (response.done) {
                        $('#import-progress .progress-bar').removeClass('active').addClass('progress-bar-success');
                        $('#import-log').append('<strong>Import Completed!</strong><br>');
                        alert('Import Finished!');
                        location.reload();
                    } else {
                        processedCount++;
                        var percent = Math.round(((totalItems - response.remaining) / totalItems) * 100); // Rough estimate
                         // Or use remaining logic: (InitialTotal - Remaining) / InitialTotal
                         // Since we reload page, simple feedback is fine.
                        
                        $('#import-progress .progress-bar').css('width', percent + '%').attr('aria-valuenow', percent);
                        $('#progress-percent').text(percent + '%');
                        $('#import-log').append(response.msg + '<br>');
                        
                        // Scroll log to bottom
                        var log = document.getElementById('import-log');
                        log.scrollTop = log.scrollHeight;

                        processNextBatch();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                     $('#import-log').append('<span style="color:red">AJAX Error: ' + textStatus + '</span><br>');
                     // Retry logic? For now, stop to avoid infinite loop
                     $('#import-all-btn').prop('disabled', false);
                }
            });
        }
    </script>
</div>
