$(document).ready(function() {
    console.log('book.js loaded');

    $('.apply-song-content').click(function() {
        var target = $(this).data('target');
        var content = $('#' + target).val();
        $('#main-song-content').val(content);
    });

    $('.toggle-song-content').click(function() {
        var target = $(this).data('target');
        console.log($('#' + target));
        $('#' + target).toggle();
    });
});