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
                $(button).removeClass('opacity-50 cursor-not-allowed').attr('disabled', false);
            } else {
                $(button).addClass('opacity-50 cursor-not-allowed').attr('disabled', true);
            }
        }

        updateDownloadButton();
        $(requires).on('change', function () {
            updateDownloadButton();
        });
    }

    // bible selector
    if ($('.bible-selector').length) {
        $('.bible-selector').on('change', function () {
            var value = $(this).val();
            $(this).siblings('textarea').text(value + ':1[*]');
            $(this).val('');
        });
    }
    
    // report list
    var reportList = $('#reportList');
    if ($(reportList).length) {
        function sortReports() {
            $(reportList).find('.report-item').each((index, report) => {
                $(report).find('textarea').attr('name', 'report[item' + (index + 1).toString() + ']')
            });
        }

        // sort list
        var sortable = Sortable.create(reportList[0], {
            onEnd: function (/**Event*/evt) {
                sortReports();
            },
        });

        // delete
        $(reportList).find('.report-item .delete').on('click', function () {
            if (confirm("确认要删除吗？")) {
                $(this).parents('.report-item').remove();
                sortReports();
            }
        });

        // add
        $(reportList).on('click', '.report-item .add', function () {
            var reportItem = $(this).parents('.report-item').clone();
                console.log(reportItem);
                $(reportItem).find('textarea').val('');
                $(this).parents('.report-item').after(reportItem);
                sortReports();
        });

    }

   
});