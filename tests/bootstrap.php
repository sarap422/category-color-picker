<?php
/**
 * PHPUnit bootstrap for Category Color Picker unit tests.
 *
 * The plugin's pure-logic class keeps its `if (!defined('ABSPATH')) exit;`
 * guard for WordPress/Plugin Check compliance. Defining ABSPATH here lets the
 * file load in the test process without WordPress.
 */

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

require __DIR__ . '/../vendor/autoload.php';

$ccp_io = __DIR__ . '/../category-color-picker/includes/class-ccp-io.php';
if (file_exists($ccp_io)) {
    require $ccp_io;
}
