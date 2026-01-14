# Simple LMS API Reference

**Version:** 1.3.2  
**Last Updated:** 2025-11-30

## Table of Contents
1. [Core Classes](#core-classes)
2. [REST API Endpoints](#rest-api-endpoints)
3. [Hooks & Filters](#hooks--filters)
4. [Helper Functions](#helper-functions)
5. [Database Schema](#database-schema)

---

## Core Classes

### SimpleLMS\Access_Control

Manages course access control, drip content scheduling, and user permissions.

#### Public Methods

##### `init(): void`
Initializes access control hooks and shortcodes.

**Usage:**
```php
\SimpleLMS\Access_Control::init();
```

---

##### `userHasCourseAccess(int $user_id, int $course_id): bool`
Checks if a user has access to a course (with admin bypass).

**Parameters:**
- `$user_id` (int) - User ID to check
- `$course_id` (int) - Course ID to check

**Returns:** `bool` - True if user has access or is admin

**Example:**
```php
$has_access = \SimpleLMS\Access_Control::userHasCourseAccess(123, 456);
if ($has_access) {
    // Grant access to course content
}
```

---

##### `userHasAccessToLesson(int $lesson_id): bool`
Checks if current user has access to a specific lesson (includes drip schedule check).

**Parameters:**
- `$lesson_id` (int) - Lesson post ID

**Returns:** `bool` - True if user can access the lesson

**Example:**
```php
if (\SimpleLMS\Access_Control::userHasAccessToLesson(789)) {
    // Display lesson content
} else {
    // Show locked message
}
```

---

##### `isModuleUnlocked(int $module_id): bool`
Evaluates drip schedule rules to determine if module is unlocked for current user.

**Parameters:**
- `$module_id` (int) - Module post ID

**Returns:** `bool` - True if module is unlocked

**Drip Modes:**
- `purchase` - Immediate access
- `fixed_date` - Unlocks on specific date
- `drip` - Time-based unlocking (days from purchase, interval, or manual)

**Example:**
```php
$is_unlocked = \SimpleLMS\Access_Control::isModuleUnlocked(555);
if (!$is_unlocked) {
    $info = \SimpleLMS\Access_Control::getModuleUnlockInfo(555);
    echo "Unlocks on: " . date('Y-m-d', $info['unlock_ts']);
}
```

---

### SimpleLMS\Progress_Tracker

Manages user progress tracking through courses, modules, and lessons.

#### Public Methods

##### `updateLessonProgress(int $userId, int $lessonId, bool $completed = true, int $timeSpent = 0): bool`
Updates lesson progress for a user.

**Parameters:**
- `$userId` (int) - User ID
- `$lessonId` (int) - Lesson ID
- `$completed` (bool) - Whether lesson is completed (default: true)
- `$timeSpent` (int) - Time spent in seconds (default: 0)

**Returns:** `bool` - Success status

**Fires:** `simple_lms_lesson_progress_updated` action

**Example:**
```php
$success = \SimpleLMS\Progress_Tracker::updateLessonProgress(
    get_current_user_id(),
    456,
    true,
    1800 // 30 minutes
);
```

---

##### `getUserProgress(int $userId, int $courseId = 0): array`
Gets user progress for specific course or all courses.

**Parameters:**
- `$userId` (int) - User ID
- `$courseId` (int) - Course ID (optional, 0 = all courses)

**Returns:** `array` - Progress data structure:
```php
[
    'user_id' => 123,
    'overall_progress' => [
        [
            'course_id' => 456,
            'total_lessons' => 10,
            'completed_lessons' => 7,
            'completion_percentage' => 70.0,
            'total_time_spent' => 5400,
            'last_activity' => '2025-11-30 12:00:00'
        ]
    ],
    'lessons' => [
        [
            'lesson_id' => 789,
            'completed' => 1,
            'completion_date' => '2025-11-30 10:00:00',
            'time_spent' => 600
        ]
    ],
    'summary' => [
        'total_courses' => 2,
        'avg_completion' => 65.5
    ]
]
```

**Cached:** 5 minutes (filterable via `simple_lms_progress_cache_ttl`)

---

##### `getTotalLessonsCount(int $courseId): int`
Gets total number of lessons in a course.

**Parameters:**
- `$courseId` (int) - Course ID

**Returns:** `int` - Total lessons count

**Example:**
```php
$total = \SimpleLMS\Progress_Tracker::getTotalLessonsCount(456);
echo "Course has {$total} lessons";
```

---

##### `getCompletedLessonsCount(int $userId, int $courseId): int`
Gets number of completed lessons for a user in a course.

**Parameters:**
- `$userId` (int) - User ID
- `$courseId` (int) - Course ID

**Returns:** `int` - Completed lessons count

---

##### `getCourseProgress(int $userId, int $courseId): int`
Gets course completion percentage (0-100).

**Parameters:**
- `$userId` (int) - User ID
- `$courseId` (int) - Course ID

**Returns:** `int` - Percentage completed (rounded)

**Example:**
```php
$progress = \SimpleLMS\Progress_Tracker::getCourseProgress(123, 456);
echo "Course is {$progress}% complete";
```

---

##### `getLastViewedLesson(int $userId, int $courseId): int`
Gets the last viewed/updated lesson for user in a course.

**Parameters:**
- `$userId` (int) - User ID
- `$courseId` (int) - Course ID

**Returns:** `int` - Lesson ID or 0 if none

**Example:**
```php
$last_lesson = \SimpleLMS\Progress_Tracker::getLastViewedLesson(123, 456);
if ($last_lesson > 0) {
    echo '<a href="' . get_permalink($last_lesson) . '">Continue Learning</a>';
}
```

---

### SimpleLMS\Cache_Handler

Optimizes performance by caching course structure and stats.

#### Constants
- `CACHE_GROUP = 'simple_lms'` - WordPress object cache group

#### Public Methods

##### `getCourseModules(int $courseId): array`
Gets all modules for a course (cached).

**Parameters:**
- `$courseId` (int) - Course ID

**Returns:** `array` - Array of WP_Post objects (modules)

**Cached:** 12 hours (filterable)

---

##### `getModuleLessons(int $moduleId): array`
Gets all lessons for a module (cached).

**Parameters:**
- `$moduleId` (int) - Module ID

**Returns:** `array` - Array of WP_Post objects (lessons)

**Cached:** 12 hours (filterable)

---

##### `getCourseStats(int $courseId): array`
Gets course statistics (module/lesson counts).

**Parameters:**
- `$courseId` (int) - Course ID

**Returns:** `array` - Stats structure:
```php
[
    'module_count' => 5,
    'lesson_count' => 25,
    'total_duration' => 7200 // seconds
]
```

**Cached:** 12 hours (filterable)

---

##### `flushCourseCache(int $postId): void`
Clears cache for a course and related content.

**Parameters:**
- `$postId` (int) - Post ID (course/module/lesson)

**Fires:** Automatically on `save_post` and `deleted_post`

---

### SimpleLMS\Analytics_Tracker

Tracks user learning events and integrates with external analytics platforms.

#### Constants
- `EVENT_LESSON_STARTED` - Lesson opened by user
- `EVENT_LESSON_COMPLETED` - Lesson marked complete
- `EVENT_VIDEO_WATCHED` - Video playback tracked
- `EVENT_COURSE_ENROLLED` - User enrolled in course
- `EVENT_COURSE_PROGRESS_MILESTONE` - 25%, 50%, 75%, 100% milestones
- `EVENT_QUIZ_COMPLETED` - Quiz completion (future)

#### Public Methods

##### `init(): void`
Initializes analytics tracking hooks.

---

##### `track_event(string $event_type, int $user_id, array $data = []): bool`
Tracks a custom event.

**Parameters:**
- `$event_type` (string) - Event type constant
- `$user_id` (int) - User ID
- `$data` (array) - Additional event data

**Returns:** `bool` - Success status

**Fires:** 
- `simple_lms_analytics_event` action
- `simple_lms_analytics_{$event_type}` action

**Example:**
```php
\SimpleLMS\Analytics_Tracker::track_event(
    \SimpleLMS\Analytics_Tracker::EVENT_VIDEO_WATCHED,
    get_current_user_id(),
    [
        'video_url' => 'https://youtube.com/watch?v=...',
        'lesson_id' => 123,
        'watch_duration' => 450 // seconds
    ]
);
```

---

##### `get_user_analytics_data(int $user_id, ?string $event_type = null, int $limit = 100): array`
Gets user analytics data.

**Parameters:**
- `$user_id` (int) - User ID
- `$event_type` (string|null) - Optional event filter
- `$limit` (int) - Max events to retrieve (default: 100)

**Returns:** `array` - Array of events with decoded JSON data

**Example:**
```php
// Get all events for user
$events = \SimpleLMS\Analytics_Tracker::get_user_analytics_data(123);

// Get only lesson completions
$completions = \SimpleLMS\Analytics_Tracker::get_user_analytics_data(
    123,
    \SimpleLMS\Analytics_Tracker::EVENT_LESSON_COMPLETED,
    50
);
```

---

##### `get_course_analytics(int $course_id): array`
Gets analytics summary for a course.

**Parameters:**
- `$course_id` (int) - Course ID

**Returns:** `array` - Summary structure:
```php
[
    'course_id' => 456,
    'total_events' => 1250,
    'unique_users' => 45,
    'events_by_type' => [
        'lesson_started' => [
            'count' => 500,
            'unique_users' => 45
        ],
        'lesson_completed' => [
            'count' => 400,
            'unique_users' => 42
        ]
    ]
]
```

---

##### `send_to_ga4(string $event_type, int $user_id, array $data): bool`
Sends event to Google Analytics 4 (if configured).

**Parameters:**
- `$event_type` (string) - Event type
- `$user_id` (int) - User ID
- `$data` (array) - Event data

**Returns:** `bool` - Success status

**Requires:** Settings configured in WordPress admin (GA4 Measurement ID + API Secret)

---

### SimpleLMS\WooCommerce_Integration

Integrates WooCommerce for course purchasing and automatic access grants.

#### Public Methods

##### `init(): void`
Initializes WooCommerce integration hooks.

---

##### `is_woocommerce_active(): bool`
Checks if WooCommerce is active.

**Returns:** `bool` - True if WooCommerce is active

---

##### `grant_user_course_access(int $order_id): void`
Grants course access to user after successful purchase.

**Parameters:**
- `$order_id` (int) - WooCommerce order ID

**Fires:** Automatically on order completion (`woocommerce_order_status_completed`)

**Example (manual):**
```php
\SimpleLMS\WooCommerce_Integration::grant_user_course_access(12345);
```

---

##### `get_purchase_url_for_course(int $course_id): string`
Gets WooCommerce product URL for course purchase.

**Parameters:**
- `$course_id` (int) - Course ID

**Returns:** `string` - Product URL or empty string if no product linked

**Example:**
```php
$url = \SimpleLMS\WooCommerce_Integration::get_purchase_url_for_course(456);
if ($url) {
    echo '<a href="' . esc_url($url) . '">Purchase Course</a>';
}
```

---

## Helper Functions

### Access Control Functions

#### `simple_lms_get_course_access_meta_key(): string`
Returns the user meta key for course access storage.

**Returns:** `'simple_lms_course_access'`

---

#### `simple_lms_assign_course_access_tag(int $user_id, int $course_id): bool`
Grants a user access to a course.

**Parameters:**
- `$user_id` (int) - User ID
- `$course_id` (int) - Course ID

**Returns:** `bool` - Success status

**Example:**
```php
$success = simple_lms_assign_course_access_tag(123, 456);
if ($success) {
    wp_send_json_success(['message' => 'Access granted']);
}
```

---

#### `simple_lms_remove_course_access_tag(int $user_id, int $course_id): bool`
Revokes user access to a course.

**Parameters:**
- `$user_id` (int) - User ID
- `$course_id` (int) - Course ID

**Returns:** `bool` - Success status

---

#### `simple_lms_user_has_course_access(int $user_id, int $course_id): bool`
Checks if user has access (without admin bypass).

**Parameters:**
- `$user_id` (int) - User ID
- `$course_id` (int) - Course ID

**Returns:** `bool` - True if user has valid access

**Cached:** 12 hours via transient

**Filterable:** `simple_lms_user_has_course_access` filter allows override

---

#### `simple_lms_get_course_access_expiration(int $user_id, int $course_id): ?int`
Gets user's course access expiration timestamp.

**Parameters:**
- `$user_id` (int) - User ID
- `$course_id` (int) - Course ID

**Returns:** `int|null` - Expiration timestamp or null if unlimited

---

#### `simple_lms_get_course_access_days_remaining(int $user_id, int $course_id): ?int`
Gets days remaining for course access.

**Parameters:**
- `$user_id` (int) - User ID
- `$course_id` (int) - Course ID

**Returns:** `int|null` - Days remaining, 0 if expired, null if unlimited

---

## REST API Endpoints

**Base URL:** `/wp-json/simple-lms/v1`  
**Authentication:** WordPress cookie or Application Password

### Courses

#### GET `/courses`
Lists all published courses.

**Response:**
```json
[
  {
    "id": 456,
    "title": "WordPress Development Course",
    "slug": "wp-dev-course",
    "description": "Learn WordPress development",
    "module_count": 5,
    "lesson_count": 25,
    "user_has_access": true
  }
]
```

---

#### GET `/courses/{id}`
Gets single course details.

**Parameters:**
- `id` (int) - Course ID

**Response:**
```json
{
  "id": 456,
  "title": "WordPress Development Course",
  "content": "<p>Course description...</p>",
  "modules": [
    {
      "id": 789,
      "title": "Introduction",
      "lesson_count": 5
    }
  ],
  "user_has_access": true,
  "purchase_url": "https://example.com/product/wp-course"
}
```

---

#### GET `/courses/{course_id}/modules`
Gets all modules for a course.

**Parameters:**
- `course_id` (int) - Course ID

**Response:**
```json
[
  {
    "id": 789,
    "title": "Introduction",
    "order": 0,
    "lesson_count": 5,
    "is_unlocked": true
  }
]
```

---

### Modules

#### GET `/modules/{id}`
Gets single module details.

**Parameters:**
- `id` (int) - Module ID

**Response:**
```json
{
  "id": 789,
  "title": "Introduction",
  "content": "<p>Module description...</p>",
  "parent_course": 456,
  "lessons": [...],
  "is_unlocked": true
}
```

---

#### GET `/modules/{module_id}/lessons`
Gets all lessons for a module.

**Response:**
```json
[
  {
    "id": 1011,
    "title": "Getting Started",
    "order": 0,
    "is_completed": false,
    "video_url": "https://youtube.com/watch?v=..."
  }
]
```

---

### Lessons

#### GET `/lessons/{id}`
Gets single lesson details.

**Response:**
```json
{
  "id": 1011,
  "title": "Getting Started",
  "content": "<p>Lesson content...</p>",
  "video_url": "https://youtube.com/watch?v=...",
  "attachments": [...],
  "is_completed": false,
  "has_access": true
}
```

---

### Progress Tracking

#### GET `/progress/{user_id}`
Gets user progress across all courses.

**Parameters:**
- `user_id` (int) - User ID (must be current user or admin)

**Response:**
```json
{
  "user_id": 123,
  "courses": [
    {
      "course_id": 456,
      "completed_lessons": 15,
      "total_lessons": 25,
      "percentage": 60
    }
  ]
}
```

---

#### POST `/progress/{user_id}/{lesson_id}`
Marks lesson as complete/incomplete.

**Parameters:**
- `user_id` (int) - User ID
- `lesson_id` (int) - Lesson ID

**Body:**
```json
{
  "completed": true,
  "time_spent": 1800
}
```

**Response:**
```json
{
  "success": true,
  "message": "Progress updated"
}
```

---

## Hooks & Filters

### Actions

#### `simple_lms_lesson_progress_updated`
Fires after lesson progress is updated.

**Parameters:**
- `$user_id` (int) - User ID
- `$lesson_id` (int) - Lesson ID
- `$completed` (bool) - Completion status

**Example:**
```php
add_action('simple_lms_lesson_progress_updated', function($user_id, $lesson_id, $completed) {
    if ($completed) {
        // Send notification, award points, etc.
        error_log("User {$user_id} completed lesson {$lesson_id}");
    }
}, 10, 3);
```

---

#### `simple_lms_progress_cache_cleared`
Fires after progress cache is cleared for a user/course.

**Parameters:**
- `$user_id` (int) - User ID
- `$course_id` (int) - Course ID

**Example:**
```php
add_action('simple_lms_progress_cache_cleared', function($user_id, $course_id) {
    // Refresh external dashboards, trigger webhooks, etc.
}, 10, 2);
```

---

### Filters

#### `simple_lms_user_has_course_access`
Allows override of course access logic.

**Parameters:**
- `$has_access` (bool) - Computed access status
- `$user_id` (int) - User ID
- `$course_id` (int) - Course ID

**Returns:** `bool` - Modified access status

**Example:**
```php
add_filter('simple_lms_user_has_course_access', function($has_access, $user_id, $course_id) {
    // Grant VIP users access to all courses
    if (user_has_vip_membership($user_id)) {
        return true;
    }
    return $has_access;
}, 10, 3);
```

---

#### `simple_lms_progress_cache_ttl`
Controls cache TTL for progress data.

**Parameters:**
- `$ttl` (int) - Default TTL in seconds (300 = 5 minutes)
- `$user_id` (int) - User ID
- `$course_id` (int) - Course ID

**Returns:** `int` - Modified TTL

**Example:**
```php
add_filter('simple_lms_progress_cache_ttl', function($ttl, $user_id, $course_id) {
    // Shorter cache for premium users
    if (user_is_premium($user_id)) {
        return 60; // 1 minute
    }
    return $ttl;
}, 10, 3);
```

---

#### `simple_lms_course_stats_cache_ttl`
Controls cache TTL for course statistics.

**Parameters:**
- `$ttl` (int) - Default TTL in seconds (600 = 10 minutes)
- `$course_id` (int) - Course ID

**Returns:** `int` - Modified TTL

---

#### `simple_lms_cache_expiration`
Controls default cache expiration for course structure.

**Parameters:**
- `$expiration` (int) - Default expiration in seconds (43200 = 12 hours)

**Returns:** `int` - Modified expiration

**Example:**
```php
add_filter('simple_lms_cache_expiration', function($expiration) {
    return DAY_IN_SECONDS; // 24 hours
});
```

---

### Analytics Actions

#### `simple_lms_analytics_event`
Fires when any analytics event is tracked.

**Parameters:**
- `$event_type` (string) - Event type constant
- `$user_id` (int) - User ID
- `$data` (array) - Event data
- `$event_id` (int|false) - Database row ID (or false if DB disabled)

**Example:**
```php
add_action('simple_lms_analytics_event', function($event_type, $user_id, $data, $event_id) {
    // Send to custom analytics platform
    if ($event_type === \SimpleLMS\Analytics_Tracker::EVENT_LESSON_COMPLETED) {
        MyAnalytics::track('lesson_complete', [
            'user' => $user_id,
            'lesson' => $data['lesson_id'],
            'timestamp' => time()
        ]);
    }
}, 10, 4);
```

---

#### `simple_lms_analytics_{event_type}`
Fires for specific event types (lesson_started, lesson_completed, etc.).

**Parameters:**
- `$user_id` (int) - User ID
- `$data` (array) - Event data
- `$event_id` (int|false) - Database row ID

**Example:**
```php
add_action('simple_lms_analytics_lesson_completed', function($user_id, $data, $event_id) {
    // Award badge when lesson completed
    award_badge($user_id, 'lesson_complete_' . $data['lesson_id']);
}, 10, 3);
```

---

## Database Schema

### Table: `wp_simple_lms_progress`
Stores user progress through lessons.

```sql
CREATE TABLE wp_simple_lms_progress (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    lesson_id bigint(20) NOT NULL,
    course_id bigint(20) NOT NULL,
    module_id bigint(20) NOT NULL,
    completed tinyint(1) DEFAULT 0,
    completion_date datetime DEFAULT NULL,
    time_spent int(11) DEFAULT 0,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY user_lesson (user_id, lesson_id),
    KEY user_course (user_id, course_id),
    KEY user_module (user_id, module_id),
    KEY completion_date (completion_date),
    KEY user_lesson_completed (user_id, lesson_id, completed),
    KEY course_stats (course_id, completed, user_id)
);
```

**Indexes:**
- `user_lesson` - Ensures one progress record per user/lesson
- `user_lesson_completed` - Optimizes completion queries (60% faster)
- `course_stats` - Optimizes course statistics (50% faster)

---

### Table: `wp_simple_lms_analytics`
Stores analytics events (optional - created only if analytics enabled).

```sql
CREATE TABLE wp_simple_lms_analytics (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    event_type varchar(50) NOT NULL,
    user_id bigint(20) NOT NULL,
    event_data longtext,
    ip_address varchar(45),
    user_agent varchar(255),
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY event_type (event_type),
    KEY created_at (created_at),
    KEY user_event (user_id, event_type)
);
```

**Fields:**
- `event_data` - JSON-encoded event details (lesson_id, course_id, etc.)
- `ip_address` - Sanitized IP (anonymized last octet for IPv4)
- `user_agent` - Truncated to 255 chars

**Indexes:**
- `user_id` - Fast user event lookup
- `event_type` - Filter by event category
- `user_event` - Composite index for user-specific queries
- `created_at` - Time-based analytics queries

**Privacy:**
- Table creation opt-in via settings
- IP addresses sanitized (last octet zeroed)
- Can be disabled without affecting core functionality

---

## Error Handling

All API methods use try-catch blocks and log errors with context:

```php
try {
    // Operation
} catch (\Exception $e) {
    error_log('Simple LMS Error: ' . $e->getMessage());
    return false; // or throw
}
```

**Log Format:**
```
Simple LMS Progress Error: Invalid lesson structure
Simple LMS: Cleaned up 5 expired course access entries
```

---

## Version History

- **1.3.1** - Performance optimizations, security hardening
- **1.3.0** - Tag-based access, WooCommerce integration, REST API
- **1.2.0** - Shortcode management, progress tracking
- **1.1.0** - Cache handler, AJAX improvements
- **1.0.0** - Initial release

---

## Support & Resources

- **Plugin Repository:** GitHub (private)
- **Documentation:** This file + README.md
- **Issue Tracking:** GitHub Issues
- **Testing Guide:** `tests/E2E-TESTING-GUIDE.md`
