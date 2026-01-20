<?php
// Stub overrides for VS Code PHP Language Server (not loaded at runtime)
if (!function_exists('current_user_can')) {
    function current_user_can($capability, ...$args): bool {
        return true;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null) {
        return true;
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback = null, $priority = 10, $accepted_args = 1) {
        return true;
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback = null, $priority = 10, $accepted_args = 1) {
        return true;
    }
}
