$(document).ready(function() {
    $('.apply-song-content').click(function() {
        var target = $(this).data('target');
        var content = $('#' + target).val();
        $('#main-song-content').val(content);
    });

    $('.toggle-song-content').click(function() {
        var target = $(this).data('target');
        $('#' + target).toggle();
    });

    $('.beauty-song-content').click(function() {
        var target = $('#' + $(this).data('target'));
        if ($(target)) {
            var content = $(target).val();
            var lines = content.split('\n');
            for (var i = 0; i < lines.length; i++) {
                // delete leading and trailing spaces
                lines[i] = lines[i].trim();
                if (i > 1) {
                    // replace all full-width punctuation marks with full-width space
                    lines[i] = lines[i].replace(/[\u3002\uff1b\uff0c\uff1a\u201c\u201d\uff08\uff09\u3001\uff1f\uff01\u0020\u002c\u002e\u003b\u003a\u003f\u0021]/g, '\u3000');
                    // replace all full-width spaces with full-width space
                    lines[i] = lines[i].replace(/\u3000+$/, '');
                    // replace all full-width spaces with full-width space
                    lines[i] = lines[i].replace(/\u3000+/g, '\u3000');
                }
            }
            $(target).val(lines.join('\n'));
        }
    });

    $('.get-task-plans').click(function() {
        var target = $(this).data('target');
        var url = $(this).data('url');
        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                $('#' + target).html(response.data.text);
            }
        });
    });
});