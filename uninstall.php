<?php
/**
 * Uninstall Simple LMS
 * 
 * Fired when the plugin is uninstalled.
 * Removes all plugin data from the database (optional, based on settings).
 *
 * @package SimpleLMS
 * @since 1.3.3
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if user wants to keep data (option set in settings)
$keep_data = get_option('simple_lms_keep_data_on_uninstall', false);

if ($keep_data) {
    // User wants to keep data, exit early
    return;
}

global $wpdb;

/**
 * Remove custom post types and their meta
 */
function simple_lms_uninstall_remove_posts() {
    $post_types = ['course', 'module', 'lesson'];
    
    foreach ($post_types as $post_type) {
        $posts = get_posts([
            'post_type'      => $post_type,
            'posts_per_page' => -1,
            'post_status'    => 'any',
        ]);
        
        foreach ($posts as $post) {
            // Force delete (skip trash)
            wp_delete_post($post->ID, true);
        }
    }
}

/**
 * Remove plugin options
 */
function simple_lms_uninstall_remove_options() {
    global $wpdb;
    
    // Remove all options with simple_lms prefix
    $wpdb->query(
        "DELETE FROM {$wpdb->options} 
         WHERE option_name LIKE 'simple\_lms\_%'"
    );
    
    // Remove specific known options
    delete_option('simple_lms_version');
    delete_option('simple_lms_db_version');
    delete_option('simple_lms_analytics_retention_days');
    delete_option('simple_lms_keep_data_on_uninstall');
}

/**
 * Remove user meta
 */
function simple_lms_uninstall_remove_user_meta() {
    global $wpdb;
    
    // Remove course access tags
    $wpdb->query(
        "DELETE FROM {$wpdb->usermeta} 
         WHERE meta_key = 'simple_lms_course_access'"
    );
    
    // Remove other plugin-specific user meta
    $wpdb->query(
        "DELETE FROM {$wpdb->usermeta} 
         WHERE meta_key LIKE 'simple\_lms\_%'"
    );
}

/**
 * Remove transients
 */
function simple_lms_uninstall_remove_transients() {
    global $wpdb;
    
    // Remove transients
    $wpdb->query(
        "DELETE FROM {$wpdb->options} 
         WHERE option_name LIKE '\_transient\_simple\_lms\_%' 
         OR option_name LIKE '\_transient\_timeout\_simple\_lms\_%'"
    );
    
    // Remove site transients (multisite)
    $wpdb->query(
        "DELETE FROM {$wpdb->options} 
         WHERE option_name LIKE '\_site\_transient\_simple\_lms\_%' 
         OR option_name LIKE '\_site\_transient\_timeout\_simple\_lms\_%'"
    );
}

/**
 * Remove custom database tables
 */
function simple_lms_uninstall_remove_tables() {
    global $wpdb;
    
    $tables = [
        $wpdb->prefix . 'simple_lms_progress',
        $wpdb->prefix . 'simple_lms_analytics',
    ];
    
    foreach ($tables as $table) {
        // Check if table exists before dropping
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table
        ));
        
        if ($table_exists) {
            $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
        }
    }
}

/**
 * Clear WordPress caches
 */
function simple_lms_uninstall_clear_caches() {
    // Clear object cache
    wp_cache_flush();
    
    // Clear rewrite rules
    flush_rewrite_rules();
}

/**
 * Main uninstall routine
 */
function simple_lms_uninstall() {
    // Remove posts and meta
    simple_lms_uninstall_remove_posts();
    
    // Remove options
    simple_lms_uninstall_remove_options();
    
    // Remove user meta
    simple_lms_uninstall_remove_user_meta();
    
    // Remove transients
    simple_lms_uninstall_remove_transients();
    
    // Remove custom tables
    simple_lms_uninstall_remove_tables();
    
    // Clear caches
    simple_lms_uninstall_clear_caches();
    
    // Log uninstall (optional, for debugging)
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Simple LMS: Plugin uninstalled and all data removed.');
    }
}

// Execute uninstall
simple_lms_uninstall();
