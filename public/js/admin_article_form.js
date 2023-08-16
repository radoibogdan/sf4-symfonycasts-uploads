// Tells dropzone to not automatically configure itself
Dropzone.autoDiscover = false;

$(document).ready(function() {

    initializeDropzone();

    var $locationSelect = $('.js-article-form-location');
    var $specificLocationTarget = $('.js-specific-location-target');

    $locationSelect.on('change', function(e) {
        $.ajax({
            url: $locationSelect.data('specific-location-url'),
            data: {
                location: $locationSelect.val()
            },
            success: function (html) {
                if (!html) {
                    $specificLocationTarget.find('select').remove();
                    $specificLocationTarget.addClass('d-none');

                    return;
                }

                // Replace the current field and show
                $specificLocationTarget
                    .html(html)
                    .removeClass('d-none')
            }
        });
    });
});

/* Dropzone multiple file uploads - configuration*/
function initializeDropzone() {
    var formElement = document.querySelector('.js-reference-dropzone');
    if (!formElement) {
        return;
    }

    /*
    * Allows getting file in route admin_article_add_references by name "reference"
    * $request->files->get('reference');
    */
    var dropzone = new Dropzone(formElement, {
        paramName: 'reference'
    })
}
