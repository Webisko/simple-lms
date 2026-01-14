# Query Monitor Baseline — Simple LMS v1.3.3

## Environment
- PHP: 8.0 / 8.1 / 8.2 / 8.3
- WordPress: 6.4 / 6.5 / 6.6 / 6.7
- Data: 10 courses, 50 modules, 200 lessons, 100 users
- Theme: [name]
- Plugins: [list]

## Tests

### 1. Course Page
- URL: /course/[slug]
- Queries: [target <10] — Actual: []
- Query time: [target <50ms] — Actual: []
- Page generation: [target <200ms] — Actual: []
- Memory peak: [target <5MB] — Actual: []

### 2. Lesson Page (with video)
- URL: /lesson/[slug]
- Queries: [target <15] — Actual: []
- Query time: [target <100ms] — Actual: []
- Page generation: [target <300ms] — Actual: []
- Memory peak: [target <8MB] — Actual: []

### 3. Admin: Courses List
- URL: /wp-admin/edit.php?post_type=course
- Queries: [target <30] — Actual: []
- Query time: [target <150ms] — Actual: []
- Page generation: [target <500ms] — Actual: []
- Memory peak: [target <20MB] — Actual: []

### 4. AJAX: Update Lesson Progress
- Hook: simple_lms_update_lesson_progress
- Queries: [target <5] — Actual: []
- Response time: [target <200ms] — Actual: []

## How to Measure
1. Install Query Monitor
2. Open target pages and note metrics from the QM panel
3. For AJAX, use browser DevTools Network timing and QM’s AJAX panel
4. Record values above

## Findings
- Summary: [All metrics within targets / Notes]
- Optimizations: [optional]

