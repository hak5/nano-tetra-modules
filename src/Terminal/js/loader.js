$(function() {
    // JS
    var js = $('<script src="modules/Terminal/js/jquery-resizable.min.js"></script>');
    $('head').append(js);

    // HTML
    var button = '<li id="terminal-open">' +
                 '    <a>' +
                 '        <img width="20px" src="modules/Terminal/img/console-open.png"/>' +
                 '    </a>' +
                 '</li>';
    $('.navbar-top-links').prepend(button);

    var html = $('<div></div>').load('modules/Terminal/html/inject.html');
    $('body').append(html);
});
