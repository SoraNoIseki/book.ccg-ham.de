$( document ).ready(function() {

    // validation of ppt generator
    if ($('#pptGenerator').length) {
        var form = $('#pptGenerator');
        var requires = $(form).find('textarea.required');
        var button = $('#pptGenerator').find('#downloadButton');
        
        function updateDownloadButton() {
            var enable = true;
            $(requires).each((index, item) => {
                if ($(item).val() === '') {
                    enable = false;
                }
            });
            if (enable) {
                console.log('enable button');
                $(button).removeClass('opacity-50 cursor-not-allowed').attr('disabled', false);
            } else {
                console.log('disable button');
                $(button).addClass('opacity-50 cursor-not-allowed').attr('disabled', true);
            }
        }

        updateDownloadButton();
        $(requires).on('change', () => {
            updateDownloadButton();
        });
    }
});