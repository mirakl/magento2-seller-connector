require([
    'jquery',
    'Magento_Ui/js/modal/modal'
], function ($, modal) {
    let options = {
        type: 'popup',
        responsive: true,
        innerScroll: true,
        modalClass: 'mirakl-order-thread-modal',
        buttons: [{
            text: $.mage.__('Close'),
            class: 'action submit btn btn-default',
            click: function () {
                this.closeModal();
            }
        }]
    };

    $(document).on('click', '.order-thread-view', function (e) {
        e.preventDefault();
        openModalUrl(this.href, options);
    });

    $(document).on('click', '.order-thread-new', function (e) {
        openModalUrl($(this).attr('data-url'), options);
    });

    function openModalUrl(url, options)
    {
        $.ajax({
            type: 'get',
            url: url,
            showLoader: true,
            dataType: 'html',
            complete: function (data) {
                $('.mirakl-thread-view-content')
                    .html(data.responseText)
                    .modal(options)
                    .modal('openModal');
            }
        });
    }
});
