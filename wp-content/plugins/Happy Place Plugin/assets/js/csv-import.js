/**
 * CSV Import Tool JavaScript
 */
jQuery(document).ready(function($) {
    const $form = $('#hph-csv-import-form');
    const $previewBtn = $('#preview-csv');
    const $importBtn = $('#import-csv');
    const $previewSection = $('#csv-preview');
    const $progressSection = $('#import-progress');
    const $resultsSection = $('#import-results');

    let csvData = null;
    let validationResults = null;

    /**
     * Preview CSV data
     */
    $previewBtn.on('click', function(e) {
        e.preventDefault();
        
        const fileInput = $('#csv_file')[0];
        if (!fileInput.files.length) {
            alert('Please select a CSV file first.');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'hph_import_csv');
        formData.append('action_type', 'preview');
        formData.append('nonce', hphImport.nonce);
        formData.append('csv_file', fileInput.files[0]);

        $previewBtn.prop('disabled', true).text('Loading Preview...');

        $.ajax({
            url: hphImport.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    csvData = response.data;
                    validationResults = response.data.validation;
                    renderPreview(response.data);
                    $importBtn.prop('disabled', false);
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Failed to process CSV file. Please try again.');
            },
            complete: function() {
                $previewBtn.prop('disabled', false).text('Preview CSV Data');
            }
        });
    });

    /**
     * Import CSV data
     */
    $form.on('submit', function(e) {
        e.preventDefault();

        if (!csvData) {
            alert('Please preview the CSV data first.');
            return;
        }

        // Show validation warnings
        if (validationResults.errors.length > 0) {
            const proceed = confirm(
                `Found ${validationResults.errors.length} errors in your CSV. ` +
                'These rows will be skipped. Continue with import?'
            );
            if (!proceed) return;
        }

        const fileInput = $('#csv_file')[0];
        const formData = new FormData();
        formData.append('action', 'hph_import_csv');
        formData.append('action_type', 'import');
        formData.append('nonce', hphImport.nonce);
        formData.append('csv_file', fileInput.files[0]);
        formData.append('import_mode', $('#import_mode').val());
        formData.append('default_status', $('#default_status').val());

        // Show progress
        $progressSection.show();
        $resultsSection.hide();
        updateProgress(0, 'Starting import...');

        $importBtn.prop('disabled', true);

        $.ajax({
            url: hphImport.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                updateProgress(100, 'Import completed!');
                
                setTimeout(() => {
                    $progressSection.hide();
                    if (response.success) {
                        renderResults(response.data);
                    } else {
                        alert('Import failed: ' + response.data);
                    }
                }, 1000);
            },
            error: function() {
                updateProgress(100, 'Import failed!');
                alert('Import failed. Please try again.');
            },
            complete: function() {
                $importBtn.prop('disabled', false);
            }
        });
    });

    /**
     * Render CSV preview
     */
    function renderPreview(data) {
        let html = `
            <div style="padding: 20px;">
                <div class="preview-summary">
                    <p><strong>Total Rows:</strong> ${data.total_rows}</p>
                    <p><strong>Valid Rows:</strong> ${data.validation.valid_rows}</p>
                    ${data.validation.errors.length > 0 ? 
                        `<p style="color: #d63638;"><strong>Errors:</strong> ${data.validation.errors.length}</p>` : 
                        ''
                    }
                    ${data.validation.warnings.length > 0 ? 
                        `<p style="color: #dba617;"><strong>Warnings:</strong> ${data.validation.warnings.length}</p>` : 
                        ''
                    }
                </div>
        `;

        // Show validation messages
        if (data.validation.errors.length > 0 || data.validation.warnings.length > 0) {
            html += '<div class="validation-messages" style="margin: 20px 0;">';
            
            if (data.validation.errors.length > 0) {
                html += '<h4 style="color: #d63638;">Errors:</h4><ul>';
                data.validation.errors.forEach(error => {
                    html += `<li style="color: #d63638;">${error}</li>`;
                });
                html += '</ul>';
            }

            if (data.validation.warnings.length > 0) {
                html += '<h4 style="color: #dba617;">Warnings:</h4><ul>';
                data.validation.warnings.forEach(warning => {
                    html += `<li style="color: #dba617;">${warning}</li>`;
                });
                html += '</ul>';
            }
            
            html += '</div>';
        }

        // Preview table
        html += '<h4>Data Preview (First 5 Rows):</h4>';
        html += '<div style="overflow-x: auto;"><table class="preview-table">';
        
        // Headers
        html += '<thead><tr>';
        data.headers.forEach(header => {
            html += `<th>${header}</th>`;
        });
        html += '</tr></thead>';

        // Rows
        html += '<tbody>';
        data.rows.forEach(row => {
            html += '<tr>';
            data.headers.forEach(header => {
                const value = row[header] || '';
                html += `<td>${escapeHtml(String(value).substring(0, 50))}${value.length > 50 ? '...' : ''}</td>`;
            });
            html += '</tr>';
        });
        html += '</tbody></table></div>';

        html += '</div>';

        $('#preview-content').html(html);
        $previewSection.show();
    }

    /**
     * Update import progress
     */
    function updateProgress(percent, message) {
        $('.progress-fill').css('width', percent + '%');
        $('.progress-text').text(message);
    }

    /**
     * Render import results
     */
    function renderResults(results) {
        let html = `
            <div class="result-summary">
                <div class="result-stat">
                    <div class="number">${results.total}</div>
                    <div class="label">Total Rows</div>
                </div>
                <div class="result-stat">
                    <div class="number">${results.created}</div>
                    <div class="label">Created</div>
                </div>
                <div class="result-stat">
                    <div class="number">${results.updated}</div>
                    <div class="label">Updated</div>
                </div>
                <div class="result-stat">
                    <div class="number">${results.skipped}</div>
                    <div class="label">Skipped</div>
                </div>
                <div class="result-stat">
                    <div class="number">${results.errors}</div>
                    <div class="label">Errors</div>
                </div>
            </div>
        `;

        if (results.log && results.log.length > 0) {
            html += '<h3>Import Log:</h3>';
            html += '<div class="import-log">';
            results.log.forEach(entry => {
                const className = `log-${entry.type}`;
                html += `<div class="${className}">${escapeHtml(entry.message)}</div>`;
            });
            html += '</div>';
        }

        // Success message
        if (results.errors === 0) {
            html = `<div class="notice notice-success"><p><strong>Import completed successfully!</strong></p></div>` + html;
        } else {
            html = `<div class="notice notice-warning"><p><strong>Import completed with ${results.errors} errors.</strong></p></div>` + html;
        }

        $('#results-content').html(html);
        $resultsSection.show();

        // Scroll to results
        $('html, body').animate({
            scrollTop: $resultsSection.offset().top - 50
        }, 500);
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * File input change handler
     */
    $('#csv_file').on('change', function() {
        // Reset preview and import button when file changes
        $previewSection.hide();
        $progressSection.hide();
        $resultsSection.hide();
        $importBtn.prop('disabled', true);
        csvData = null;
        validationResults = null;
    });
});