# Security Policy

## 🔐 Simple LMS Security Documentation

This document outlines the security architecture, procedures, and best practices implemented in Simple LMS.

---

## Table of Contents

1. [Security Architecture](#security-architecture)
2. [Authentication & Authorization](#authentication--authorization)
3. [CSRF Protection (Nonce)](#csrf-protection-nonce)
4. [Input Validation & Sanitization](#input-validation--sanitization)
5. [Capability Matrix](#capability-matrix)
6. [REST API Security](#rest-api-security)
7. [AJAX Security](#ajax-security)
8. [Data Privacy & GDPR](#data-privacy--gdpr)
9. [Security Best Practices](#security-best-practices)
10. [Reporting Security Issues](#reporting-security-issues)

---

## Security Architecture

### Core Security Components

#### 1. Security_Service (`includes/class-security-service.php`)

Central security service providing:
- **Nonce generation and verification** (contextual)
- **Capability assertions** (centralized checks)
- **Access control helpers** (course/module/lesson)
- **Input sanitization utilities**

```php
// Example usage
$security = $container->get(Security_Service::class);

// Generate nonce
$nonce = $security->createNonce('ajax');

// Verify nonce
if (!$security->verifyNonce($_POST['nonce'], 'ajax')) {
    wp_send_json_error(['message' => 'Invalid security token']);
}

// Check capability
if (!$security->currentUserCanEditCourse($course_id)) {
    wp_send_json_error(['message' => 'Insufficient permissions']);
}
```

#### 2. Access_Control (`includes/access-control.php`)

Manages course access logic:
- **Tag-based access control** (user meta)
- **WooCommerce integration** (purchase verification)
- **Drip content scheduling** (time-based unlocking)
- **Redirect handling** (unauthorized access)

---

## Authentication & Authorization

### WordPress User Roles

Simple LMS respects WordPress core roles and capabilities:

| Role | Capabilities | Access Level |
|------|--------------|--------------|
| **Administrator** | Full access | All operations, settings, analytics |
| **Editor** | Course management | Create/edit/delete courses, modules, lessons |
| **Author** | Own content | Create/edit own courses |
| **Subscriber** | Student access | View enrolled courses, track progress |
| **Guest** | Public content | View public courses (if configured) |

### Custom Capabilities

Simple LMS uses WordPress standard capabilities with contextual checks:

- `edit_posts` - Create courses/modules/lessons
- `edit_post` - Edit specific course/module/lesson (ownership)
- `delete_posts` - Delete courses/modules/lessons
- `delete_post` - Delete specific item
- `manage_options` - Plugin settings, analytics configuration
- `manage_categories` - Bulk tag operations

---

## CSRF Protection (Nonce)

### Nonce Contexts

Simple LMS uses contextual nonce verification:

#### 1. REST API Nonces

**Context:** `rest`

```php
// Creating nonce (PHP)
$nonce = $security->createNonce('rest');

// JavaScript usage
fetch('/wp-json/simple-lms/v1/courses', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': simpleLMSData.restNonce
    },
    body: JSON.stringify({ title: 'New Course', nonce: simpleLMSData.restNonce })
});

// Verification (server-side)
if (!$security->verifyNonce($_POST['nonce'] ?? '', 'rest')) {
    return new WP_Error('invalid_nonce', 'Security verification failed');
}
```

#### 2. AJAX Nonces

**Context:** `ajax`

```php
// Creating nonce (PHP - localized to JS)
wp_localize_script('simple-lms-admin', 'simpleLMSData', [
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('simple-lms-nonce')
]);

// JavaScript usage
jQuery.ajax({
    url: simpleLMSData.ajaxUrl,
    type: 'POST',
    data: {
        action: 'add_new_module',
        course_id: 123,
        nonce: simpleLMSData.nonce
    }
});

// Verification (server-side - Ajax_Handler)
// Automatically handled by verifyAjaxRequest()
// Accepts both 'nonce' and legacy 'security' parameters
```

#### 3. Frontend Nonces (Lesson Completion)

**Context:** `simple-lms-nonce`

```php
// Creating nonce
wp_localize_script('simple-lms-frontend', 'simpleLMSFrontend', [
    'nonce' => wp_create_nonce('simple-lms-nonce')
]);

// Verification (completeLessonHandler)
if (!check_ajax_referer('simple-lms-nonce', 'nonce', false)) {
    wp_send_json_error(['message' => 'Invalid security token']);
}
```

### Nonce Best Practices

✅ **DO:**
- Use contextual nonces (`rest`, `ajax`, specific actions)
- Verify nonce before processing any state-changing operation
- Include nonce in AJAX requests (header or body)
- Regenerate nonces after critical operations (optional)

❌ **DON'T:**
- Reuse generic nonces across different contexts
- Skip nonce verification for "internal" operations
- Expose nonces in URLs (use POST body/headers)
- Store nonces in localStorage (use sessionStorage if needed)

---

## Input Validation & Sanitization

### Server-Side Validation

#### 1. Integer IDs

```php
$course_id = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;

if ($course_id <= 0) {
    throw new \InvalidArgumentException('Invalid course ID');
}

// Additional validation
$course = get_post($course_id);
if (!$course || $course->post_type !== 'course') {
    throw new \InvalidArgumentException('Course not found');
}
```

#### 2. Text Fields

```php
$title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';

if (empty($title) || strlen($title) < 3) {
    throw new \InvalidArgumentException('Title must be at least 3 characters');
}
```

#### 3. Rich Content (HTML)

```php
$content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';

// wp_kses_post allows safe HTML tags (p, strong, em, ul, ol, li, a, img, etc.)
// Strips dangerous tags (script, iframe, object, etc.)
```

#### 4. Status Values (Whitelist)

```php
$allowed_statuses = ['draft', 'pending', 'publish', 'private'];
$status = isset($_POST['status']) ? sanitize_key($_POST['status']) : 'draft';

if (!in_array($status, $allowed_statuses, true)) {
    $status = 'draft'; // Fallback to safe default
}
```

#### 5. Arrays

```php
// Integer array
$module_ids = isset($_POST['modules']) && is_array($_POST['modules'])
    ? array_map('absint', $_POST['modules'])
    : [];

// String array
$tags = isset($_POST['tags']) && is_array($_POST['tags'])
    ? array_map('sanitize_text_field', $_POST['tags'])
    : [];
```

### Output Escaping

Always escape output based on context:

```php
// HTML context
echo esc_html($user_input);

// Attribute context
echo '<div data-id="' . esc_attr($course_id) . '">';

// URL context
echo '<a href="' . esc_url($course_url) . '">';

// JavaScript context
echo '<script>var courseTitle = ' . wp_json_encode($title) . ';</script>';

// Translation with variables
echo esc_html(sprintf(__('Course: %s', 'simple-lms'), $course_title));
```

---

## Capability Matrix

### CRUD Operations

| Operation | Required Capability | Context | Additional Checks |
|-----------|-------------------|---------|-------------------|
| **Create Course** | `edit_posts` | REST (nonce) | Status defaults to `draft` |
| **Update Course** | `edit_post` (course_id) | REST (nonce) | Ownership verification |
| **Delete Course** | `delete_post` (course_id) | AJAX (nonce) | Ownership + cascade check |
| **Create Module** | `edit_post` (course_id) | REST/AJAX (nonce) | Parent course exists |
| **Update Module** | `edit_post` (module_id) | REST/AJAX (nonce) | Ownership verification |
| **Delete Module** | `delete_post` (module_id) | AJAX (nonce) | Orphan lessons handling |
| **Create Lesson** | `edit_post` (module_id) | REST/AJAX (nonce) | Parent module exists |
| **Update Lesson** | `edit_post` (lesson_id) | REST/AJAX (nonce) | Ownership verification |
| **Delete Lesson** | `delete_post` (lesson_id) | AJAX (nonce) | Ownership verification |

### Student Operations

| Operation | Required Capability | Context | Additional Checks |
|-----------|-------------------|---------|-------------------|
| **View Course** | Logged in (or public) | Frontend | Tag-based access or purchase |
| **View Module** | Course access | Frontend | Drip unlock schedule |
| **View Lesson** | Module unlocked | Frontend | Sequential completion (optional) |
| **Complete Lesson** | Logged in (own progress) | AJAX (nonce) | Rate limiting (20/min) |
| **Uncomplete Lesson** | Logged in (own progress) | AJAX (nonce) | Rate limiting (20/min) |

### Admin Operations

| Operation | Required Capability | Context | Additional Checks |
|-----------|-------------------|---------|-------------------|
| **Plugin Settings** | `manage_options` | Admin page | - |
| **Analytics Configuration** | `manage_options` | Admin page (AJAX) | - |
| **Bulk Tag Update** | `manage_categories` | AJAX (nonce) | - |
| **User Progress Edit** | `edit_users` | Admin/REST | Target user ID validation |

### WooCommerce Integration

| Operation | Required Capability | Context | Additional Checks |
|-----------|-------------------|---------|-------------------|
| **Link Product to Course** | `edit_post` (product_id) | Admin metabox | Product is virtual |
| **Create Course Product** | `edit_post` (course_id) | AJAX (nonce) | Course exists |
| **Set Default Product** | `edit_post` (course_id) | AJAX (nonce) | Product in course list |
| **Purchase Course** | Logged in customer | WooCommerce checkout | - |
| **Grant Access on Payment** | System (hook) | Order completion | Order status = completed |

---

## REST API Security

### Endpoint Security Model

All REST API endpoints follow this security pattern:

#### 1. Public Read Endpoints

**No authentication required** (read-only data)

```php
'GET /wp-json/simple-lms/v1/courses'
'GET /wp-json/simple-lms/v1/courses/{id}'
'GET /wp-json/simple-lms/v1/modules/{id}'
```

**Permission Callback:**
```php
public function checkPublicReadPermission() {
    return true; // Public access
}
```

#### 2. Authenticated Read Endpoints

**User must be logged in**

```php
'GET /wp-json/simple-lms/v1/progress/{user_id}'
```

**Permission Callback:**
```php
public function checkUserProgressPermission(\WP_REST_Request $request) {
    $user_id = (int) $request->get_param('user_id');
    $current_user = get_current_user_id();
    
    // Allow access to own progress or admins
    return $current_user === $user_id || current_user_can('edit_users');
}
```

#### 3. Write Endpoints (Create/Update)

**Requires nonce + capability check**

```php
'POST /wp-json/simple-lms/v1/courses'
'PUT /wp-json/simple-lms/v1/courses/{id}'
```

**Permission Callback:**
```php
public function checkCreateCoursePermission() {
    return current_user_can('edit_posts');
}

public function checkUpdateCoursePermission(\WP_REST_Request $request) {
    $course_id = (int) $request->get_param('id');
    return current_user_can('edit_post', $course_id);
}
```

**Nonce Verification (in handler):**
```php
public function createCourse(\WP_REST_Request $request) {
    // Verify nonce
    $nonce = $request->get_param('nonce');
    if (!$this->verifyRequestNonce($nonce)) {
        return new \WP_Error('invalid_nonce', 'Security verification failed', ['status' => 403]);
    }
    
    // Sanitize input
    $title = sanitize_text_field($request->get_param('title'));
    $content = wp_kses_post($request->get_param('content'));
    
    // ... create course
}
```

### Rate Limiting (Future Enhancement)

Planned rate limiting for REST API:

```php
// Example: 60 requests per minute per user
$rate_key = 'rest_api_rate_' . get_current_user_id();
$requests = (int) get_transient($rate_key);

if ($requests >= 60) {
    return new \WP_Error('rate_limit', 'Too many requests', ['status' => 429]);
}

set_transient($rate_key, $requests + 1, MINUTE_IN_SECONDS);
```

---

## AJAX Security

### AJAX Security Flow

All AJAX handlers use unified security verification:

#### 1. Action Validation

```php
private const VALID_ACTIONS = [
    'add_new_module',
    'add_new_lesson_from_module',
    'duplicate_lesson',
    'delete_lesson',
    // ... etc
];

$action = sanitize_key($_POST['action'] ?? '');

if (!in_array($action, self::VALID_ACTIONS, true)) {
    throw new \InvalidArgumentException('Invalid action');
}
```

#### 2. Nonce Verification

```php
// Accepts both 'nonce' and legacy 'security' parameter
$nonce = $_POST['nonce'] ?? $_POST['security'] ?? '';

if (!$nonce) {
    throw new \Exception('Missing security token');
}

// Prefer injected Security_Service
if (self::$security) {
    $valid = self::$security->verifyNonce($nonce, 'ajax');
} else {
    // Fallback to wp_verify_nonce
    $valid = wp_verify_nonce($nonce, 'simple-lms-nonce');
}

if (!$valid) {
    throw new \Exception('Security verification failed');
}
```

#### 3. Authentication Check

```php
if (!is_user_logged_in()) {
    throw new \Exception('Must be logged in');
}
```

#### 4. Capability Check (Action-Specific)

```php
// Capability mapping per action
$capMap = [
    'add_new_module' => 'edit_posts',
    'delete_lesson' => 'delete_posts',
    'save_course_settings' => 'manage_options',
    'bulk_update_tags' => 'manage_categories'
];

$requiredCap = $capMap[$action] ?? 'edit_posts';

if (!current_user_can($requiredCap)) {
    throw new \Exception('Insufficient permissions');
}
```

### Student AJAX (Lesson Completion)

Special handling for student-facing AJAX:

```php
// Rate limiting: max 20 completions per minute per user
$rate_key = 'slms_completion_rate_' . $user_id;
$attempts = (int) get_transient($rate_key);

if ($attempts >= 20) {
    wp_send_json_error(['message' => 'Too many attempts. Try again in a moment.']);
    return;
}

set_transient($rate_key, $attempts + 1, MINUTE_IN_SECONDS);
```

---

## Data Privacy & GDPR

### Personal Data Handling

Simple LMS implements WordPress Privacy Tools API:

#### 1. Data Collection

**Progress Tracking:**
- User ID
- Course/Module/Lesson IDs
- Completion timestamps
- Started/completed status

**Analytics (if enabled):**
- User ID
- Event type
- Event data (JSON)
- IP address
- User agent
- Timestamp

#### 2. Data Export

Users can request personal data export via WordPress Privacy Tools:

**Exporters registered:**
- `simple-lms-progress` - Course progress data
- `simple-lms-analytics` - Analytics events

**Export format:**
```json
{
  "group_id": "simple-lms-progress",
  "group_label": "Simple LMS - Course Progress",
  "items": [
    {
      "item_id": "simple-lms-progress-123",
      "data": [
        {"name": "Course", "value": "Introduction to PHP"},
        {"name": "Lesson", "value": "Variables and Data Types"},
        {"name": "Completion Status", "value": "Completed"},
        {"name": "Completed At", "value": "2025-12-01 14:30:00"}
      ]
    }
  ]
}
```

#### 3. Data Erasure

Users can request data deletion via WordPress Privacy Tools:

**Erasers registered:**
- `simple-lms-progress` - Deletes all progress records
- `simple-lms-analytics` - Deletes all analytics events

**What gets deleted:**
```php
// Progress table
DELETE FROM wp_simple_lms_progress WHERE user_id = %d

// Analytics table
DELETE FROM wp_simple_lms_analytics WHERE user_id = %d

// User meta
delete_user_meta($user_id, 'simple_lms_course_access');
delete_user_meta($user_id, 'simple_lms_course_access_expiration');
delete_user_meta($user_id, 'simple_lms_completed_lessons');
```

#### 4. Data Retention

Configurable retention period for analytics:

**Default:** 365 days  
**Option:** `simple_lms_analytics_retention_days`  
**Special value:** `-1` = unlimited retention

**Automated cleanup:**
```php
// Daily cron job
wp_schedule_event(time(), 'daily', 'simple_lms_cleanup_old_analytics');

// Cleanup logic
$cutoff_date = gmdate('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
DELETE FROM wp_simple_lms_analytics WHERE event_time < $cutoff_date
```

---

## Security Best Practices

### For Developers

#### 1. Always Verify Permissions

```php
// ❌ BAD - No permission check
public function updateCourse($course_id, $data) {
    wp_update_post(['ID' => $course_id, 'post_content' => $data['content']]);
}

// ✅ GOOD - Check capability
public function updateCourse($course_id, $data) {
    if (!current_user_can('edit_post', $course_id)) {
        throw new \Exception('Insufficient permissions');
    }
    wp_update_post(['ID' => $course_id, 'post_content' => wp_kses_post($data['content'])]);
}
```

#### 2. Use Prepared Statements

```php
// ❌ BAD - SQL injection risk
$results = $wpdb->get_results("SELECT * FROM {$table} WHERE user_id = {$user_id}");

// ✅ GOOD - Prepared statement
$results = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table} WHERE user_id = %d",
    $user_id
));
```

#### 3. Validate File Uploads

```php
// ✅ GOOD - Validate MIME type and size
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$max_size = 5 * 1024 * 1024; // 5MB

if (!in_array($_FILES['image']['type'], $allowed_types, true)) {
    throw new \Exception('Invalid file type');
}

if ($_FILES['image']['size'] > $max_size) {
    throw new \Exception('File too large');
}
```

#### 4. Use Security_Service

```php
// ✅ GOOD - Centralized security logic
$security = $container->get(Security_Service::class);

// Nonce verification
if (!$security->verifyNonce($_POST['nonce'], 'ajax')) {
    return false;
}

// Capability assertion
$security->assertCapability('edit_posts', 'Cannot create courses');

// Access check
if (!$security->currentUserCanViewCourse($course_id, $user_id)) {
    wp_redirect($security->getNoAccessUrl());
}
```

### For Users

#### 1. Keep WordPress Updated

Always use the latest stable version of WordPress for security patches.

#### 2. Use Strong Passwords

Enforce strong passwords for all user accounts with access to course management.

#### 3. Limit Administrator Accounts

Grant `manage_options` capability only to trusted administrators.

#### 4. Regular Backups

Backup database regularly, especially progress and analytics tables:
- `wp_simple_lms_progress`
- `wp_simple_lms_analytics`

#### 5. SSL/TLS Required

Always use HTTPS for admin area and student login/progress tracking.

---

## Reporting Security Issues

### Responsible Disclosure

If you discover a security vulnerability in Simple LMS, please report it responsibly:

**DO NOT** open a public GitHub issue for security vulnerabilities.

**Instead:**

1. **Email:** security@webisko.pl
2. **Subject:** "Simple LMS Security Vulnerability"
3. **Include:**
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if any)

### Response Timeline

- **Initial Response:** Within 48 hours
- **Vulnerability Assessment:** Within 1 week
- **Patch Development:** 1-2 weeks (depending on severity)
- **Public Disclosure:** After patch is released and users have time to update (typically 30 days)

### Security Updates

Security patches will be released as:
- **Critical:** Immediate hotfix release
- **High:** Included in next minor version (1.x.y → 1.x.y+1)
- **Medium/Low:** Included in next major version (1.x → 1.x+1)

---

## Changelog

### Security Improvements

#### Version 1.4.0 (December 2025)
- ✅ Introduced `Security_Service` for centralized nonce/capability management
- ✅ Enhanced REST API write endpoints with nonce requirement
- ✅ Implemented granular permission callbacks for REST operations
- ✅ Added capability mapping for AJAX actions
- ✅ Improved input sanitization (wp_kses_post, whitelist validation)
- ✅ Integrated structured logging for security events
- ✅ Added rate limiting for lesson completion AJAX

#### Version 1.3.0
- Added GDPR compliance (Privacy Tools API)
- Implemented data export/erasure

#### Version 1.2.0
- Enhanced WooCommerce integration security
- Added access control drip logic

---

## References

- [WordPress Security Best Practices](https://developer.wordpress.org/apis/security/)
- [WordPress Nonces](https://developer.wordpress.org/apis/security/nonces/)
- [WordPress Data Validation](https://developer.wordpress.org/apis/security/data-validation/)
- [WordPress Privacy API](https://developer.wordpress.org/apis/privacy/)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)

---

**Last Updated:** December 2, 2025  
**Version:** 1.4.0  
**Maintainer:** Filip Meyer-Lüters (Webisko)
