# Simple LMS REST API Documentation

**Version:** 1.3.3  
**Namespace:** `simple-lms/v1`  
**Base URL:** `https://yoursite.com/wp-json/simple-lms/v1`

---

## Table of Contents

1. [Authentication](#authentication)
2. [Endpoints Overview](#endpoints-overview)
3. [Courses](#courses)
4. [Modules](#modules)
5. [Lessons](#lessons)
6. [Progress Tracking](#progress-tracking)
7. [Error Handling](#error-handling)
8. [Examples](#examples)

---

## Authentication

### Authentication Methods

1. **WordPress Nonce** (for logged-in users, same-origin requests)
   - Add `_wpnonce` parameter to requests
   - Recommended for frontend applications on the same domain

2. **Application Passwords** (for external/programmatic access)
   - Generate in: User Profile → Application Passwords
   - Use HTTP Basic Authentication
   - Format: `Authorization: Basic base64(username:app_password)`

3. **Cookie Authentication** (for logged-in users)
   - Automatically used for authenticated WordPress users
   - No additional headers required

### Permission Levels

| Permission Level | Required Capability | Endpoints |
|-----------------|-------------------|-----------|
| **Public** | None | GET `/courses` (list only) |
| **Authenticated** | Logged-in user | GET `/courses/{id}`, GET `/progress/{user_id}` |
| **Course Access** | Tag-based access or `edit_posts` | GET course/module/lesson details |
| **Editor** | `edit_posts` | POST, PUT, DELETE operations |
| **Self** | Own user ID | `/progress` endpoints for own user |

---

## Endpoints Overview

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/courses` | List all courses | No |
| POST | `/courses` | Create a course | Yes (editor) |
| GET | `/courses/{id}` | Get single course with modules | Yes (access check) |
| PUT | `/courses/{id}` | Update course | Yes (editor) |
| GET | `/courses/{id}/modules` | Get course modules | Yes (access check) |
| POST | `/courses/{id}/modules` | Create module | Yes (editor) |
| GET | `/modules/{id}` | Get single module | Yes (access check) |
| GET | `/modules/{id}/lessons` | Get module lessons | Yes (access check) |
| POST | `/modules/{id}/lessons` | Create lesson | Yes (editor) |
| GET | `/lessons/{id}` | Get single lesson | Yes (access check) |
| GET | `/progress/{user_id}` | Get user progress | Yes (self or admin) |
| POST | `/progress/{user_id}/{lesson_id}` | Update lesson progress | Yes (self or admin) |

---

## Courses

### GET `/courses`

List all published courses with filtering and pagination.

**Authentication:** None (public endpoint)

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | integer | 1 | Page number (min: 1) |
| `per_page` | integer | 10 | Items per page (min: 1, max: 100) |
| `search` | string | - | Search term for course title/content |
| `orderby` | string | date | Sort by: `date`, `title`, `menu_order` |
| `order` | string | DESC | Sort order: `ASC`, `DESC` |
| `category` | string | - | Filter by course category (meta key) |

**Response:**

```json
[
  {
    "id": 123,
    "title": "WordPress Development Course",
    "slug": "wordpress-development-course",
    "status": "publish",
    "date_created": "2025-01-15T10:30:00",
    "date_modified": "2025-01-20T14:22:00",
    "featured_image": "https://example.com/wp-content/uploads/2025/01/course-image.jpg",
    "meta": {
      "allow_comments": true,
      "user_has_access": false
    }
  }
]
```

**Response Headers:**

- `X-WP-Total`: Total number of courses
- `X-WP-TotalPages`: Total number of pages

**Example Request:**

```bash
curl "https://yoursite.com/wp-json/simple-lms/v1/courses?per_page=20&orderby=title&order=ASC"
```

---

### GET `/courses/{id}`

Get detailed information about a specific course, including modules and lessons.

**Authentication:** Required (course access check)

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | Course ID |

**Response:**

```json
{
  "id": 123,
  "title": "WordPress Development Course",
  "slug": "wordpress-development-course",
  "status": "publish",
  "date_created": "2025-01-15T10:30:00",
  "date_modified": "2025-01-20T14:22:00",
  "featured_image": "https://example.com/wp-content/uploads/2025/01/course-image.jpg",
  "meta": {
    "allow_comments": true,
    "user_has_access": true
  },
  "modules": [
    {
      "id": 456,
      "title": "Introduction to WordPress",
      "slug": "introduction-to-wordpress",
      "status": "publish",
      "order": 1,
      "course_id": 123,
      "lesson_count": 5
    }
  ],
  "stats": {
    "total_lessons": 25,
    "completed_lessons": 10,
    "progress_percentage": 40
  }
}
```

**Example Request (with Application Password):**

```bash
curl -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  "https://yoursite.com/wp-json/simple-lms/v1/courses/123"
```

**Error Responses:**

- **404 Not Found:** Course does not exist
- **403 Forbidden:** User does not have access to this course

---

### POST `/courses`

Create a new course.

**Authentication:** Required (`edit_posts` capability)

**Request Body:**

```json
{
  "title": "New Course Title",
  "status": "draft"
}
```

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `title` | string | Yes | Course title |
| `status` | string | No | `draft` or `publish` (default: `draft`) |

**Response:**

```json
{
  "id": 789,
  "title": "New Course Title",
  "slug": "new-course-title",
  "status": "draft",
  "date_created": "2025-01-21T09:15:00",
  "date_modified": "2025-01-21T09:15:00",
  "featured_image": null,
  "meta": {
    "allow_comments": false,
    "user_has_access": true
  }
}
```

**Example Request:**

```bash
curl -X POST "https://yoursite.com/wp-json/simple-lms/v1/courses" \
  -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{"title": "Advanced React Course", "status": "draft"}'
```

---

### PUT `/courses/{id}`

Update an existing course.

**Authentication:** Required (`edit_post` capability for specific course)

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | Course ID to update |

**Request Body:**

```json
{
  "title": "Updated Course Title",
  "status": "publish"
}
```

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `title` | string | No | Course title |
| `status` | string | No | `draft` or `publish` |

**Response:** Same as GET `/courses/{id}`

**Example Request:**

```bash
curl -X PUT "https://yoursite.com/wp-json/simple-lms/v1/courses/123" \
  -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{"status": "publish"}'
```

---

## Modules

### GET `/courses/{course_id}/modules`

Get all modules for a specific course.

**Authentication:** Required (course access check)

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `course_id` | integer | Parent course ID |

**Response:**

```json
[
  {
    "id": 456,
    "title": "Introduction to WordPress",
    "slug": "introduction-to-wordpress",
    "status": "publish",
    "order": 1,
    "course_id": 123,
    "lesson_count": 5
  },
  {
    "id": 457,
    "title": "Advanced WordPress Features",
    "slug": "advanced-wordpress-features",
    "status": "publish",
    "order": 2,
    "course_id": 123,
    "lesson_count": 8
  }
]
```

**Example Request:**

```bash
curl -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  "https://yoursite.com/wp-json/simple-lms/v1/courses/123/modules"
```

---

### POST `/courses/{course_id}/modules`

Create a new module within a course.

**Authentication:** Required (`edit_posts` capability)

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `course_id` | integer | Parent course ID |

**Request Body:**

```json
{
  "title": "New Module Title",
  "course_id": 123
}
```

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `title` | string | Yes | Module title |
| `course_id` | integer | Yes | Parent course ID |

**Response:** Module object (same format as GET response)

**Example Request:**

```bash
curl -X POST "https://yoursite.com/wp-json/simple-lms/v1/courses/123/modules" \
  -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{"title": "New Module", "course_id": 123}'
```

---

### GET `/modules/{id}`

Get details of a specific module.

**Authentication:** Required (course access check via parent course)

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | Module ID |

**Response:** Module object (same format as course modules list)

---

## Lessons

### GET `/modules/{module_id}/lessons`

Get all lessons for a specific module.

**Authentication:** Required (course access check via parent course)

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `module_id` | integer | Parent module ID |

**Response:**

```json
[
  {
    "id": 789,
    "title": "Setting Up WordPress Locally",
    "slug": "setting-up-wordpress-locally",
    "content": "<p>In this lesson, we'll learn how to...</p>",
    "excerpt": "Learn how to set up a local WordPress development environment.",
    "status": "publish",
    "order": 1,
    "module_id": 456,
    "featured_image": "https://example.com/wp-content/uploads/2025/01/lesson-image.jpg",
    "comments_open": true
  }
]
```

**Example Request:**

```bash
curl -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  "https://yoursite.com/wp-json/simple-lms/v1/modules/456/lessons"
```

---

### POST `/modules/{module_id}/lessons`

Create a new lesson within a module.

**Authentication:** Required (`edit_posts` capability)

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `module_id` | integer | Parent module ID |

**Request Body:**

```json
{
  "title": "New Lesson Title",
  "content": "<p>Lesson content here...</p>",
  "module_id": 456
}
```

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `title` | string | Yes | Lesson title |
| `content` | string | No | Lesson content (HTML allowed) |
| `module_id` | integer | Yes | Parent module ID |

**Response:** Lesson object (same format as GET response)

**Example Request:**

```bash
curl -X POST "https://yoursite.com/wp-json/simple-lms/v1/modules/456/lessons" \
  -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{"title": "Introduction to REST API", "content": "<p>REST API basics...</p>", "module_id": 456}'
```

---

### GET `/lessons/{id}`

Get details of a specific lesson.

**Authentication:** Required (course access check via parent course)

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | Lesson ID |

**Response:** Lesson object (same format as module lessons list)

---

## Progress Tracking

### GET `/progress/{user_id}`

Get all progress data for a specific user across all courses.

**Authentication:** Required (must be the user themselves or have `edit_users` capability)

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `user_id` | integer | User ID |

**Response:**

```json
{
  "user_id": 42,
  "courses": [
    {
      "course_id": 123,
      "course_title": "WordPress Development Course",
      "total_lessons": 25,
      "completed_lessons": 10,
      "progress_percentage": 40,
      "last_accessed": "2025-01-20T15:30:00",
      "completed_lesson_ids": [789, 790, 791, 792, 793, 794, 795, 796, 797, 798]
    }
  ],
  "overall_stats": {
    "total_courses": 3,
    "completed_courses": 0,
    "total_lessons": 75,
    "completed_lessons": 22,
    "overall_progress_percentage": 29
  }
}
```

**Example Request:**

```bash
curl -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  "https://yoursite.com/wp-json/simple-lms/v1/progress/42"
```

**Error Responses:**

- **403 Forbidden:** User is not authorized to view this progress data

---

### POST `/progress/{user_id}/{lesson_id}`

Mark a lesson as completed or incomplete for a specific user.

**Authentication:** Required (must be the user themselves or have `edit_users` capability)

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `user_id` | integer | User ID |
| `lesson_id` | integer | Lesson ID |

**Request Body:**

```json
{
  "completed": true
}
```

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `completed` | boolean | No | Mark as completed (default: `true`) |

**Response:**

```json
{
  "success": true,
  "message": "Postęp zaktualizowany"
}
```

**Example Request (Mark as Completed):**

```bash
curl -X POST "https://yoursite.com/wp-json/simple-lms/v1/progress/42/789" \
  -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{"completed": true}'
```

**Example Request (Mark as Incomplete):**

```bash
curl -X POST "https://yoursite.com/wp-json/simple-lms/v1/progress/42/789" \
  -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{"completed": false}'
```

---

## Error Handling

### Standard Error Response Format

All errors return a JSON object with the following structure:

```json
{
  "code": "error_code",
  "message": "Human-readable error message",
  "data": {
    "status": 400
  }
}
```

### Common Error Codes

| HTTP Status | Error Code | Description |
|-------------|-----------|-------------|
| 400 | `rest_invalid_param` | Invalid parameter value |
| 401 | `rest_not_logged_in` | Authentication required |
| 403 | `rest_forbidden` | Insufficient permissions |
| 404 | `course_not_found` | Course does not exist |
| 404 | `rest_post_invalid_id` | Invalid resource ID |
| 500 | `api_error` | Internal server error |
| 500 | `update_failed` | Database update failed |

### Error Examples

**401 Unauthorized:**

```json
{
  "code": "rest_not_logged_in",
  "message": "You are not currently logged in.",
  "data": {
    "status": 401
  }
}
```

**403 Forbidden:**

```json
{
  "code": "rest_forbidden",
  "message": "Sorry, you are not allowed to access this resource.",
  "data": {
    "status": 403
  }
}
```

**404 Not Found:**

```json
{
  "code": "course_not_found",
  "message": "Kurs nie znaleziony",
  "data": {
    "status": 404
  }
}
```

---

## Examples

### Example 1: Frontend Course Listing

```javascript
// Fetch all courses with pagination
async function fetchCourses(page = 1) {
  const response = await fetch(`/wp-json/simple-lms/v1/courses?page=${page}&per_page=12`);
  const courses = await response.json();
  const totalPages = parseInt(response.headers.get('X-WP-TotalPages'));
  
  return { courses, totalPages };
}

// Usage
fetchCourses(1).then(({ courses, totalPages }) => {
  courses.forEach(course => {
    console.log(`${course.title} (ID: ${course.id})`);
  });
  console.log(`Total pages: ${totalPages}`);
});
```

---

### Example 2: Display Course with Modules

```javascript
// Fetch course details with modules
async function fetchCourseDetails(courseId) {
  const response = await fetch(`/wp-json/simple-lms/v1/courses/${courseId}`, {
    headers: {
      'X-WP-Nonce': wpApiSettings.nonce // WordPress nonce for authenticated request
    }
  });
  
  if (!response.ok) {
    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
  }
  
  return await response.json();
}

// Usage
fetchCourseDetails(123).then(course => {
  console.log(`Course: ${course.title}`);
  console.log(`Progress: ${course.stats.progress_percentage}%`);
  console.log(`Modules: ${course.modules.length}`);
  
  course.modules.forEach(module => {
    console.log(`  - ${module.title} (${module.lesson_count} lessons)`);
  });
});
```

---

### Example 3: Mark Lesson as Completed

```javascript
// Mark lesson as completed
async function completeLeson(userId, lessonId) {
  const response = await fetch(`/wp-json/simple-lms/v1/progress/${userId}/${lessonId}`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({ completed: true })
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message);
  }
  
  return await response.json();
}

// Usage (e.g., on "Mark as Complete" button click)
document.getElementById('complete-btn').addEventListener('click', async () => {
  try {
    const result = await completeLesson(42, 789);
    console.log(result.message); // "Postęp zaktualizowany"
    alert('Lesson marked as completed!');
  } catch (error) {
    console.error('Error:', error.message);
    alert('Failed to update progress');
  }
});
```

---

### Example 4: External Application (Python)

```python
import requests
from base64 import b64encode

# Configuration
BASE_URL = "https://yoursite.com/wp-json/simple-lms/v1"
USERNAME = "your_username"
APP_PASSWORD = "xxxx xxxx xxxx xxxx xxxx xxxx"  # Generated in WordPress

# Create Basic Auth header
credentials = f"{USERNAME}:{APP_PASSWORD}"
auth_header = b64encode(credentials.encode()).decode()
headers = {
    "Authorization": f"Basic {auth_header}",
    "Content-Type": "application/json"
}

# Fetch user progress
def get_user_progress(user_id):
    url = f"{BASE_URL}/progress/{user_id}"
    response = requests.get(url, headers=headers)
    response.raise_for_status()
    return response.json()

# Mark lesson as completed
def complete_lesson(user_id, lesson_id):
    url = f"{BASE_URL}/progress/{user_id}/{lesson_id}"
    data = {"completed": True}
    response = requests.post(url, json=data, headers=headers)
    response.raise_for_status()
    return response.json()

# Usage
if __name__ == "__main__":
    try:
        # Get progress
        progress = get_user_progress(42)
        print(f"Overall progress: {progress['overall_stats']['overall_progress_percentage']}%")
        
        # Complete a lesson
        result = complete_lesson(42, 789)
        print(result['message'])
        
    except requests.exceptions.HTTPError as e:
        print(f"HTTP Error: {e}")
```

---

### Example 5: Create Course (PHP/WordPress Plugin)

```php
<?php
// Create a new course via REST API
function create_simple_lms_course($title, $status = 'draft') {
    $url = rest_url('simple-lms/v1/courses');
    
    $args = [
        'method' => 'POST',
        'headers' => [
            'Content-Type' => 'application/json',
            'X-WP-Nonce' => wp_create_nonce('wp_rest')
        ],
        'body' => json_encode([
            'title' => $title,
            'status' => $status
        ])
    ];
    
    $response = wp_remote_request($url, $args);
    
    if (is_wp_error($response)) {
        return ['error' => $response->get_error_message()];
    }
    
    return json_decode(wp_remote_retrieve_body($response), true);
}

// Usage
$course = create_simple_lms_course('New Course from API', 'publish');
if (isset($course['error'])) {
    echo "Error: " . $course['error'];
} else {
    echo "Created course ID: " . $course['id'];
}
```

---

## Best Practices

### 1. Authentication

- **Use Application Passwords** for external/programmatic access
- **Use WordPress Nonce** for same-origin frontend requests
- **Never hardcode credentials** in client-side code
- **Rotate Application Passwords** regularly for security

### 2. Error Handling

- **Always check response status** before parsing JSON
- **Display user-friendly error messages** from `message` field
- **Log technical errors** to console/logs for debugging
- **Implement retry logic** for transient network errors

### 3. Performance

- **Use pagination** (`per_page`) to limit large datasets
- **Cache responses** on the client side when appropriate
- **Use `X-WP-Total` header** to calculate pagination UI
- **Batch requests** when possible (e.g., fetch course + modules in single request)

### 4. Security

- **Validate user permissions** before displaying sensitive data
- **Sanitize all input** before sending to API
- **Use HTTPS** in production environments
- **Implement rate limiting** for public-facing endpoints

### 5. Data Consistency

- **Refresh progress data** after marking lessons complete
- **Handle concurrent updates** gracefully (last-write-wins)
- **Use optimistic UI updates** with error rollback
- **Sync client state** with server response after mutations

---

## Rate Limiting

Currently, Simple LMS REST API does not implement rate limiting. Consider implementing:

- **WordPress plugins:** WP REST API Rate Limit, Limit Login Attempts
- **Server-level:** Nginx rate limiting, Cloudflare Rate Limiting Rules
- **Application-level:** Custom middleware in `rest_api_init` hook

---

## Changelog

### Version 1.3.3 (2025-01-21)
- ✅ Full REST API implementation
- ✅ Tag-based access control for courses
- ✅ Progress tracking endpoints
- ✅ Course, module, and lesson CRUD operations
- ✅ Pagination and filtering support
- ✅ Application Password authentication support

### Version 1.2.0 (2024-12-15)
- Initial REST API structure
- Basic course listing endpoint

---

## Support & Resources

- **Plugin Documentation:** [DEEP-REPORT.md](../DEEP-REPORT.md)
- **Performance Guide:** [PERFORMANCE-OPTIMIZATION-LOG.md](../PERFORMANCE-OPTIMIZATION-LOG.md)
- **Database Schema:** [DB-INDEX-VERIFICATION.md](../DB-INDEX-VERIFICATION.md)
- **Test Coverage:** [tests/COVERAGE-REPORT.md](../tests/COVERAGE-REPORT.md)
- **WordPress REST API Handbook:** https://developer.wordpress.org/rest-api/

---

**Generated by:** GitHub Copilot  
**Last Updated:** 2025-01-21  
**Plugin Version:** 1.3.3
