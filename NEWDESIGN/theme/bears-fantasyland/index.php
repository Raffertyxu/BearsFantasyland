<?php
if (!defined('ABSPATH')) { exit; }
if (function_exists('bfnd_is_site_page') && bfnd_is_site_page()) {
    require __DIR__ . '/templates/site.php';
    return;
}
require __DIR__ . '/templates/fallback.php';
