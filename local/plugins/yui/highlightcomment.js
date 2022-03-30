M.local_plugins_highlightcomment = {};

/**
 * This function is called for comments block
 */
M.local_plugins_highlightcomment.init = function(Y, options) {
    Y.on(['mouseover', 'mouseout'], M.local_plugins_highlightcomment.highlight, '.plugin-comment', null, Y);
};

M.local_plugins_highlightcomment.highlight = function(e, Y) {
    Y.all('.plugin-comment').removeClass('highlighted');
    if (e.type == 'mouseover') {
        var el = e.target;
        while (el && !el.hasClass('plugin-comment')) el = el.get('parentNode');
        if (!el) return;
        el.addClass('highlighted');
    }
};
