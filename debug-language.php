<?php
// Direct WordPress database query to check language setting
define('WP_USE_THEMES', false);
require(dirname(__FILE__) . '/../../../../wp-load.php');

global $wpdb;

// Check what's in the database
$result = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
        'simple_lms_%'
    )
);

echo "=== Simple LMS Settings in Database ===\n";
if ($result) {
    foreach ($result as $row) {
        echo $row->option_name . ": " . $row->option_value . "\n";
    }
} else {
    echo "No settings found\n";
}

// Check which .mo files exist
echo "\n=== Available .mo Files ===\n";
$mo_files = glob(dirname(__FILE__) . '/languages/*.mo');
foreach ($mo_files as $file) {
    echo basename($file) . " (" . filesize($file) . " bytes)\n";
}

// Check current locale
echo "\n=== WordPress Info ===\n";
echo "Current get_locale(): " . get_locale() . "\n";
echo "WP_LANG constant: " . (defined('WP_LANG') ? WP_LANG : 'not defined') . "\n";
?>
