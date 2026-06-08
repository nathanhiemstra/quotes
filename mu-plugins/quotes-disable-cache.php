<?php
/**
 * Plugin Name: Quotes Disable Cache
 * Description: Sends no-cache headers for the Quotes site while layout/content is still changing.
 */

foreach (['DONOTCACHEPAGE', 'DONOTCACHEOBJECT', 'DONOTCACHEDB'] as $constant) {
    if (! defined($constant)) {
        define($constant, true);
    }
}

add_action('send_headers', function () {
    if (is_admin()) {
        return;
    }

    nocache_headers();
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
});
