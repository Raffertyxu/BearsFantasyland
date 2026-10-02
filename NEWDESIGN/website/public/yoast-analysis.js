(function ($) {
    'use strict';

    var registered = false;
    var content = window.BFNDYoastPageContent && window.BFNDYoastPageContent.content;

    function registerLayoutContent() {
        var yoast = window.YoastSEO;
        if (registered || !content || !yoast || !yoast.app || !yoast.analysis || !yoast.analysis.worker) {
            return false;
        }

        yoast.app.registerPlugin('bfndRenderedPageContent', { status: 'ready' });
        yoast.app.registerModification('content', function (pageContent) {
            var savedContent = typeof pageContent === 'string' ? pageContent.replace(/\[bfnd_page\b[^\]]*\]/g, '') : '';
            return savedContent + '\n' + content;
        }, 'bfndRenderedPageContent', 10);
        registered = true;
        return true;
    }

    if (!registerLayoutContent()) {
        $(window).on('YoastSEO:ready', registerLayoutContent);
    }
})(jQuery);
