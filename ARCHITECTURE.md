# Simple LMS Architecture

This document explains the architectural decisions, design patterns, and technical principles that guide Simple LMS development.

---

## Table of Contents

1. [Architectural Overview](#architectural-overview)
2. [Design Principles](#design-principles)
3. [Core Patterns](#core-patterns)
4. [Service Container](#service-container)
5. [Dependency Injection](#dependency-injection)
6. [Security Architecture](#security-architecture)
7. [Logging & Observability](#logging--observability)
8. [Caching Strategy](#caching-strategy)
9. [Build System](#build-system)
10. [Database Design](#database-design)
11. [API Design](#api-design)
12. [Future Directions](#future-directions)

---

## Architectural Overview

Simple LMS follows a **hybrid architecture** combining WordPress conventions with modern PHP patterns:

```
┌─────────────────────────────────────────────────────┐
│                   WordPress Core                     │
│         (Hooks, Post Types, Taxonomies, Users)       │
└──────────────────────┬──────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────┐
│                  Simple LMS Plugin                   │
│  ┌───────────────────────────────────────────────┐  │
│  │         Service Container (PSR-11)            │  │
│  │  ┌────────────────────────────────────────┐  │  │
│  │  │   Core Services (Logger, Security)     │  │  │
│  │  └────────────────────────────────────────┘  │  │
│  └───────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────┐  │
│  │        Business Logic Layer                   │  │
│  │  • Access Control  • Progress Tracking        │  │
│  │  • Analytics       • WooCommerce Integration  │  │
│  └───────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────┐  │
│  │           Data Access Layer                   │  │
│  │  • Cache Handler  • Database Queries          │  │
│  │  • Post Meta      • User Meta                 │  │
│  └───────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────┐  │
│  │        Presentation Layer                     │  │
│  │  • REST API       • AJAX Handlers             │  │
│  │  • Dynamic Tags   • Admin UI                  │  │
│  │  • Frontend Views • Builder Integrations      │  │
│  └───────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘
```

### Key Components

| Layer | Responsibility | Examples |
|-------|----------------|----------|
| **Service Container** | Dependency management, service lifecycle | `ServiceContainer`, `Logger`, `Security_Service` |
| **Business Logic** | Core functionality, domain rules | `Access_Control`, `Progress_Tracker`, `Analytics_Tracker` |
| **Data Access** | Database operations, caching | `Cache_Handler`, `wpdb` queries, post/user meta |
| **Presentation** | User interaction, data delivery | REST API, AJAX, Dynamic Tags/Widgets, Admin UI |

---

## Design Principles

### 1. WordPress First

**Principle:** Embrace WordPress conventions while adding modern PHP practices.

**Rationale:** Simple LMS must integrate seamlessly with the WordPress ecosystem while maintaining code quality and testability.

**Implementation:**
- Use WordPress post types for courses, modules, lessons
- Leverage WordPress user roles and capabilities
- Follow WordPress coding standards (with PSR enhancements)
- Hook into WordPress lifecycle (`init`, `wp_loaded`, etc.)

**Example:**
```php
// WordPress convention: Custom post type
register_post_type('simple_course', [
    'public' => true,
    'capability_type' => 'course',
    'supports' => ['title', 'editor', 'thumbnail'],
]);

// Modern enhancement: Dependency injection for handler
$cpt_handler = $container->get(Custom_Post_Types::class);
$cpt_handler->register();
```

---

### 2. Dependency Injection Over Static Calls

**Principle:** Prefer constructor injection for testability and flexibility.

**Rationale:** Static methods create hard dependencies and make unit testing difficult. Constructor injection allows easy mocking and promotes loose coupling.

**Migration Path:**
```php
// Phase 1: Legacy static pattern (deprecated)
class Old_Class {
    public static function init() {
        add_action('init', [__CLASS__, 'do_something']);
    }
    
    public static function do_something() {
        error_log('Static logging'); // Hard dependency
    }
}

// Phase 2: Instance-based with optional DI
class New_Class {
    private ?Logger $logger;
    
    public function __construct(?Logger $logger = null) {
        $this->logger = $logger;
    }
    
    public function register(): void {
        add_action('init', [$this, 'do_something']);
    }
    
    public function do_something(): void {
        $this->logger?->info('Action executed'); // Testable
    }
    
    // Backward compatibility shim
    public static function init(): void {
        $instance = new self(SimpleLMS::container()->get(Logger::class));
        $instance->register();
    }
}
```

**Benefits:**
- ✅ Testable (mock Logger in tests)
- ✅ Flexible (swap Logger implementation)
- ✅ Backward compatible (static init() preserved)
- ✅ Observable (centralized logging)

---

### 3. Explicit Over Implicit

**Principle:** Make behavior explicit through type declarations and clear APIs.

**Rationale:** PHP 8.0+ type system prevents bugs and improves IDE support.

**Example:**
```php
// ✅ Explicit: Types, return values, exceptions documented
/**
 * Grant user access to course
 *
 * @param int $user_id  User ID to grant access.
 * @param int $course_id Course ID.
 * @param int $order_id  WooCommerce order ID (0 for manual).
 * @return bool True on success, false on failure.
 * @throws \InvalidArgumentException If user or course doesn't exist.
 */
public function grant_access(int $user_id, int $course_id, int $order_id = 0): bool {
    if (!get_userdata($user_id)) {
        throw new \InvalidArgumentException("User $user_id not found");
    }
    // ...
}

// ❌ Implicit: No types, unclear behavior
public function grant_access($user_id, $course_id, $order_id = 0) {
    // What happens with invalid inputs? Who knows!
}
```

---

### 4. Security by Default

**Principle:** Every user input must be validated, every output must be escaped.

**Rationale:** WordPress plugins are common attack vectors. Security must be built-in, not bolted-on.

**Implementation:**
- Nonce verification for all state-changing operations
- Capability checks before privileged actions
- Input sanitization (integers, text, HTML, URLs)
- Output escaping (`esc_html`, `esc_attr`, `wp_kses`)
- Rate limiting for expensive operations

See [SECURITY.md](SECURITY.md) for comprehensive guidelines.

---

### 5. Performance Conscious

**Principle:** Optimize for real-world performance while maintaining code quality.

**Rationale:** LMS plugins handle large datasets (thousands of users, hundreds of courses). Poor performance creates bad UX.

**Strategies:**
- **Caching**: Object cache for expensive queries (300-600s TTL)
- **Query Optimization**: Indexed meta queries, JOIN over multiple queries
- **Lazy Loading**: Defer expensive operations until needed
- **Rate Limiting**: Prevent abuse (20 lesson completions/min per user)
- **Pagination**: REST API returns max 100 items per page

**Example:**
```php
// ✅ Optimized: Single query with caching
public function get_user_progress(int $user_id, int $course_id): array {
    $cache_key = "progress_{$user_id}_{$course_id}";
    $cached = wp_cache_get($cache_key, 'simple_lms');
    
    if ($cached !== false) {
        return $cached;
    }
    
    global $wpdb;
    $progress = $wpdb->get_results($wpdb->prepare(
        "SELECT lesson_id, completed, completed_at 
         FROM {$wpdb->prefix}lms_progress 
         WHERE user_id = %d AND course_id = %d",
        $user_id,
        $course_id
    ), ARRAY_A);
    
    wp_cache_set($cache_key, $progress, 'simple_lms', 300);
    return $progress;
}

// ❌ Unoptimized: Multiple queries, no caching
public function get_user_progress(int $user_id, int $course_id): array {
    $lessons = get_posts(['post_type' => 'simple_lesson']);
    $progress = [];
    foreach ($lessons as $lesson) {
        $completed = get_user_meta($user_id, "_lesson_{$lesson->ID}_complete", true);
        $progress[] = ['lesson_id' => $lesson->ID, 'completed' => $completed];
    }
    return $progress;
}
```

---

## Core Patterns

### Service Locator Pattern

**Purpose:** Centralized dependency management and service lifecycle.

**Implementation:** `ServiceContainer` (PSR-11 compatible)

```php
namespace SimpleLMS;

class ServiceContainer {
    private static ?self $instance = null;
    private array $services = [];
    private array $factories = [];

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register(string $id, callable $factory): void {
        $this->factories[$id] = $factory;
    }

    public function singleton(string $id, callable $factory): void {
        $this->register($id, function() use ($id, $factory) {
            if (!isset($this->services[$id])) {
                $this->services[$id] = $factory($this);
            }
            return $this->services[$id];
        });
    }

    public function get(string $id) {
        if (!isset($this->factories[$id])) {
            throw new \Exception("Service $id not found");
        }
        return $this->factories[$id]($this);
    }
}
```

**Usage:**
```php
// Bootstrap: Register services
$container = ServiceContainer::getInstance();

$container->singleton(Logger::class, function($c) {
    return new Logger();
});

$container->singleton(Security_Service::class, function($c) {
    return new Security_Service($c->get(Logger::class));
});

$container->register(Progress_Tracker::class, function($c) {
    return new Progress_Tracker($c->get(Logger::class));
});

// Usage: Retrieve services
$logger = $container->get(Logger::class);
$logger->info('Application started');
```

**Benefits:**
- Centralized dependency configuration
- Lazy instantiation (services created on-demand)
- Singleton support (shared instances)
- Testability (mock container in tests)

---

### Repository Pattern

**Purpose:** Abstract data access logic from business logic.

**Implementation:** Implicit through static handlers (future: explicit repositories)

**Current (Implicit):**
```php
// Access_Control acts as implicit repository
class Access_Control {
    public static function grant_access(int $user_id, int $course_id): bool {
        return update_user_meta($user_id, "_course_access_{$course_id}", true);
    }
    
    public static function user_has_access(int $user_id, int $course_id): bool {
        return (bool) get_user_meta($user_id, "_course_access_{$course_id}", true);
    }
}
```

**Future (Explicit Repository):**
```php
interface CourseAccessRepository {
    public function grantAccess(int $userId, int $courseId): bool;
    public function hasAccess(int $userId, int $courseId): bool;
    public function revokeAccess(int $userId, int $courseId): bool;
}

class UserMetaAccessRepository implements CourseAccessRepository {
    public function grantAccess(int $userId, int $courseId): bool {
        return update_user_meta($userId, "_course_access_{$courseId}", true);
    }
    // ...
}
```

**Why Future:**
- Current codebase prioritizes WordPress conventions
- Explicit repositories add complexity without immediate value
- Migration path exists for when needed (e.g., custom tables)

---

### Strategy Pattern

**Purpose:** Swap algorithms/implementations at runtime.

**Example:** Cache backend selection

```php
interface CacheBackend {
    public function get(string $key, string $group): mixed;
    public function set(string $key, mixed $value, string $group, int $ttl): bool;
    public function delete(string $key, string $group): bool;
}

class WordPressCacheBackend implements CacheBackend {
    public function get(string $key, string $group): mixed {
        return wp_cache_get($key, $group);
    }
    // ...
}

class RedisCacheBackend implements CacheBackend {
    private \Redis $redis;
    
    public function __construct(\Redis $redis) {
        $this->redis = $redis;
    }
    
    public function get(string $key, string $group): mixed {
        return $this->redis->get("{$group}:{$key}");
    }
    // ...
}

// Usage: Select backend based on configuration
$backend = extension_loaded('redis')
    ? new RedisCacheBackend($redis)
    : new WordPressCacheBackend();

$cache = new Cache_Handler($backend);
```

---

## Service Container

### Registration

Services registered in `simple-lms.php`:

```php
// Core services (singleton)
$container->singleton(Logger::class, function($c) {
    $debug = defined('WP_DEBUG') && WP_DEBUG;
    $verboseOpt = get_option('simple_lms_verbose_logging', false);
    $debugEnabled = (bool) apply_filters('simple_lms_debug_enabled', ($debug || $verboseOpt));
    return new Logger($debugEnabled);
});

$container->singleton(Security_Service::class, function($c) {
    return new Security_Service($c->get(Logger::class));
});

// Business logic (factory - new instance each time)
$container->register(Progress_Tracker::class, function($c) {
    return new Progress_Tracker($c->get(Logger::class));
});

$container->register(WooCommerce_Integration::class, function($c) {
    return new WooCommerce_Integration(
        $c->get(Logger::class),
        $c->get(Security_Service::class)
    );
});
```

### Singleton vs Factory

| Pattern | When to Use | Example |
|---------|-------------|---------|
| **Singleton** | Stateless services, shared configuration | `Logger`, `Security_Service`, `Cache_Handler` |
| **Factory** | Stateful objects, per-request instances | `Progress_Tracker` (if holding state), domain models |

---

## Dependency Injection

### Constructor Injection

**Preferred method** for all dependencies:

```php
class Analytics_Tracker {
    public function __construct(
        private Logger $logger,
        private Security_Service $security
    ) {}
    
    public function track_event(string $event_type, int $user_id, array $data): void {
        // Verify permissions
        if (!$this->security->current_user_can('read')) {
            $this->logger->warning('Unauthorized analytics tracking attempt', [
                'user_id' => $user_id,
                'event' => $event_type,
            ]);
            return;
        }
        
        // Track event
        $this->logger->info('Analytics event tracked', [
            'type' => $event_type,
            'user' => $user_id,
        ]);
        
        do_action('simple_lms_analytics_event', $event_type, $user_id, $data);
    }
}
```

**Benefits:**
- Dependencies explicit in constructor signature
- All dependencies available throughout object lifecycle
- Easy to mock in tests
- IDE autocomplete support

### Method Injection

**Use when:** Dependency only needed for specific method, varies per call

```php
class Report_Generator {
    public function generate(ReportConfig $config, OutputFormatter $formatter): string {
        $data = $this->fetch_data($config);
        return $formatter->format($data);
    }
}

// Usage with different formatters
$generator = new Report_Generator();
$pdf = $generator->generate($config, new PdfFormatter());
$csv = $generator->generate($config, new CsvFormatter());
```

### Property Injection

**Avoid:** Makes dependencies unclear, breaks immutability

```php
// ❌ Bad: Property injection
class BadExample {
    public $logger; // Public mutable property
    
    public function doSomething() {
        $this->logger->info('...');
    }
}

$bad = new BadExample();
$bad->logger = new Logger(); // Required but not enforced

// ✅ Good: Constructor injection
class GoodExample {
    public function __construct(private Logger $logger) {}
    
    public function doSomething() {
        $this->logger->info('...');
    }
}

$good = new GoodExample(new Logger()); // Enforced at construction
```

---

## Security Architecture

### Defense in Depth

Multiple security layers protect against attacks:

```
User Request
    │
    ▼
┌─────────────────────────────┐
│   1. Nonce Verification     │ ← CSRF Protection
└─────────────┬───────────────┘
              ▼
┌─────────────────────────────┐
│   2. Authentication Check   │ ← User logged in?
└─────────────┬───────────────┘
              ▼
┌─────────────────────────────┐
│   3. Capability Check       │ ← User has permission?
└─────────────┬───────────────┘
              ▼
┌─────────────────────────────┐
│   4. Input Validation       │ ← Data type/format correct?
└─────────────┬───────────────┘
              ▼
┌─────────────────────────────┐
│   5. Input Sanitization     │ ← Remove harmful data
└─────────────┬───────────────┘
              ▼
┌─────────────────────────────┐
│   6. Business Logic         │ ← Process request
└─────────────┬───────────────┘
              ▼
┌─────────────────────────────┐
│   7. Output Escaping        │ ← Prevent XSS
└─────────────┬───────────────┘
              ▼
          Response
```

### Security_Service

Centralized security operations:

```php
class Security_Service {
    private const NONCE_ACTION_BASE = 'simple_lms';
    
    /**
     * Create contextual nonce
     *
     * @param string $context Context (rest, ajax, frontend).
     * @return string Nonce value.
     */
    public function create_nonce(string $context): string {
        $action = apply_filters(
            'simple_lms_nonce_action',
            self::NONCE_ACTION_BASE . '_' . $context,
            $context
        );
        return wp_create_nonce($action);
    }
    
    /**
     * Verify contextual nonce
     *
     * @param string $nonce   Nonce value to verify.
     * @param string $context Context (rest, ajax, frontend).
     * @return bool True if valid.
     */
    public function verify_nonce(string $nonce, string $context): bool {
        $action = apply_filters(
            'simple_lms_nonce_action',
            self::NONCE_ACTION_BASE . '_' . $context,
            $context
        );
        return (bool) wp_verify_nonce($nonce, $action);
    }
    
    /**
     * Assert user has capability
     *
     * @param string $capability Required capability.
     * @param int    $user_id    User ID (0 = current user).
     * @return bool True if user has capability.
     */
    public function current_user_can(string $capability, int $user_id = 0): bool {
        if ($user_id === 0) {
            return current_user_can($capability);
        }
        
        $user = get_userdata($user_id);
        return $user && $user->has_cap($capability);
    }
}
```

**Usage in AJAX:**
```php
public function handle_ajax_request(): void {
    // 1. Nonce
    if (!$this->security->verify_nonce($_POST['nonce'] ?? '', 'ajax')) {
        wp_send_json_error('Invalid nonce');
    }
    
    // 2. Authentication
    if (!is_user_logged_in()) {
        wp_send_json_error('Not authenticated');
    }
    
    // 3. Capability
    if (!$this->security->current_user_can('edit_courses')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    // 4-5. Validate & sanitize
    $course_id = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;
    if ($course_id === 0) {
        wp_send_json_error('Invalid course ID');
    }
    
    // 6. Process
    $result = $this->process_course($course_id);
    
    // 7. Output (JSON auto-escaped by wp_send_json_success)
    wp_send_json_success($result);
}
```

---

## Logging & Observability

### Structured Logging

PSR-3-inspired Logger with context:

```php
class Logger {
    private bool $debugEnabled;
    
    public function __construct(bool $debugEnabled = false) {
        $this->debugEnabled = $debugEnabled;
    }
    
    public function info(string $message, array $context = []): void {
        if (!$this->debugEnabled) {
            return;
        }
        
        $formatted = $this->interpolate($message, $context);
        error_log("[SimpleLMS] INFO: {$formatted}");
    }
    
    private function interpolate(string $message, array $context): string {
        $replace = [];
        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = is_scalar($val) 
                ? (string) $val 
                : json_encode($val);
        }
        return strtr($message, $replace);
    }
}
```

**Usage:**
```php
$this->logger->info('User enrolled in course', [
    'user_id' => $user_id,
    'course_id' => $course_id,
    'order_id' => $order_id,
    'timestamp' => current_time('mysql'),
]);

// Output: [SimpleLMS] INFO: User enrolled in course {"user_id":123,"course_id":456,"order_id":789,"timestamp":"2025-12-02 10:30:00"}
```

### Log Levels

| Level | Purpose | Example |
|-------|---------|---------|
| `debug` | Development debugging | Variable dumps, execution flow |
| `info` | Informational events | User actions, successful operations |
| `warning` | Potential issues | Deprecated function use, retryable failures |
| `error` | Error conditions | Failed operations, exceptions |

### Enabling Verbose Logging

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Or via filter
add_filter('simple_lms_debug_enabled', '__return_true');

// Or via option
update_option('simple_lms_verbose_logging', true);
```

---

## Caching Strategy

### Cache Hierarchy

```
┌─────────────────────────────┐
│   Object Cache (Redis/      │  ← 1st: Fastest, volatile
│   Memcached/APCu)            │
└─────────────┬───────────────┘
              │ Miss
              ▼
┌─────────────────────────────┐
│   WordPress Transients      │  ← 2nd: Persistent, per-site
└─────────────┬───────────────┘
              │ Miss
              ▼
┌─────────────────────────────┐
│   Database Query            │  ← 3rd: Source of truth
└─────────────────────────────┘
```

### Cache Key Strategy

**Versioned keys** for instant invalidation:

```php
class Cache_Handler {
    private const VERSION_OPTION = 'simple_lms_cache_version';
    
    public static function generate_cache_key(string $prefix, int $id): string {
        $version = get_option(self::VERSION_OPTION, 1);
        $lang = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : '';
        
        return "{$prefix}_{$id}_v{$version}" . ($lang ? "_{$lang}" : '');
    }
    
    public static function increment_cache_version(): void {
        $current = (int) get_option(self::VERSION_OPTION, 1);
        update_option(self::VERSION_OPTION, $current + 1);
    }
}
```

**Benefits:**
- No need to delete individual keys
- Instant global invalidation
- Multilingual support built-in

### Cache TTL Guidelines

| Data Type | TTL | Rationale |
|-----------|-----|-----------|
| **User Progress** | 300s (5min) | Frequently updated during learning sessions |
| **Course Statistics** | 600s (10min) | Updated less frequently, expensive to calculate |
| **Post Content** | 3600s (1hr) | Rarely changes after publication |
| **Global Settings** | 86400s (24hr) | Admin-controlled, infrequent changes |

### Cache Invalidation

**Events that trigger invalidation:**

```php
// On post save (course/module/lesson)
add_action('save_post', function($post_id) {
    if (in_array(get_post_type($post_id), ['simple_course', 'simple_module', 'simple_lesson'])) {
        Cache_Handler::increment_cache_version();
    }
});

// On user progress update
add_action('simple_lms_lesson_progress_updated', function($user_id, $lesson_id) {
    Progress_Tracker::clearProgressCache($user_id);
});

// Manual flush (admin action)
add_action('admin_post_simple_lms_flush_cache', function() {
    check_admin_referer('simple_lms_flush_cache');
    
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }
    
    Cache_Handler::increment_cache_version();
    wp_cache_flush();
    
    wp_redirect(add_query_arg('cache_flushed', '1', wp_get_referer()));
});
```

---

## Build System

### Vite Configuration

Modern asset bundling with **Vite 5**:

```javascript
// vite.config.js
import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
    build: {
        outDir: 'assets/dist',
        emptyOutDir: true,
        manifest: true,
        rollupOptions: {
            input: {
                admin: path.resolve(__dirname, 'assets/src/js/admin.js'),
                frontend: path.resolve(__dirname, 'assets/src/js/frontend.js'),
                'admin-style': path.resolve(__dirname, 'assets/src/css/admin.css'),
                'frontend-style': path.resolve(__dirname, 'assets/src/css/frontend.css'),
            },
            output: {
                entryFileNames: '[name].[hash].js',
                chunkFileNames: 'chunks/[name].[hash].js',
                assetFileNames: 'assets/[name].[hash][extname]',
            },
        },
    },
    css: {
        postcss: {
            plugins: [
                require('autoprefixer'),
            ],
        },
    },
});
```

### Build Process

```powershell
# Development (watch mode)
npm run dev

# Production build
npm run build
```

### Asset Versioning

**Manifest-based loading:**

```php
private function getAssetVersion(string $file): string {
    static $manifest = null;
    
    if ($manifest === null) {
        $manifest_path = SIMPLE_LMS_PLUGIN_DIR . 'assets/dist/manifest.json';
        $manifest = file_exists($manifest_path)
            ? json_decode(file_get_contents($manifest_path), true)
            : [];
    }
    
    return $manifest[$file]['file'] ?? $file;
}

// Usage
wp_enqueue_script(
    'simple-lms-admin',
    SIMPLE_LMS_PLUGIN_URL . 'assets/dist/' . $this->getAssetVersion('admin.js'),
    ['jquery'],
    null, // Version handled by filename hash
    true
);
```

**Benefits:**
- Cache-busting via content hashes
- No manual version updates needed
- Efficient browser caching

---

## Database Design

### Post Types

| Post Type | Purpose | Meta Fields (excerpt) |
|-----------|---------|----------------------|
| `simple_course` | LMS Course | `_course_modules` (module IDs), `_course_difficulty`, `_woo_product_ids` |
| `simple_module` | Course Module | `_parent_course` (course ID), `_module_lessons` (lesson IDs), `_module_order` |
| `simple_lesson` | Lesson Content | `_parent_module` (module ID), `_parent_course` (course ID), `_lesson_duration` |

### User Meta

| Meta Key | Type | Purpose |
|----------|------|---------|
| `_course_access_{course_id}` | bool | User has access to course |
| `_lesson_{lesson_id}_progress` | int | Lesson completion percentage (0-100) |
| `_lesson_{lesson_id}_completed` | bool | Lesson fully completed |
| `_course_{course_id}_last_accessed` | timestamp | Last activity in course |

### Custom Tables

**Analytics Events:**

```sql
CREATE TABLE {$wpdb->prefix}simple_lms_analytics (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    event_data JSON,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_user_event (user_id, event_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Query Optimization

**Indexed meta queries:**

```php
global $wpdb;

// ✅ Optimized: Uses meta_key index
$courses_with_access = $wpdb->get_col($wpdb->prepare(
    "SELECT meta_value FROM {$wpdb->usermeta}
     WHERE user_id = %d 
     AND meta_key LIKE '_course_access_%%'
     AND meta_value = '1'",
    $user_id
));

// ❌ Slow: Full table scan
$courses_with_access = [];
$all_meta = get_user_meta($user_id);
foreach ($all_meta as $key => $value) {
    if (strpos($key, '_course_access_') === 0 && $value[0] === '1') {
        $courses_with_access[] = str_replace('_course_access_', '', $key);
    }
}
```

---

## API Design

### REST API

**Namespace:** `simple-lms/v1`

**Endpoint Structure:**

```
GET    /courses              - List courses
GET    /courses/{id}         - Get single course
POST   /courses              - Create course (admin)
PUT    /courses/{id}         - Update course (admin)
DELETE /courses/{id}         - Delete course (admin)

GET    /courses/{id}/modules - List course modules
GET    /lessons/{id}         - Get lesson content
POST   /progress/complete    - Mark lesson complete
GET    /progress/{course_id} - Get user progress
```

**Permissions:**

```php
register_rest_route('simple-lms/v1', '/courses', [
    'methods' => 'GET',
    'callback' => [$this, 'get_courses'],
    'permission_callback' => '__return_true', // Public
]);

register_rest_route('simple-lms/v1', '/courses', [
    'methods' => 'POST',
    'callback' => [$this, 'create_course'],
    'permission_callback' => function() {
        return current_user_can('publish_courses'); // Requires capability
    },
]);
```

**Responses:**

```json
// Success
{
    "success": true,
    "data": {
        "course": {
            "id": 123,
            "title": "Introduction to PHP",
            "modules": [...]
        }
    }
}

// Error
{
    "success": false,
    "message": "Course not found",
    "code": "course_not_found",
    "data": {
        "status": 404
    }
}
```

### AJAX Handlers

**Action Naming:** `wp_ajax_{action}` and `wp_ajax_nopriv_{action}`

```php
add_action('wp_ajax_simple_lms_complete_lesson', [$this, 'ajax_complete_lesson']);

public function ajax_complete_lesson(): void {
    // Security checks
    if (!$this->security->verify_nonce($_POST['nonce'] ?? '', 'ajax')) {
        wp_send_json_error('Invalid nonce');
    }
    
    if (!$this->security->current_user_can('read')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    // Process
    $lesson_id = isset($_POST['lesson_id']) ? absint($_POST['lesson_id']) : 0;
    $result = Progress_Tracker::mark_lesson_complete(get_current_user_id(), $lesson_id);
    
    // Respond
    wp_send_json_success([
        'completed' => $result,
        'progress' => Progress_Tracker::get_course_progress(
            get_current_user_id(),
            get_post_meta($lesson_id, '_parent_course', true)
        ),
    ]);
}
```

---

## Future Directions

### Planned Improvements

#### 1. Explicit Repository Layer

**Goal:** Decouple business logic from WordPress data access

**Implementation:**
```php
interface CourseRepository {
    public function find(int $id): ?Course;
    public function findAll(array $filters = []): array;
    public function save(Course $course): bool;
    public function delete(int $id): bool;
}

class WP_Course_Repository implements CourseRepository {
    public function find(int $id): ?Course {
        $post = get_post($id);
        return $post ? Course::fromPost($post) : null;
    }
    // ...
}
```

**Benefits:**
- Easier migration to custom tables
- Testable without WordPress
- Clear data access contracts

---

#### 2. Event Sourcing for Progress

**Goal:** Audit trail of all learning activities

**Implementation:**
```php
class ProgressEventStore {
    public function append(ProgressEvent $event): void {
        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}lms_events", [
            'aggregate_id' => $event->userId . ':' . $event->courseId,
            'event_type' => $event->type,
            'event_data' => json_encode($event->data),
            'occurred_at' => current_time('mysql'),
        ]);
    }
    
    public function getStream(int $userId, int $courseId): array {
        // Replay all events for user/course
    }
}
```

**Benefits:**
- Complete audit trail
- Time-travel debugging (replay past states)
- Analytics insights (learning patterns)

---

#### 3. GraphQL API

**Goal:** Flexible querying for modern frontends

**Implementation:**
```graphql
type Course {
  id: ID!
  title: String!
  modules: [Module!]!
  progress(userId: ID!): Progress
}

type Query {
  course(id: ID!): Course
  courses(filter: CourseFilter): [Course!]!
}

type Mutation {
  completeLesson(lessonId: ID!): Progress!
}
```

**Benefits:**
- Efficient data fetching (no over/under-fetching)
- Better DX for React/Vue apps
- Real-time subscriptions possible

---

#### 4. Microservices (Optional)

**Goal:** Scale independently (analytics, video processing)

**Architecture:**
```
┌──────────────┐       ┌──────────────┐
│   WordPress  │──────▶│   Analytics  │
│   (Core LMS) │       │   Service    │
└──────────────┘       └──────────────┘
       │
       │
       ▼
┌──────────────┐       ┌──────────────┐
│  User Data   │       │    Video     │
│   Service    │──────▶│  Processing  │
└──────────────┘       │   Service    │
                       └──────────────┘
```

**Benefits:**
- Independent scaling (analytics high-traffic)
- Technology flexibility (Python for ML)
- Fault isolation

---

## Related Documentation

- [SECURITY.md](SECURITY.md) - Security architecture details
- [HOOKS.md](HOOKS.md) - Extensibility points
- [TESTING.md](TESTING.md) - Testing strategy
- [CONTRIBUTING.md](CONTRIBUTING.md) - Development workflow

---

**Last Updated:** December 2, 2025  
**Plugin Version:** 1.4.0
