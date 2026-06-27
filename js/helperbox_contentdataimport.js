(function (Drupal, $, once) {

    Drupal.behaviors.helperboxContentDataImport = {
        attach: function (context) {

            function import_ajax(sourceUrl, contentType, nodeContent, taxonomyContent, page, createdCount = 0, updatedCount = 0) {

                $.ajax({
                    url: Drupal.url('helperbox/api/importdata'),
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        source_url: sourceUrl,
                        content_type: contentType,
                        node_content: nodeContent,
                        taxonomy_term_content: taxonomyContent,
                        page: page
                    },

                    success: function (response) {
                        // accumulate counts
                        createdCount += parseInt(response.created_count || 0);
                        updatedCount += parseInt(response.updated_count || 0);

                        // message
                        $('#import-result-wrapper #message-status').html(
                            '<div class="messages messages--status">' +
                            response.message + ' at page: ' + page +
                            '</div>'
                        );

                        // show accumulated counts
                        if (createdCount) {
                            $('#import-result-wrapper #import-count-create').html(
                                '<div class="messages messages--status">' +
                                '<p>Total created: ' + createdCount + '</p>' +
                                '</div>'
                            );
                        }
                        if (updatedCount) {
                            $('#import-result-wrapper #import-count-update').html(
                                '<div class="messages messages--status">' +
                                '<p>Total updated: ' + updatedCount + '</p>' +
                                '</div>'
                            );
                        }

                        // next page recursion
                        if (response.next_url) {
                            import_ajax(
                                sourceUrl,
                                contentType,
                                nodeContent,
                                taxonomyContent,
                                page + 1,
                                createdCount,
                                updatedCount
                            );
                        } else {
                            $('#helperbox-import-btn').show();
                        }
                    },

                    error: function (xhr, status, error) {

                        $('#import-result-wrapper #message-status').html(
                            '<div class="messages messages--error">' +
                            'Import failed at page: ' + page +
                            '<br>' +
                            error +
                            '</div>'
                        );

                        console.error('AJAX Error:', {
                            status: status,
                            error: error,
                            response: xhr.responseText
                        });

                        $('#helperbox-import-btn').show();

                    },

                    complete: function () {
                        // always runs (success OR error)
                        console.log('Request completed for page:', page);
                    }
                });
            }

            once('helperbox-import', 'form#helperbox-content-data-import-form', context).forEach(function (form) {

                // BLOCK ENTER KEY SUBMIT
                $(form).on('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        return false;
                    }
                });

                // BLOCK NORMAL SUBMIT
                $(form).on('submit', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    return false;
                });

                // HANDLE BUTTON CLICK INSTEAD
                $(form).find('#helperbox-import-btn').on('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    $(this).hide();

                    const sourceUrl = $('input[name="source_url"]', form).val();
                    const contentType = $('select[name="content_type"]', form).val();
                    const nodeContent = $('select[name="node_content"]', form).val();
                    const taxonomyContent = $('select[name="taxonomy_term_content"]', form).val();
                    const page = Number($('input[name="page"]', form).val() || 0);

                    $('#import-result-wrapper #message-status').html(
                        '<div class="messages messages--status">Continue... Loading... at page: ' + page + '</div>'
                    );
                    $('#import-result-wrapper #import-count-create').html('');

                    $('#import-result-wrapper #import-count-update').html('');

                    // start with counters = 0
                    import_ajax(sourceUrl, contentType, nodeContent, taxonomyContent, page, 0, 0);
                });

            });

        }
    };

})(Drupal, jQuery, once);