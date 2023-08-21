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
        this.sortable = Sortable.create(this.$element[0], { // [0] because Sortable wants raw html (not a jquery element)
            handle: '.drag-handle', // only move whole item when moving this element
            animation: 150,
            onEnd: () => {
                // console.log(this.sortable.toArray());
                $.ajax({
                    'url': this.$element.data('url')+'/reorder',
                    'method': 'POST',
                    'data': JSON.stringify(this.sortable.toArray())
                });
            }
        });
        this.references = [];
        this.render();

        // Delete reference - Add event listener to every reference
        this.$element.on('click', '.js-reference-delete', (event) => {
            this.handleReferenceDelete(event);
        });

        this.$element.on('blur', '.js-edit-filename', (event) => {
            this.handleReferenceEditFilename(event);
        });

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

    // Edit article reference filename
    handleReferenceEditFilename(event) {
        const $li = $(event.currentTarget).closest('.list-group-item');
        const id = $li.data('id');
        const reference = this.references.find(reference => {
            return reference.id = id;
        });
        reference.originalFilename = $(event.currentTarget).val();

        $.ajax({
            'url': '/admin/article/references/'+id, // admin_article_edit_reference
            'method': 'PUT',
            'data': JSON.stringify(reference),
            'error': function (data, status, error) {
                const span = document.createElement("span");
                span.innerHTML = JSON.parse(data.responseText).detail;
                span.className = "alert alert-danger";
                $(event.currentTarget).closest('.list-group-item').after(span);
            }
        })
    }

    // Show List
    render() {
        const itemsHtml = this.references.map(reference => {
            return `
<li class="list-group-item d-flex justify-content-between align-items-center" data-id="${reference.id}">
    <!-- Show only name ${reference.originalFilename}-->
    <!-- User can reorder -->
    <span class="drag-handle fa fa-reorder"></span>
    <!-- User can edit -->
    <input class="form-control js-edit-filename" type="text" value="${reference.originalFilename}" style="  width: auto">
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
