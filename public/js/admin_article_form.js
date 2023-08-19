// Tells dropzone to not automatically configure itself
Dropzone.autoDiscover = false;

$(document).ready(function() {
    // Instantiating ReferenceList will populate the list with references (see its constructor)
    var referenceList = new ReferenceList($('.js-reference-list'));

    // Config for multiple file upload
    initializeDropzone(referenceList);

    // Editing Article Location and hide/show options depending on the location
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

// TODO - use Webpack Encore so ES6 syntax is transpiled to ES5
class ReferenceList
{
    constructor($element) {
        this.$element = $element;
        this.references = [];
        this.render();

        // Delete reference - Add event listener to every reference
        this.$element.on('click', '.js-reference-delete', (event) => {
            this.handleReferenceDelete(event);
        })

        // Get article references
        $.ajax({
            url: this.$element.data('url') // api_admin_article_list_references
        }).then(data => {
            this.references = data;
            this.render();
        })
    }

    // Add new article reference to liste and Refresh list
    addReference(reference) {
        this.references.push(reference);
        this.render();
    }

    // Delete article reference and Refresh list
    handleReferenceDelete(event) {
        const $li = $(event.currentTarget).closest('.list-group-item');
        const id = $li.data('id');
        $li.addClass('disabled');

        $.ajax({
            'url': '/admin/article/references/'+id, // admin_article_delete_reference
            'method': 'DELETE'
        }).then(() => {
            this.references = this.references.filter(reference => {
                return reference.id !== id;
            });
            this.render();
        });
    }

    // Show List
    render() {
        const itemsHtml = this.references.map(reference => {
            return `
<li class="list-group-item d-flex justify-content-between align-items-center" data-id="${reference.id}">
    ${reference.originalFilename}
    <span>
        <a class="btn btn-link btn-sm" href="/admin/article/references/${reference.id}/download"><span class="fa fa-download" style="vertical-align: middle"></span></a>
        <button class="js-reference-delete btn btn-link btn-sm"><span class="fa fa-trash"></span></button>
    </span>
</li>
`
        });
        this.$element.html(itemsHtml.join(''));
    }
}

/**
 * Dropzone multiple file uploads - configuration
 * @param {ReferenceList} referenceList
 */
function initializeDropzone(referenceList) {
    var formElement = document.querySelector('.js-reference-dropzone');
    if (!formElement) {
        return;
    }

    /*
    * Allows getting file in route admin_article_add_references by name "reference"
    * $request->files->get('reference');
    */
    var dropzone = new Dropzone(formElement, {
        paramName: 'reference',
        init: function () { // init - called when setting things up
            // SUCCESS file upload => add filename to list
            this.on('success', function (file, data) {
                referenceList.addReference(data);
            })
            // ERROR
            // file - contains details about the file that was uploaded
            // data - data sent back by the server
            this.on('error', function (file, data) {
                if (data.detail) {
                    this.emit('error', file, data.detail); // error message is in data.detail
                }
            })
        }
    })
}
