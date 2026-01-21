# Simple LMS Hooks Reference

Comprehensive documentation of all action and filter hooks available in Simple LMS for theme and plugin developers.

---

## Table of Contents

1. [Actions](#actions)
   - [Plugin Lifecycle](#plugin-lifecycle)
   - [Access Control](#access-control)
   - [Progress Tracking](#progress-tracking)
   - [Analytics](#analytics)
   - [Cache Management](#cache-management)
2. [Filters](#filters)
   - [Security & Nonces](#security--nonces)
    - [Assets & Performance](#assets--performance)
   - [Access Control](#access-control-filters)
   - [Cache Configuration](#cache-configuration)
   - [Debug & Logging](#debug--logging)
   - [Content Filters](#content-filters)
3. [Hook Usage Examples](#hook-usage-examples)
4. [Best Practices](#best-practices)

---

## Actions

### Plugin Lifecycle

#### `simple_lms_before_init`
Fires before Simple LMS initializes its core components.

**Parameters:** None

**Use Cases:**
- Register custom services before plugin initialization
- Modify plugin configuration early in the load sequence
- Add custom autoloaders or dependencies

**Example:**
```php
add_action('simple_lms_before_init', function() {
    // Register custom analytics provider
    MyCustomAnalytics::init();
});
```

---

#### `simple_lms_after_init`
Fires after Simple LMS completes initialization.

**Parameters:** None

**Use Cases:**
- Add custom post type extensions
- Register additional REST API endpoints
- Initialize integrations that depend on Simple LMS

**Example:**
```php
add_action('simple_lms_after_init', function() {
    // Register custom course taxonomy
    register_taxonomy('course_category', 'simple_course', [
        'label' => __('Course Categories', 'my-plugin'),
        'hierarchical' => true,
    ]);
});
```

---

#### `simple_lms_activated`
Fires when the plugin is activated.

**Parameters:** None

**Use Cases:**
- Create custom database tables
- Set default options for extensions
- Schedule custom cron events

**Example:**
```php
add_action('simple_lms_activated', function() {
    // Schedule custom cleanup task
    if (!wp_next_scheduled('my_custom_lms_cleanup')) {
        wp_schedule_event(time(), 'daily', 'my_custom_lms_cleanup');
    }
});
```

---

#### `simple_lms_deactivated`
Fires when the plugin is deactivated.

**Parameters:** None

**Use Cases:**
- Clear custom cron events
- Flush rewrite rules
- Clean up temporary data

**Example:**
```php
add_action('simple_lms_deactivated', function() {
    // Clear custom scheduled events
    wp_clear_scheduled_hook('my_custom_lms_cleanup');
});
```

---

#### `simple_lms_uninstalled`
Fires during plugin uninstallation (legacy - use uninstall.php).

**Parameters:** None

**Note:** Modern plugins should use `uninstall.php` instead. This hook is maintained for backward compatibility.

---

### Access Control

#### `simple_lms_user_enrolled`
Fires when a user is enrolled in a course.

**Parameters:**
- `int $user_id` - User ID being enrolled
- `int $course_id` - Course ID
- `int $order_id` - WooCommerce order ID (0 if manual enrollment)

**Use Cases:**
- Send custom welcome emails
- Grant additional access to related content
- Update external CRM systems
- Award user badges or achievements

**Example:**
```php
add_action('simple_lms_user_enrolled', function($user_id, $course_id, $order_id) {
    // Send welcome email with course materials
    $user = get_userdata($user_id);
    $course = get_post($course_id);
    
    wp_mail(
        $user->user_email,
        sprintf(__('Welcome to %s', 'my-plugin'), $course->post_title),
        get_welcome_email_content($course_id)
    );
}, 10, 3);
```

---

#### `simple_lms_access_revoked`
Fires when a user's course access is revoked.

**Parameters:**
- `int $user_id` - User ID losing access
- `int $course_id` - Course ID
- `string $reason` - Reason for revocation (e.g., 'subscription_expired', 'refund', 'manual')

**Use Cases:**
- Send access expiration notifications
- Clean up user progress data
- Update external systems
- Log audit trail

**Example:**
```php
add_action('simple_lms_access_revoked', function($user_id, $course_id, $reason) {
    // Log access revocation for audit
    error_log(sprintf(
        'User %d lost access to course %d. Reason: %s',
        $user_id,
        $course_id,
        $reason
    ));
    
    // Optionally notify user
    if ($reason === 'subscription_expired') {
        send_renewal_reminder($user_id, $course_id);
    }
}, 10, 3);
```

---

### Progress Tracking

#### `simple_lms_lesson_progress_updated`
Fires when a user's lesson progress is updated.

**Parameters:**
- `int $user_id` - User ID
- `int $lesson_id` - Lesson ID
- `bool $completed` - Whether lesson is marked complete

**Use Cases:**
- Update custom progress dashboards
- Award points for lesson completion
- Trigger conditional content unlocking
- Send progress reports to instructors

**Example:**
```php
add_action('simple_lms_lesson_progress_updated', function($user_id, $lesson_id, $completed) {
    if ($completed) {
        // Award points for completion
        award_user_points($user_id, 10, 'lesson_completed');
        
        // Check if course should be marked complete
        $course_id = get_post_meta($lesson_id, '_parent_course', true);
        if (is_course_complete($user_id, $course_id)) {
            do_action('my_plugin_course_completed', $user_id, $course_id);
        }
    }
}, 10, 3);
```

---

#### `simple_lms_course_completed`
Fires when a user completes all lessons in a course.

**Parameters:**
- `int $user_id` - User ID
- `int $course_id` - Course ID
- `array $completion_data` - Contains: `total_lessons`, `completed_at`, `duration_seconds`

**Use Cases:**
- Award certificates
- Send completion emails
- Update LMS records
- Trigger next course recommendations

**Example:**
```php
add_action('simple_lms_course_completed', function($user_id, $course_id, $completion_data) {
    // Generate and email certificate
    $certificate_url = generate_course_certificate($user_id, $course_id);
    
    $user = get_userdata($user_id);
    wp_mail(
        $user->user_email,
        __('Congratulations on Course Completion!', 'my-plugin'),
        sprintf(
            __('Download your certificate: %s', 'my-plugin'),
            $certificate_url
        )
    );
}, 10, 3);
```

---

### Analytics

#### `simple_lms_analytics_event`
Fires for all analytics events (generic hook).

**Parameters:**
- `string $event_type` - Event type (e.g., 'lesson_completed', 'course_started', 'quiz_submitted')
- `int $user_id` - User ID triggering the event
- `array $data` - Event-specific data array

**Use Cases:**
- Send events to external analytics platforms
- Log all user interactions
- Build custom reporting dashboards
- A/B testing tracking

**Example:**
```php
add_action('simple_lms_analytics_event', function($event_type, $user_id, $data) {
    // Send to Google Analytics 4
    if (function_exists('gtag')) {
        gtag('event', $event_type, [
            'user_id' => $user_id,
            'event_category' => 'lms',
            'value' => $data['value'] ?? 0,
        ]);
    }
}, 10, 3);
```

---

#### `simple_lms_analytics_{event_type}`
Dynamic hook for specific analytics event types.

**Parameters:**
- `int $user_id` - User ID
- `array $data` - Event data

**Available Event Types:**
- `simple_lms_analytics_lesson_completed`
- `simple_lms_analytics_course_started`
- `simple_lms_analytics_quiz_submitted`
- `simple_lms_analytics_video_watched`
- `simple_lms_analytics_resource_downloaded`

**Example:**
```php
add_action('simple_lms_analytics_lesson_completed', function($user_id, $data) {
    // Track lesson completion in Mixpanel
    mixpanel_track('Lesson Completed', [
        'user_id' => $user_id,
        'lesson_id' => $data['lesson_id'],
        'duration' => $data['duration_seconds'],
    ]);
}, 10, 2);
```

---

### Cache Management

#### `simple_lms_progress_cache_cleared`
Fires when progress cache is cleared for a user.

**Parameters:**
- `int $user_id` - User ID
- `int $course_id` - Course ID (0 if all courses)

**Use Cases:**
- Clear related custom caches
- Invalidate CDN cached data
- Refresh external dashboard displays

**Example:**
```php
add_action('simple_lms_progress_cache_cleared', function($user_id, $course_id) {
    // Clear custom transients
    if ($course_id === 0) {
        // Clear all courses for user
        delete_transient("user_{$user_id}_course_stats");
    } else {
        delete_transient("user_{$user_id}_course_{$course_id}_stats");
    }
}, 10, 2);
```

---

## Filters

### Security & Nonces

#### `simple_lms_nonce_action`
Filters the nonce action string for security verification.

**Parameters:**
- `string $action` - Default nonce action (e.g., 'simple_lms_rest', 'simple_lms_ajax')
- `string $context` - Context ('rest', 'ajax', 'frontend')

**Returns:** `string` - Modified nonce action

**Use Cases:**
- Implement per-user nonce actions
- Add additional security layers
- Integrate with external auth systems

**Example:**
```php
add_filter('simple_lms_nonce_action', function($action, $context) {
    // Add user ID to nonce action for extra security
    if ($context === 'ajax') {
        return $action . '_' . get_current_user_id();
    }
    return $action;
}, 10, 2);
```

---

#### `simple_lms_rest_nonce_action`
Filters REST API nonce action (legacy - use `simple_lms_nonce_action`).

**Parameters:**
- `string $action` - Default action ('simple_lms_rest')

**Returns:** `string` - Modified action

---

#### `simple_lms_ajax_nonce_action`
Filters AJAX nonce action (legacy - use `simple_lms_nonce_action`).

**Parameters:**
- `string $action` - Default action ('simple-lms-nonce')

**Returns:** `string` - Modified action

---

### Assets & Performance

#### `simple_lms_enqueue_frontend_assets`
Filters whether Simple LMS frontend assets should be enqueued on the current request.

**Parameters:**
- `bool $should_enqueue` - Default decision based on LMS page context

**Returns:** `bool` - True to enqueue assets, false to skip

**Use Cases:**
- Disable assets on custom landing pages
- Force assets on pages with builder widgets
- Optimize performance for non-LMS pages

**Example:**
```php
add_filter('simple_lms_enqueue_frontend_assets', function($should_enqueue) {
    // Always enqueue on a custom dashboard page
    if (is_page('student-dashboard')) {
        return true;
    }
    return $should_enqueue;
});
```

---

### Access Control Filters

#### `simple_lms_user_has_course_access`
Filters whether a user has access to a course.

**Parameters:**
- `bool $has_access` - Default access determination
- `int $user_id` - User ID being checked
- `int $course_id` - Course ID

**Returns:** `bool` - True if user has access, false otherwise

**Use Cases:**
- Grant temporary access for previews
- Implement custom access rules (e.g., organization membership)
- Add time-based access restrictions
- Integrate with external membership systems

**Example:**
```php
add_filter('simple_lms_user_has_course_access', function($has_access, $user_id, $course_id) {
    // Allow preview access for first module
    if (!$has_access && isset($_GET['preview']) && current_user_can('read')) {
        $first_module = get_first_module_id($course_id);
        $requested_module = get_query_var('module_id');
        return ($requested_module === $first_module);
    }
    return $has_access;
}, 10, 3);
```

---

### Cache Configuration

#### `simple_lms_cache_expiration`
Filters the default cache expiration time.

**Parameters:**
- `int $expiration` - Default expiration in seconds (300)

**Returns:** `int` - Modified expiration time

**Example:**
```php
add_filter('simple_lms_cache_expiration', function($expiration) {
    // Use longer cache for production
    return is_production() ? 3600 : 300;
});
```

---

#### `simple_lms_progress_cache_ttl`
Filters progress cache TTL for specific user/course.

**Parameters:**
- `int $ttl` - Default TTL in seconds (300)
- `int $user_id` - User ID
- `int $course_id` - Course ID

**Returns:** `int` - Modified TTL

**Example:**
```php
add_filter('simple_lms_progress_cache_ttl', function($ttl, $user_id, $course_id) {
    // Use shorter cache for active courses
    $last_activity = get_user_last_activity($user_id, $course_id);
    if ($last_activity > (time() - 3600)) {
        return 60; // 1 minute for active users
    }
    return $ttl;
}, 10, 3);
```

---

#### `simple_lms_course_stats_cache_ttl`
Filters course statistics cache TTL.

**Parameters:**
- `int $ttl` - Default TTL in seconds (600)
- `int $course_id` - Course ID

**Returns:** `int` - Modified TTL

**Example:**
```php
add_filter('simple_lms_course_stats_cache_ttl', function($ttl, $course_id) {
    // Shorter TTL for popular courses
    $enrollment_count = get_course_enrollment_count($course_id);
    if ($enrollment_count > 1000) {
        return 300; // 5 minutes
    }
    return $ttl;
}, 10, 2);
```

---

### Debug & Logging

#### `simple_lms_debug_enabled`
Filters whether debug logging is enabled.

**Parameters:**
- `bool $enabled` - Default state (based on WP_DEBUG and plugin option)

**Returns:** `bool` - True to enable debug logging

**Use Cases:**
- Force logging for specific environments
- Disable logging in production
- Conditional logging based on user role

**Example:**
```php
add_filter('simple_lms_debug_enabled', function($enabled) {
    // Force debug for administrators
    if (current_user_can('manage_options')) {
        return true;
    }
    // Disable in production for non-admins
    return wp_get_environment_type() !== 'production' && $enabled;
});
```

---

### Content Filters

Note: Simple LMS uses WordPress core `the_content` filter for lesson/course content. No custom content filters are provided.

---

## Hook Usage Examples

### Example 1: Custom Certificate Generation

```php
/**
 * Generate and send certificate when course is completed
 */
add_action('simple_lms_course_completed', 'my_generate_certificate', 10, 3);

function my_generate_certificate($user_id, $course_id, $completion_data) {
    $user = get_userdata($user_id);
    $course = get_post($course_id);
    
    // Generate certificate PDF
    require_once(__DIR__ . '/lib/certificate-generator.php');
    $cert = new Certificate_Generator();
    $pdf_path = $cert->generate([
        'user_name' => $user->display_name,
        'course_title' => $course->post_title,
        'completion_date' => current_time('mysql'),
        'duration' => human_time_diff(0, $completion_data['duration_seconds']),
    ]);
    
    // Store certificate URL in user meta
    $cert_url = upload_certificate_to_media($pdf_path, $user_id, $course_id);
    add_user_meta($user_id, "_course_{$course_id}_certificate", $cert_url, true);
    
    // Send email with certificate
    wp_mail(
        $user->user_email,
        sprintf(__('Your %s Certificate', 'my-plugin'), $course->post_title),
        sprintf(__("Congratulations!\n\nDownload: %s", 'my-plugin'), $cert_url),
        ['Content-Type: text/html; charset=UTF-8']
    );
}
```

---

### Example 2: Integration with External CRM

```php
/**
 * Sync enrollments and progress to external CRM
 */
class LMS_CRM_Integration {
    public function __construct() {
        add_action('simple_lms_user_enrolled', [$this, 'sync_enrollment'], 10, 3);
        add_action('simple_lms_course_completed', [$this, 'sync_completion'], 10, 3);
        add_action('simple_lms_access_revoked', [$this, 'sync_revocation'], 10, 3);
    }
    
    public function sync_enrollment($user_id, $course_id, $order_id) {
        $user = get_userdata($user_id);
        $course = get_post($course_id);
        
        $this->api_request('POST', '/enrollments', [
            'email' => $user->user_email,
            'course_name' => $course->post_title,
            'enrolled_at' => current_time('c'),
            'order_id' => $order_id,
        ]);
    }
    
    public function sync_completion($user_id, $course_id, $completion_data) {
        $user = get_userdata($user_id);
        
        $this->api_request('PATCH', '/enrollments/' . $user->user_email, [
            'status' => 'completed',
            'completed_at' => current_time('c'),
            'duration_seconds' => $completion_data['duration_seconds'],
        ]);
    }
    
    public function sync_revocation($user_id, $course_id, $reason) {
        $user = get_userdata($user_id);
        
        $this->api_request('PATCH', '/enrollments/' . $user->user_email, [
            'status' => 'revoked',
            'revoked_at' => current_time('c'),
            'reason' => $reason,
        ]);
    }
    
    private function api_request($method, $endpoint, $data) {
        wp_remote_request(CRM_API_URL . $endpoint, [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . CRM_API_KEY,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($data),
            'timeout' => 15,
        ]);
    }
}

new LMS_CRM_Integration();
```

---

### Example 3: Custom Access Rules

```php
/**
 * Implement organization-based access control
 */
add_filter('simple_lms_user_has_course_access', 'org_based_access_control', 10, 3);

function org_based_access_control($has_access, $user_id, $course_id) {
    // Skip if user already has access through normal means
    if ($has_access) {
        return true;
    }
    
    // Check if course is tagged for organization access
    $org_courses = get_post_meta($course_id, '_organization_access', true);
    if (empty($org_courses)) {
        return $has_access;
    }
    
    // Check user's organization membership
    $user_orgs = get_user_meta($user_id, '_organization_memberships', true);
    if (empty($user_orgs)) {
        return false;
    }
    
    // Grant access if user belongs to any authorized organization
    $org_courses_array = explode(',', $org_courses);
    $user_orgs_array = explode(',', $user_orgs);
    
    return !empty(array_intersect($org_courses_array, $user_orgs_array));
}
```

---

### Example 4: Analytics Integration

```php
/**
 * Send all LMS events to Google Analytics 4
 */
add_action('simple_lms_analytics_event', 'send_to_ga4', 10, 3);

function send_to_ga4($event_type, $user_id, $data) {
    // Don't track admin actions
    if (current_user_can('manage_options')) {
        return;
    }
    
    // Map LMS events to GA4 event names
    $event_map = [
        'lesson_completed' => 'lms_lesson_complete',
        'course_started' => 'lms_course_start',
        'quiz_submitted' => 'lms_quiz_submit',
    ];
    
    $ga4_event = $event_map[$event_type] ?? $event_type;
    
    // Send to GA4 Measurement Protocol
    wp_remote_post('https://www.google-analytics.com/mp/collect', [
        'body' => json_encode([
            'client_id' => $user_id,
            'events' => [[
                'name' => $ga4_event,
                'params' => array_merge([
                    'engagement_time_msec' => 100,
                ], $data),
            ]],
        ]),
        'headers' => [
            'Content-Type' => 'application/json',
        ],
    ]);
}
```

---

## Best Practices

### Performance

1. **Use Specific Hooks**: Prefer `simple_lms_analytics_lesson_completed` over generic `simple_lms_analytics_event` when possible to reduce unnecessary callback executions.

2. **Async Operations**: For expensive operations (external API calls, PDF generation), queue them:
```php
add_action('simple_lms_course_completed', function($user_id, $course_id) {
    wp_schedule_single_event(time() + 10, 'my_generate_certificate', [$user_id, $course_id]);
}, 10, 2);
```

3. **Cache Results**: Cache expensive filter results:
```php
add_filter('simple_lms_user_has_course_access', function($has_access, $user_id, $course_id) {
    $cache_key = "access_{$user_id}_{$course_id}";
    $cached = wp_cache_get($cache_key, 'simple_lms');
    
    if ($cached !== false) {
        return $cached;
    }
    
    $result = expensive_access_check($user_id, $course_id);
    wp_cache_set($cache_key, $result, 'simple_lms', 300);
    
    return $result;
}, 10, 3);
```

### Security

1. **Validate Inputs**: Always validate/sanitize data from hooks:
```php
add_action('simple_lms_user_enrolled', function($user_id, $course_id, $order_id) {
    $user_id = absint($user_id);
    $course_id = absint($course_id);
    
    if (!get_userdata($user_id) || !get_post($course_id)) {
        return; // Invalid data
    }
    
    // Proceed with valid data
}, 10, 3);
```

2. **Check Capabilities**: Verify permissions in hooks:
```php
add_filter('simple_lms_user_has_course_access', function($has_access, $user_id, $course_id) {
    // Only allow override if current user is admin
    if (!current_user_can('manage_options')) {
        return $has_access;
    }
    
    // Admin override logic
    return isset($_GET['force_access']) ? true : $has_access;
}, 10, 3);
```

3. **Nonce Verification**: When hooks trigger user actions, verify nonces:
```php
add_action('simple_lms_before_init', function() {
    if (isset($_GET['reset_progress'])) {
        if (!wp_verify_nonce($_GET['_wpnonce'], 'reset_progress')) {
            wp_die('Security check failed');
        }
        // Reset progress logic
    }
});
```

### Debugging

1. **Log Hook Executions**: Use debug logging for troubleshooting:
```php
add_action('simple_lms_course_completed', function($user_id, $course_id) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log(sprintf(
            'Course completed: User %d, Course %d at %s',
            $user_id,
            $course_id,
            current_time('mysql')
        ));
    }
}, 10, 2);
```

2. **Use Priority Appropriately**: Default priority is 10. Use lower for early execution, higher for late:
```php
// Run before default handlers
add_action('simple_lms_user_enrolled', 'my_early_handler', 5, 3);

// Run after default handlers
add_action('simple_lms_user_enrolled', 'my_late_handler', 20, 3);
```

3. **Document Custom Hooks**: If your extension adds new hooks, document them:
```php
/**
 * Fires when a custom quiz type is submitted.
 *
 * @since 1.0.0
 *
 * @param int   $user_id   User ID submitting quiz
 * @param int   $quiz_id   Quiz ID
 * @param array $answers   User's answers
 * @param int   $score     Calculated score
 */
do_action('my_plugin_quiz_submitted', $user_id, $quiz_id, $answers, $score);
```

---

## Related Documentation

- [SECURITY.md](SECURITY.md) - Security architecture and nonce contexts
- [ARCHITECTURE.md](ARCHITECTURE.md) - Design patterns and service architecture
- [README.md](README.md) - Plugin overview and features
- [WordPress Plugin Handbook - Hooks](https://developer.wordpress.org/plugins/hooks/)

---

**Last Updated:** December 2, 2025  
**Plugin Version:** 1.4.0
