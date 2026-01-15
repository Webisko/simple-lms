<?php
declare(strict_types=1);

namespace SimpleLMS;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST API - Compatibility wrapper
 * 
 * This file provides a compatibility layer for the refactored REST API.
 * The actual implementation is in class-rest-api-refactored.php
 * 
 * @package SimpleLMS
 * @since 1.5.0
 */

// Include refactored class with full implementation
require_once __DIR__ . '/class-rest-api-refactored.php';

// Alias for compatibility
class_alias('SimpleLMS\Rest_API', 'SimpleLMS_Rest_API');
