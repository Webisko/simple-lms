# Contributing to Simple LMS

Thank you for your interest in contributing to Simple LMS! This document provides guidelines and best practices for contributing to the project.

**Development Style:** This project uses vibe coding with GitHub Copilot Agent Mode for rapid development.

---

## Table of Contents

1. [Quick Start](#quick-start)
2. [Vibe Coding with Copilot](#vibe-coding-with-copilot)
3. [Code of Conduct](#code-of-conduct)
4. [Development Workflow](#development-workflow)
5. [Coding Standards](#coding-standards)
6. [Commit Guidelines](#commit-guidelines)
7. [Git Workflow](#git-workflow)
8. [Testing Requirements](#testing-requirements)
9. [Documentation](#documentation)

---

## Quick Start

### Setup
```bash
# Clone repository
git clone https://github.com/YOUR_USERNAME/simple-lms.git
cd simple-lms

# Install dependencies
composer install

# Copy to WordPress
cp -r . /path/to/wp-content/plugins/simple-lms

# Activate in Local by Flywheel
# Open Local → Simple LMS → Enable
```

### Development Environment
- **Editor:** VS Code with extensions (see `.vscode/settings.json`)
- **Local WordPress:** Local by Flywheel
- **PHP:** 7.4+ (tested on 7.4, 8.0, 8.1, 8.2)
- **WP-CLI:** Available for testing

---

## Vibe Coding with Copilot

### Best Practices

**1. Detailed Prompts (Not "just write code")**
```
✓ "Create Elementor widget for course overview:
   - Module list with expand/collapse
   - User progress percentage
   - Access validation via user_meta simple_lms_course_access
   - Responsive grid layout
   - Support both Polish and English"

✗ "Make a widget"
```

**2. Context in Prompts**
```
"In SimpleLMS codebase context:
- Namespace all classes as SimpleLMS\\
- Always sanitize/validate user input (absint, sanitize_text_field)
- Always escape output (esc_html, esc_attr)
- Follow PSR-12 standard
- Use WordPress hooks/filters pattern
- Add comprehensive PHPDoc comments"
```

**3. Code Review**
- Review all Copilot-generated code as you would review PR
- Check for security issues (sanitize/escape)
- Check for performance (query caching, N+1 issues)
- Check for WordPress standards compliance
- Ask Copilot to explain generated code

**4. Agent Mode Tasks**
```
Copilot can handle:
- "Refactor this file for performance"
- "Add comprehensive error handling"
- "Create unit tests for this class"
- "Generate PHPDoc for all functions"
- "Audit security issues in this code"
```

---

## Code of Conduct

### Our Pledge

We are committed to providing a welcoming and inclusive environment for all contributors. We expect:

- **Respectful Communication**: Treat all contributors with respect and professionalism
- **Constructive Feedback**: Provide helpful, actionable feedback in code reviews
- **Collaboration**: Work together to find the best solutions
- **Inclusivity**: Welcome contributors of all skill levels and backgrounds

### Unacceptable Behavior

- Harassment, discrimination, or personal attacks
- Trolling, insulting comments, or off-topic discussions
- Publishing others' private information without permission
- Any conduct inappropriate in a professional setting

---

## Code of Conduct

### Our Pledge

We are committed to providing a welcoming and inclusive environment for all contributors. We expect:

- **Respectful Communication**: Treat all contributors with respect and professionalism
- **Constructive Feedback**: Provide helpful, actionable feedback in code reviews
- **Collaboration**: Work together to find the best solutions
- **Inclusivity**: Welcome contributors of all skill levels and backgrounds

### Unacceptable Behavior

- Harassment, discrimination, or personal attacks
- Trolling, insulting comments, or off-topic discussions
- Publishing others' private information without permission
- Any conduct inappropriate in a professional setting

---

## Getting Started

### Prerequisites

Before contributing, ensure you have:

- **PHP 8.0+** with required extensions (mbstring, xml, json)
- **Composer 2.0+** for dependency management
- **Node.js 18+** and npm for asset building
- **Git** for version control
- **WordPress 6.0+** local development environment (Local by Flywheel, XAMPP, or similar)

### Fork and Clone

1. **Fork the repository** on GitHub
2. **Clone your fork**:
   ```bash
   git clone https://github.com/YOUR-USERNAME/simple-lms.git
   cd simple-lms
   ```

3. **Add upstream remote**:
   ```bash
   git remote add upstream https://github.com/ORIGINAL-OWNER/simple-lms.git
   ```

4. **Install dependencies**:
   ```bash
   # PHP dependencies
   composer install
   
   # Node dependencies (for asset building)
   npm install
   ```

5. **Create a feature branch**:
   ```bash
   git checkout -b feature/your-feature-name
   ```

---

## Development Workflow

### Branch Strategy

We use **Git Flow** with the following branches:

- `main` - Production-ready code (protected)
- `develop` - Integration branch for features (protected)
- `feature/*` - Feature development branches
- `bugfix/*` - Bug fix branches
- `hotfix/*` - Emergency production fixes
- `release/*` - Release preparation branches

### Branch Naming Convention

```
feature/add-quiz-system
feature/rest-api-v2
bugfix/cache-invalidation-issue
bugfix/lesson-progress-race-condition
hotfix/security-nonce-bypass
release/v1.0.0
```

### Development Steps

1. **Sync with upstream**:
   ```bash
   git checkout develop
   git fetch upstream
   git merge upstream/develop
   ```

2. **Create feature branch**:
   ```bash
   git checkout -b feature/your-feature-name develop
   ```

3. **Make changes** following coding standards

4. **Write tests** for new functionality

5. **Run quality checks**:
   ```bash
   composer check  # lint + analyse + test
   npm run build   # build assets
   ```

6. **Commit changes** with conventional commits

7. **Push to your fork**:
   ```bash
   git push origin feature/your-feature-name
   ```

8. **Open Pull Request** to `develop` branch

---

## Coding Standards

### PHP Standards

Simple LMS follows **WordPress Coding Standards** with some PSR additions:

#### File Structure

```php
<?php
/**
 * Class description
 *
 * @package SimpleLMS
 * @since 1.0.0
 */

namespace SimpleLMS;

use SimpleLMS\Services\Logger;
use SimpleLMS\Services\Security_Service;

/**
 * Class Name
 *
 * Detailed class description explaining purpose and usage.
 *
 * @since 1.0.0
 */
class Class_Name {
    /**
     * Logger instance
     *
     * @var Logger
     */
    private Logger $logger;

    /**
     * Constructor
     *
     * @param Logger $logger Logger instance for structured logging.
     */
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }

    /**
     * Method description
     *
     * More detailed explanation of what the method does.
     *
     * @since 1.0.0
     *
     * @param int    $user_id   User ID to process.
     * @param string $context   Context for operation (e.g., 'enrollment', 'completion').
     * @return bool True on success, false on failure.
     */
    public function process_user(int $user_id, string $context = 'default'): bool {
        // Method implementation
        return true;
    }
}
```

#### Naming Conventions

| Element | Convention | Example |
|---------|------------|---------|
| **Classes** | `Snake_Case` (WordPress style) | `Cache_Handler`, `Progress_Tracker` |
| **Methods** | `snake_case` | `get_user_progress()`, `mark_complete()` |
| **Variables** | `$snake_case` | `$user_id`, `$course_progress` |
| **Constants** | `SCREAMING_SNAKE_CASE` | `SIMPLE_LMS_VERSION`, `CACHE_TTL` |
| **Hooks** | `snake_case` with plugin prefix | `simple_lms_before_init`, `simple_lms_user_enrolled` |

#### Type Declarations

**Always use type hints** for PHP 8.0+:

```php
// ✅ Good: Full type declarations
public function grant_access(int $user_id, int $course_id, int $order_id = 0): bool {
    // Implementation
}

// ❌ Bad: Missing types
public function grant_access($user_id, $course_id, $order_id = 0) {
    // Implementation
}
```

#### Dependency Injection

**Prefer constructor injection** over static calls:

```php
// ✅ Good: Constructor injection
class Analytics_Tracker {
    public function __construct(
        private Logger $logger,
        private Security_Service $security
    ) {}
    
    public function track_event(string $event_type, array $data): void {
        $this->logger->info('Event tracked', ['type' => $event_type]);
    }
}

// ❌ Bad: Static dependencies
class Analytics_Tracker {
    public static function track_event(string $event_type, array $data): void {
        error_log("Event: $event_type"); // Untestable
    }
}
```

#### Security Best Practices

**Always validate, sanitize, and escape**:

```php
// ✅ Good: Complete security chain
if (!check_ajax_referer('simple_lms_ajax', 'nonce', false)) {
    wp_send_json_error('Invalid nonce');
}

if (!current_user_can('edit_courses')) {
    wp_send_json_error('Insufficient permissions');
}

$lesson_id = isset($_POST['lesson_id']) ? absint($_POST['lesson_id']) : 0;
$title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';

echo '<h2>' . esc_html($title) . '</h2>';
```

See [SECURITY.md](SECURITY.md) for comprehensive security guidelines.

### JavaScript/CSS Standards

#### JavaScript (ES6+)

```javascript
// Use modern ES6+ syntax
class CoursePlayer {
    constructor(courseId, options = {}) {
        this.courseId = courseId;
        this.options = { autoplay: false, ...options };
    }

    async loadLesson(lessonId) {
        try {
            const response = await fetch(`/wp-json/simple-lms/v1/lessons/${lessonId}`);
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Failed to load lesson:', error);
            return null;
        }
    }
}

// Use const/let, never var
const API_BASE = '/wp-json/simple-lms/v1';
let currentLesson = null;
```

#### CSS (Utility-First with PostCSS)

```css
/* Use custom properties for theming */
:root {
    --simple-lms-primary: #2271b1;
    --simple-lms-spacing: 1rem;
}

/* Prefer utility classes */
.course-card {
    @apply flex flex-col gap-4 p-4 rounded-lg shadow-md;
}

/* Use logical properties */
.lesson-content {
    padding-block: var(--simple-lms-spacing);
    margin-inline: auto;
}
```

---

## Commit Guidelines

### Conventional Commits

We use **Conventional Commits** specification:

```
<type>(<scope>): <subject>

<body>

<footer>
```

#### Types

| Type | Description | Example |
|------|-------------|---------|
| `feat` | New feature | `feat(analytics): add GA4 integration` |
| `fix` | Bug fix | `fix(cache): resolve race condition in invalidation` |
| `refactor` | Code restructuring | `refactor(di): migrate to ServiceContainer` |
| `perf` | Performance improvement | `perf(queries): optimize course listing query` |
| `docs` | Documentation only | `docs(hooks): document analytics events` |
| `style` | Code style (formatting) | `style(phpcs): fix spacing violations` |
| `test` | Adding/updating tests | `test(analytics): add retention cleanup tests` |
| `chore` | Maintenance tasks | `chore(deps): update PHPUnit to 10.5` |
| `security` | Security improvements | `security(nonce): enforce contextual nonces` |

#### Scope

Scope indicates the affected component:

- `core` - Core plugin functionality
- `cache` - Cache system
- `analytics` - Analytics subsystem
- `woocommerce` - WooCommerce integration
- `rest-api` - REST API endpoints
- `ajax` - AJAX handlers
- `di` - Dependency injection
- `security` - Security features
- `i18n` - Internationalization
- `build` - Build system

#### Examples

```bash
# Feature with breaking change
feat(rest-api)!: add v2 endpoints with pagination

BREAKING CHANGE: v1 endpoints deprecated, migrate to /v2/* endpoints

# Bug fix with issue reference
fix(progress): prevent duplicate completion events

Fixes race condition when marking lessons complete rapidly.
Progress_Tracker now uses transactional update with wp_cache_add.

Closes #123

# Security fix
security(nonce): enforce contextual nonces in AJAX handlers

All AJAX actions now verify nonces with context-specific actions.
Added Security_Service integration for centralized verification.

# Documentation
docs(testing): add coverage report guide

Added instructions for generating and interpreting PHPUnit
coverage reports with Xdebug configuration steps.
```

---

## Pull Request Process

### Before Submitting

**Checklist:**

- [ ] Code follows WordPress coding standards (`composer lint` passes)
- [ ] PHPStan analysis passes (`composer analyse` passes)
- [ ] All tests pass (`composer test` passes)
- [ ] New features have tests (target: 80% coverage)
- [ ] Documentation updated (README, HOOKS.md, etc.)
- [ ] Changelog updated (CHANGELOG.md)
- [ ] No merge conflicts with `develop`
- [ ] Assets built (`npm run build` if JS/CSS changed)

### PR Template

Use this template for your pull request:

```markdown
## Description
<!-- Clear, concise description of changes -->

## Type of Change
- [ ] 🐛 Bug fix (non-breaking)
- [ ] ✨ New feature (non-breaking)
- [ ] 💥 Breaking change (fix or feature requiring updates)
- [ ] 📝 Documentation update
- [ ] ♻️ Refactoring (no functional changes)
- [ ] ⚡ Performance improvement

## Related Issues
<!-- Link related issues: Closes #123, Fixes #456 -->

## Testing
<!-- Describe testing performed -->
- [ ] Unit tests added/updated
- [ ] Integration tests added/updated
- [ ] Manual testing performed

## Screenshots (if applicable)
<!-- Add screenshots for UI changes -->

## Checklist
- [ ] My code follows the project's coding standards
- [ ] I have performed a self-review of my code
- [ ] I have commented complex logic
- [ ] I have updated the documentation
- [ ] My changes generate no new warnings
- [ ] I have added tests that prove my fix is effective or that my feature works
- [ ] New and existing unit tests pass locally with my changes
- [ ] Any dependent changes have been merged and published
```

### Review Process

1. **Automated Checks**: CI runs tests, linting, and static analysis
2. **Code Review**: Maintainer reviews code quality, security, and design
3. **Feedback**: Address review comments and push updates
4. **Approval**: Two approvals required for merging
5. **Merge**: Maintainer merges to `develop` (squash & merge)

### Review Expectations

**Reviewers will check:**

- **Functionality**: Does it work as intended?
- **Security**: Are inputs validated? Nonces checked? Capabilities verified?
- **Performance**: Any database query concerns? Caching implemented?
- **Code Quality**: Readable? Well-documented? Follows standards?
- **Tests**: Adequate coverage? Edge cases handled?
- **Backward Compatibility**: Breaking changes documented?

---

## Issue Guidelines

### Reporting Bugs

**Use the Bug Report template:**

```markdown
**Describe the bug**
A clear and concise description of what the bug is.

**To Reproduce**
Steps to reproduce the behavior:
1. Go to '...'
2. Click on '...'
3. Scroll down to '...'
4. See error

**Expected behavior**
A clear and concise description of what you expected to happen.

**Screenshots**
If applicable, add screenshots to help explain your problem.

**Environment:**
 - WordPress Version: [e.g. 6.4.2]
 - PHP Version: [e.g. 8.1.12]
 - Simple LMS Version: [e.g. 1.4.0]
 - Browser: [e.g. Chrome 120]
 - Other Plugins: [List active plugins that might be relevant]

**Additional context**
Add any other context about the problem here.

**Error Logs**
```
Paste any relevant error logs from wp-content/debug.log
```
```

### Feature Requests

**Use the Feature Request template:**

```markdown
**Is your feature request related to a problem? Please describe.**
A clear and concise description of what the problem is. Ex. I'm always frustrated when [...]

**Describe the solution you'd like**
A clear and concise description of what you want to happen.

**Describe alternatives you've considered**
A clear and concise description of any alternative solutions or features you've considered.

**Use Cases**
Who would benefit from this feature? How would they use it?

**Additional context**
Add any other context or screenshots about the feature request here.
```

### Issue Labels

| Label | Description | Usage |
|-------|-------------|-------|
| `bug` | Something isn't working | Confirmed bugs |
| `feature` | New feature request | Enhancement proposals |
| `enhancement` | Improvement to existing feature | Polish, UX improvements |
| `documentation` | Documentation improvements | Docs, guides, comments |
| `performance` | Performance-related issue | Slow queries, memory issues |
| `security` | Security vulnerability | Security concerns (private if severe) |
| `good first issue` | Good for newcomers | Entry-level contributions |
| `help wanted` | Extra attention needed | Community help requested |
| `wontfix` | This will not be worked on | Declined feature requests |
| `duplicate` | This issue already exists | Duplicate reports |

---

## Testing Requirements

### Minimum Coverage

All contributions must maintain or improve test coverage:

| Component | Minimum Coverage |
|-----------|------------------|
| New Classes | 80% |
| New Methods | 75% |
| Bug Fixes | 100% (regression test) |
| Overall Project | 75% |

### Writing Tests

See [TESTING.md](TESTING.md) for comprehensive testing guide.

**Every bug fix must include:**

1. **Failing test** that reproduces the bug
2. **Fix** that makes the test pass
3. **Documentation** of the issue in code comments

**Example:**

```php
/**
 * Test that lesson completion doesn't create duplicate entries
 *
 * Regression test for issue #123 where rapid lesson completion
 * created multiple progress records.
 *
 * @see https://github.com/simple-lms/simple-lms/issues/123
 */
public function testLessonCompletionPreventsDuplicates(): void {
    $user_id = 1;
    $lesson_id = 100;

    // Simulate rapid completion (race condition)
    Progress_Tracker::mark_lesson_complete($user_id, $lesson_id);
    Progress_Tracker::mark_lesson_complete($user_id, $lesson_id);

    // Should only have one completion record
    $records = $this->get_completion_records($user_id, $lesson_id);
    $this->assertCount(1, $records, 'Duplicate completion prevented');
}
```

### Running Tests Locally

```bash
# All tests
composer test

# Specific test file
vendor/bin/phpunit tests/Unit/ProgressTrackerTest.php

# With coverage
composer test:coverage
```

---

## Documentation

### What to Document

**Always update documentation when:**

- Adding new features → Update README.md, add examples
- Adding hooks → Document in HOOKS.md with parameters and examples
- Changing APIs → Update REST API documentation
- Modifying behavior → Update relevant guides
- Adding dependencies → Update installation docs

### Documentation Style

**Use clear, concise language:**

```markdown
<!-- ✅ Good: Clear, actionable -->
## Install Dependencies

Install PHP dependencies using Composer:

\`\`\`bash
composer install
\`\`\`

This installs PHPUnit, Brain Monkey, and code quality tools.

<!-- ❌ Bad: Vague, assumes knowledge -->
## Setup

You need to install stuff first. Run composer.
```

**Include code examples:**

```markdown
<!-- ✅ Good: Complete, executable example -->
## Granting Course Access

Use the `Access_Control` class to grant users access to courses:

\`\`\`php
use SimpleLMS\Access_Control;

// Grant access with WooCommerce order
Access_Control::grant_access(
    $user_id = 123,
    $course_id = 456,
    $order_id = 789
);

// Check access
if (Access_Control::user_has_access(123, 456)) {
    echo 'Access granted';
}
\`\`\`

<!-- ❌ Bad: Incomplete, unclear -->
## Access Control

You can grant access using the class. Just call the method.
```

### Documentation Checklist

- [ ] Code comments for complex logic
- [ ] PHPDoc blocks for all public methods
- [ ] README.md updated for user-facing changes
- [ ] HOOKS.md updated for new hooks
- [ ] TESTING.md updated for new test requirements
- [ ] SECURITY.md updated for security-related changes
- [ ] CHANGELOG.md updated

---

## Getting Help

### Communication Channels

- **GitHub Discussions**: General questions, feature discussions
- **GitHub Issues**: Bug reports, feature requests
- **Pull Request Comments**: Code review discussions
- **Documentation**: Check README.md, HOOKS.md, TESTING.md first

### Response Times

- **Bug Reports**: 2-3 business days
- **Feature Requests**: 1 week
- **Pull Requests**: 3-5 business days
- **Security Issues**: 24 hours

### Contact Maintainers

For security vulnerabilities, email: [security@example.com](mailto:security@example.com)

Do **not** open public issues for security vulnerabilities. See [SECURITY.md](SECURITY.md) for responsible disclosure process.

---

## License

By contributing to Simple LMS, you agree that your contributions will be licensed under the same license as the project (see LICENSE file).

---

## Recognition

Contributors will be recognized in:

- Project README.md (Contributors section)
- Release notes for features/fixes
- Annual contributors report

Thank you for contributing to Simple LMS! 🎉

---

## Related Documentation

- [HOOKS.md](HOOKS.md) - Hook reference for developers
- [TESTING.md](TESTING.md) - Testing guide and best practices
- [SECURITY.md](SECURITY.md) - Security guidelines and reporting
- [ARCHITECTURE.md](ARCHITECTURE.md) - Design decisions and patterns
- [README.md](README.md) - Plugin overview and features

---

**Last Updated:** December 2, 2025  
**Plugin Version:** 1.4.0
