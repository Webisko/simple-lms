# Simple LMS REST API - Postman Collection Examples

## Prerequisites

1. **Generate Application Password:**
   - Go to: WordPress Admin → Users → Your Profile
   - Scroll to "Application Passwords"
   - Name: "REST API Testing"
   - Click "Add New Application Password"
   - Copy the generated password (format: `xxxx xxxx xxxx xxxx xxxx xxxx`)

2. **Base URL:**
   ```
   https://yoursite.local/wp-json/simple-lms/v1
   ```

3. **Authentication:**
   - Method: Basic Auth
   - Username: `your_wordpress_username`
   - Password: `xxxx xxxx xxxx xxxx xxxx xxxx` (Application Password)

---

## 1. List All Courses (Public)

**Request:**
```http
GET /wp-json/simple-lms/v1/courses?per_page=10&page=1
```

**cURL:**
```bash
curl "https://yoursite.local/wp-json/simple-lms/v1/courses?per_page=10&page=1"
```

**Expected Response:**
```json
[
  {
    "id": 123,
    "title": "WordPress Development Course",
    "slug": "wordpress-development-course",
    "status": "publish",
    "date_created": "2025-01-15T10:30:00",
    "date_modified": "2025-01-20T14:22:00",
    "featured_image": "https://yoursite.local/wp-content/uploads/2025/01/course.jpg",
    "meta": {
      "allow_comments": true,
      "user_has_access": false
    }
  }
]
```

**Response Headers:**
- `X-WP-Total: 25`
- `X-WP-TotalPages: 3`

---

## 2. Search Courses

**Request:**
```http
GET /wp-json/simple-lms/v1/courses?search=wordpress&orderby=title&order=ASC
```

**cURL:**
```bash
curl "https://yoursite.local/wp-json/simple-lms/v1/courses?search=wordpress&orderby=title&order=ASC"
```

---

## 3. Get Single Course (Authenticated)

**Request:**
```http
GET /wp-json/simple-lms/v1/courses/123
Authorization: Basic base64(username:password)
```

**cURL:**
```bash
curl -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  "https://yoursite.local/wp-json/simple-lms/v1/courses/123"
```

**Expected Response:**
```json
{
  "id": 123,
  "title": "WordPress Development Course",
  "slug": "wordpress-development-course",
  "status": "publish",
  "date_created": "2025-01-15T10:30:00",
  "date_modified": "2025-01-20T14:22:00",
  "featured_image": "https://yoursite.local/wp-content/uploads/2025/01/course.jpg",
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

---

## 4. Create New Course (Editor Required)

**Request:**
```http
POST /wp-json/simple-lms/v1/courses
Authorization: Basic base64(username:password)
Content-Type: application/json

{
  "title": "Advanced React Course",
  "status": "draft"
}
```

**cURL:**
```bash
curl -X POST "https://yoursite.local/wp-json/simple-lms/v1/courses" \
  -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Advanced React Course",
    "status": "draft"
  }'
```

**Expected Response:**
```json
{
  "id": 789,
  "title": "Advanced React Course",
  "slug": "advanced-react-course",
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

---

## 5. Update Course (Editor Required)

**Request:**
```http
PUT /wp-json/simple-lms/v1/courses/789
Authorization: Basic base64(username:password)
Content-Type: application/json

{
  "status": "publish"
}
```

**cURL:**
```bash
curl -X PUT "https://yoursite.local/wp-json/simple-lms/v1/courses/789" \
  -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "publish"
  }'
```

---

## 6. Get Course Modules

**Request:**
```http
GET /wp-json/simple-lms/v1/courses/123/modules
Authorization: Basic base64(username:password)
```

**cURL:**
```bash
curl -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  "https://yoursite.local/wp-json/simple-lms/v1/courses/123/modules"
```

**Expected Response:**
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

---

## 7. Create New Module

**Request:**
```http
POST /wp-json/simple-lms/v1/courses/123/modules
Authorization: Basic base64(username:password)
Content-Type: application/json

{
  "title": "Theme Development Basics",
  "course_id": 123
}
```

**cURL:**
```bash
curl -X POST "https://yoursite.local/wp-json/simple-lms/v1/courses/123/modules" \
  -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Theme Development Basics",
    "course_id": 123
  }'
```

---

## 8. Get Module Lessons

**Request:**
```http
GET /wp-json/simple-lms/v1/modules/456/lessons
Authorization: Basic base64(username:password)
```

**cURL:**
```bash
curl -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  "https://yoursite.local/wp-json/simple-lms/v1/modules/456/lessons"
```

**Expected Response:**
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
    "featured_image": "https://yoursite.local/wp-content/uploads/2025/01/lesson.jpg",
    "comments_open": true
  }
]
```

---

## 9. Create New Lesson

**Request:**
```http
POST /wp-json/simple-lms/v1/modules/456/lessons
Authorization: Basic base64(username:password)
Content-Type: application/json

{
  "title": "Introduction to REST API",
  "content": "<p>REST API basics and how to use them...</p>",
  "module_id": 456
}
```

**cURL:**
```bash
curl -X POST "https://yoursite.local/wp-json/simple-lms/v1/modules/456/lessons" \
  -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Introduction to REST API",
    "content": "<p>REST API basics and how to use them...</p>",
    "module_id": 456
  }'
```

---

## 10. Get User Progress

**Request:**
```http
GET /wp-json/simple-lms/v1/progress/42
Authorization: Basic base64(username:password)
```

**cURL:**
```bash
curl -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  "https://yoursite.local/wp-json/simple-lms/v1/progress/42"
```

**Expected Response:**
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

---

## 11. Mark Lesson as Completed

**Request:**
```http
POST /wp-json/simple-lms/v1/progress/42/789
Authorization: Basic base64(username:password)
Content-Type: application/json

{
  "completed": true
}
```

**cURL:**
```bash
curl -X POST "https://yoursite.local/wp-json/simple-lms/v1/progress/42/789" \
  -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "completed": true
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Postęp zaktualizowany"
}
```

---

## 12. Mark Lesson as Incomplete

**Request:**
```http
POST /wp-json/simple-lms/v1/progress/42/789
Authorization: Basic base64(username:password)
Content-Type: application/json

{
  "completed": false
}
```

**cURL:**
```bash
curl -X POST "https://yoursite.local/wp-json/simple-lms/v1/progress/42/789" \
  -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "completed": false
  }'
```

---

## Error Testing

### 1. Unauthorized Access (No Auth)

**Request:**
```bash
curl "https://yoursite.local/wp-json/simple-lms/v1/courses/123"
```

**Expected Response (401):**
```json
{
  "code": "rest_not_logged_in",
  "message": "You are not currently logged in.",
  "data": {
    "status": 401
  }
}
```

---

### 2. Forbidden (No Course Access)

**Request:**
```bash
curl -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  "https://yoursite.local/wp-json/simple-lms/v1/courses/999"
```

**Expected Response (403):**
```json
{
  "code": "rest_forbidden",
  "message": "Sorry, you are not allowed to access this resource.",
  "data": {
    "status": 403
  }
}
```

---

### 3. Not Found (Invalid Course ID)

**Request:**
```bash
curl -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  "https://yoursite.local/wp-json/simple-lms/v1/courses/99999"
```

**Expected Response (404):**
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

### 4. Invalid Parameter (Bad Request)

**Request:**
```bash
curl -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  "https://yoursite.local/wp-json/simple-lms/v1/courses?per_page=999"
```

**Expected Response (400):**
```json
{
  "code": "rest_invalid_param",
  "message": "Invalid parameter(s): per_page",
  "data": {
    "status": 400,
    "params": {
      "per_page": "per_page must be between 1 and 100"
    }
  }
}
```

---

## Postman Collection JSON

Import this collection into Postman for easy testing:

```json
{
  "info": {
    "name": "Simple LMS REST API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "auth": {
    "type": "basic",
    "basic": [
      {
        "key": "username",
        "value": "{{username}}",
        "type": "string"
      },
      {
        "key": "password",
        "value": "{{app_password}}",
        "type": "string"
      }
    ]
  },
  "item": [
    {
      "name": "Courses",
      "item": [
        {
          "name": "List Courses",
          "request": {
            "method": "GET",
            "header": [],
            "url": {
              "raw": "{{base_url}}/courses?per_page=10&page=1",
              "host": ["{{base_url}}"],
              "path": ["courses"],
              "query": [
                {"key": "per_page", "value": "10"},
                {"key": "page", "value": "1"}
              ]
            }
          }
        },
        {
          "name": "Get Course",
          "request": {
            "method": "GET",
            "header": [],
            "url": {
              "raw": "{{base_url}}/courses/{{course_id}}",
              "host": ["{{base_url}}"],
              "path": ["courses", "{{course_id}}"]
            }
          }
        },
        {
          "name": "Create Course",
          "request": {
            "method": "POST",
            "header": [
              {"key": "Content-Type", "value": "application/json"}
            ],
            "body": {
              "mode": "raw",
              "raw": "{\n  \"title\": \"New Course\",\n  \"status\": \"draft\"\n}"
            },
            "url": {
              "raw": "{{base_url}}/courses",
              "host": ["{{base_url}}"],
              "path": ["courses"]
            }
          }
        }
      ]
    },
    {
      "name": "Progress",
      "item": [
        {
          "name": "Get User Progress",
          "request": {
            "method": "GET",
            "header": [],
            "url": {
              "raw": "{{base_url}}/progress/{{user_id}}",
              "host": ["{{base_url}}"],
              "path": ["progress", "{{user_id}}"]
            }
          }
        },
        {
          "name": "Mark Lesson Complete",
          "request": {
            "method": "POST",
            "header": [
              {"key": "Content-Type", "value": "application/json"}
            ],
            "body": {
              "mode": "raw",
              "raw": "{\n  \"completed\": true\n}"
            },
            "url": {
              "raw": "{{base_url}}/progress/{{user_id}}/{{lesson_id}}",
              "host": ["{{base_url}}"],
              "path": ["progress", "{{user_id}}", "{{lesson_id}}"]
            }
          }
        }
      ]
    }
  ],
  "variable": [
    {
      "key": "base_url",
      "value": "https://yoursite.local/wp-json/simple-lms/v1"
    },
    {
      "key": "username",
      "value": "your_username"
    },
    {
      "key": "app_password",
      "value": "xxxx xxxx xxxx xxxx xxxx xxxx"
    },
    {
      "key": "course_id",
      "value": "123"
    },
    {
      "key": "user_id",
      "value": "42"
    },
    {
      "key": "lesson_id",
      "value": "789"
    }
  ]
}
```

---

## JavaScript Fetch Examples

### Frontend (with WordPress Nonce)

```javascript
// Assuming wpApiSettings is localized with nonce
const BASE_URL = '/wp-json/simple-lms/v1';

// GET request
async function getCourse(courseId) {
  const response = await fetch(`${BASE_URL}/courses/${courseId}`, {
    headers: {
      'X-WP-Nonce': wpApiSettings.nonce
    }
  });
  
  if (!response.ok) {
    throw new Error(`HTTP ${response.status}`);
  }
  
  return await response.json();
}

// POST request
async function markComplete(userId, lessonId) {
  const response = await fetch(`${BASE_URL}/progress/${userId}/${lessonId}`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({ completed: true })
  });
  
  return await response.json();
}
```

### External App (with Application Password)

```javascript
const BASE_URL = 'https://yoursite.local/wp-json/simple-lms/v1';
const USERNAME = 'your_username';
const APP_PASSWORD = 'xxxx xxxx xxxx xxxx xxxx xxxx';

// Create Basic Auth header
const authHeader = 'Basic ' + btoa(`${USERNAME}:${APP_PASSWORD}`);

async function getCourse(courseId) {
  const response = await fetch(`${BASE_URL}/courses/${courseId}`, {
    headers: {
      'Authorization': authHeader
    }
  });
  
  return await response.json();
}
```

---

**Note:** Replace `yoursite.local`, `username`, and `xxxx xxxx xxxx xxxx xxxx xxxx` with your actual values.
