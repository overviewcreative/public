/**
 * Property PDF Generator
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        const printButton = $('#hph-print-property');
        
        if (!printButton.length) {
            return;
        }

        printButton.on('click', function(e) {
            e.preventDefault();
            generatePropertyPDF();
        });

        function generatePropertyPDF() {
            $.ajax({
                url: hphPDF.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'generate_property_pdf',
                    nonce: hphPDF.nonce,
                    property_id: hphPDF.propertyId
                },
                beforeSend: function() {
                    printButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Generating PDF...');
                },
                success: function(response) {
                    if (response.success && response.data) {
                        const { html, filename } = response.data;
                        
                        // Create temporary container for PDF content
                        const container = $('<div/>').html(html);
                        $('body').append(container);

                        // Generate PDF
                        const options = {
                            margin: [10, 10],
                            filename: filename,
                            image: { type: 'jpeg', quality: 0.98 },
                            html2canvas: { 
                                scale: 2,
                                useCORS: true,
                                logging: false
                            },
                            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
                        };

                        html2pdf()
                            .set(options)
                            .from(container[0])
                            .save()
                            .then(() => {
                                container.remove();
                                printButton
                                    .prop('disabled', false)
                                    .html('<i class="fas fa-print"></i> Print Property Details');
                            })
                            .catch(error => {
                                console.error('PDF Generation Error:', error);
                                alert('Error generating PDF. Please try again.');
                                container.remove();
                                printButton
                                    .prop('disabled', false)
                                    .html('<i class="fas fa-print"></i> Print Property Details');
                            });
                    } else {
                        alert('Error generating PDF. Please try again.');
                        printButton
                            .prop('disabled', false)
                            .html('<i class="fas fa-print"></i> Print Property Details');
                    }
                },
                error: function() {
                    alert('Error generating PDF. Please try again.');
                    printButton
                        .prop('disabled', false)
                        .html('<i class="fas fa-print"></i> Print Property Details');
                }
            });
        }
    });
})(jQuery);
