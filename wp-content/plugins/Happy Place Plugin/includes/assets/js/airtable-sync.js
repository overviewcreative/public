(function($) {
    'use strict';

    class AirtableSync {
        constructor() {
            this.initializeEventListeners();
        }

        initializeEventListeners() {
            $('#happy-place-sync-airtable').on('click', (e) => {
                e.preventDefault();
                this.handleSync();
            });
        }

        async handleSync() {
            const baseId = $('#airtable-base-id').val();
            const tableName = $('#airtable-table-name').val();

            if (!baseId || !tableName) {
                alert('Please provide both Base ID and Table Name');
                return;
            }

            try {
                const response = await $.ajax({
                    url: happyPlaceAirtable.ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'sync_airtable',
                        nonce: happyPlaceAirtable.nonce,
                        base_id: baseId,
                        table_name: tableName
                    }
                });

                if (response.success) {
                    alert(`Sync completed! Processed ${response.data.processed} records.`);
                } else {
                    throw new Error(response.data);
                }
            } catch (error) {
                console.error('Sync error:', error);
                alert('Error during sync. Check console for details.');
            }
        }
    }

    // Initialize when document is ready
    $(document).ready(() => new AirtableSync());

})(jQuery);
