# SimpleLMS - GitHub Copilot Context

## Project Overview

**Simple LMS** is a WordPress Learning Management System plugin with:
- Hierarchical course structure (Course → Module → Lesson)
- WooCommerce integration for course sales
- Elementor & Bricks Builder widgets
- Tag-based access control (user_meta)
- Drip content scheduling
- Progress tracking
- REST API
- Multilingual support (Polish, English, German)

---

## Technical Stack

- **PHP:** 7.4+ (target 8.0+)
- **WordPress:** 6.0+
- **WooCommerce:** 7.0+ (optional integration)
- **Elementor:** 3.5+ (optional)
- **Bricks Builder:** 1.5+ (optional)
- **Standards:** PSR-12, WordPress Coding Standards

---

## Big Picture Architecture

- WordPress plugin entrypoint is `simple-lms.php`: bootstraps a DI-driven SimpleLMSPlugin, registers services, hooks, and late-loaded components.
- Dependency injection is centralized in `includes/class-service-container.php`; most services are registered as singletons and resolved by class name or string IDs.
- Hook registration is centralized through `includes/managers/HookManager.php` to avoid scattered add_action/add_filter calls.
- Core data model: CPTs `course`, `module`, `lesson`, plus `lms_template` for global templates (no-access). CPT registration lives in `includes/managers/CPTManager.php`.
- Relationships: modules store `parent_course` meta; lessons store `parent_module` meta. Many queries use these meta links.
- Access control lives in `includes/access-control.php`: course access stored in user meta `simple_lms_course_access` plus per-course expiration meta; access checks are cached via transients.
- Progress tracking uses a custom DB table (`simple_lms_progress`) created on init in `includes/class-progress-tracker.php`; analytics uses `simple_lms_analytics` in `includes/class-analytics-tracker.php` when enabled.
- REST API endpoints are in `includes/class-rest-api-refactored.php` under namespace `simple-lms/v1`; `includes/class-rest-api.php` is a compatibility alias.

## Integration Points

- **Elementor:** widgets + dynamic tags are registered via `includes/elementor-dynamic-tags/class-elementor-dynamic-tags.php`; registration happens on Elementor hooks (categories/widgets/dynamic tags).
- **Bricks:** elements are registered in `includes/bricks/class-bricks-integration.php` using `bricks_init` and custom category `simple-lms`.
- **WooCommerce:** integration is optional and loaded lazily (see `simple-lms.php` and `includes/class-woocommerce-integration.php`).
- **Elementor Site Editor:** AJAX calls are special-cased in `SimpleLMSPlugin::shouldBypassBootstrapForElementorSiteEditorAjax` to prevent breaking JSON responses.

## Project-Specific Conventions

- Prefer `ServiceContainer` for new services; wire them in `SimpleLMSPlugin::registerServices` or `registerLateServices` instead of new Singletons.
- Use `HookManager` when adding new WP hooks in core flows (front-end assets, REST registration, init). Check existing registrations in `simple-lms.php`.
- AJAX handlers are centralized in `includes/ajax-handlers.php`; use the action switch and `verifyAjaxRequest`, nonce action is `simple-lms-nonce`.
- Cache usage: `Cache_Handler` is used for course/module/lesson lists; access control caches per-user access with transients.
- Frontend assets are enqueued via `AssetManager` (`includes/managers/AssetManager.php`); CSS is prebuilt in `assets/dist/css/frontend-style.css`.

## Examples to Follow

- Register REST endpoints in `Rest_API::registerEndpoints` (`includes/class-rest-api-refactored.php`).
- Add CPTs via `CPTManager::registerPostTypes` (`includes/managers/CPTManager.php`).
- Access gating and body classes in `Access_Control::register` (`includes/access-control.php`).
- Elementor widget pattern in `includes/elementor-dynamic-tags/widgets/course-overview-widget.php`.
- Bricks element pattern in `includes/bricks/elements/course-overview.php`.

## Namespace & Structure

### Core Namespace
```php
namespace SimpleLMS;
```

### Directory Structure
```
simple-lms/
├── includes/                   # Core PHP classes
│   ├── class-*.php            # Main classes (CamelCase)
│   ├── access-control.php     # Helper functions
│   ├── elementor-dynamic-tags/
│   │   ├── widgets/           # Elementor widgets
│   │   └── tags/              # Dynamic tags
│   └── bricks/
│       └── elements/          # Bricks elements
├── assets/
│   ├── src/                   # Source (for development)
│   │   ├── css/
│   │   └── js/
│   └── dist/                  # Built assets (production)
├── languages/                 # Translations (.po, .mo)
└── tests/                     # PHPUnit tests
```

## Coding Standards

### PHP

**1. Always Use Namespace:**
```php
<?php
namespace SimpleLMS;

// NOT: class My_Class
// YES: class MyClass
```

**2. Escape ALL Output:**
```php
// Text
echo esc_html( $text );

// Attributes
echo '<div class="' . esc_attr( $class ) . '">';

// URLs
echo '<a href="' . esc_url( $url ) . '">';

// Rich HTML (allowed tags)
echo wp_kses_post( $content );
```

**3. Sanitize ALL Input:**
```php
// Text fields
$title = sanitize_text_field( $_POST['title'] );

// Integers
$course_id = absint( $_POST['course_id'] );

// Emails
$email = sanitize_email( $_POST['email'] );

// URLs
$url = esc_url_raw( $_POST['url'] );

// Verify nonces
if ( ! wp_verify_nonce( $_POST['nonce'], 'action_name' ) ) {
    wp_die( 'Security check failed' );
}
```

**4. Function Documentation (PHPDoc):**
```php
/**
 * Check if user has access to course
 *
 * @param int $user_id User ID
 * @param int $course_id Course ID
 * @return bool True if user has access
 */
function simple_lms_user_has_course_access( $user_id, $course_id ) {
    $access = (array) get_user_meta( $user_id, 'simple_lms_course_access', true );
    return in_array( $course_id, $access, true );
}
```

**5. Error Handling:**
```php
// Check for errors
if ( is_wp_error( $result ) ) {
    error_log( 'SimpleLMS Error: ' . $result->get_error_message() );
    return false;
}

// Validate post type
if ( get_post_type( $post_id ) !== 'course' ) {
    return new \WP_Error( 'invalid_post_type', __( 'Invalid post type', 'simple-lms' ) );
}
```

## Access Control System

### Tag-Based Access (NOT Roles!)

**User Meta Key:**
```php
'simple_lms_course_access' // Array of course IDs
```

**Helper Functions:**
```php
// Check access
simple_lms_user_has_course_access( $user_id, $course_id );

// Grant access
simple_lms_assign_course_access( $user_id, $course_id );

// Remove access
simple_lms_remove_course_access( $user_id, $course_id );
```

## Internationalization (i18n)

### Text Domain
```php
'simple-lms'
```

### Translation Functions
```php
// Simple string
__( 'Text', 'simple-lms' );

// Echo string
_e( 'Text', 'simple-lms' );

// With context
_x( 'Post', 'noun', 'simple-lms' );

// Plural
_n( '%s course', '%s courses', $count, 'simple-lms' );

// Escaped
esc_html__( 'Text', 'simple-lms' );
esc_attr__( 'Text', 'simple-lms' );
```

## Build System

Uses **Vite** for asset bundling:
- Run `npm install` to install dependencies
- Run `npm run dev` for development with HMR
- Run `npm run build` for production build
- See `BUILD.md` for detailed instructions

## Common Pitfalls to Avoid

❌ **DON'T:**
- Use WordPress roles for course access (use user_meta tags)
- Forget to escape output
- Forget to sanitize input
- Use global namespace (always use `SimpleLMS\`)
- Hardcode text (always use `__()` / `_e()`)
- Skip nonce verification in AJAX
- Query without caching for repeated calls

✅ **DO:**
- Use `simple_lms_user_has_course_access()` for access checks
- Always escape: `esc_html()`, `esc_attr()`, `esc_url()`
- Always sanitize: `sanitize_text_field()`, `absint()`
- Use namespace: `namespace SimpleLMS;`
- Translate strings: `__( 'Text', 'simple-lms' )`
- Verify nonces: `wp_verify_nonce()`
- Check capabilities: `current_user_can()`
- Cache queries: `wp_cache_get/set()`

## Performance Best Practices

1. **Cache Heavy Queries:**
   - Course structure (modules/lessons)
   - User progress data
   - Access control checks

2. **Lazy Load Assets:**
   - Enqueue scripts only on relevant pages
   - Use `wp_enqueue_script()` with dependencies

3. **Optimize Database:**
   - Use `posts_per_page` limits
   - Index custom meta keys if querying often

4. **Avoid N+1 Queries:**
   - Use `get_posts()` with `post__in` instead of loops
   - Cache results for repeated access

---

**Last Updated:** January 23, 2026
