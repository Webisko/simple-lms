# E2E Testing Guide for Simple LMS

## Overview
This guide covers end-to-end and integration testing for Simple LMS widgets and functionality.

## Test Structure

### Unit Tests (`tests/Unit/`)
- **AccessControlTest.php** - Access control logic, tag assignment/removal
- **ProgressTrackerTest.php** - Progress tracking, caching, calculations
- **CacheHandlerTest.php** - Course/module/lesson caching
- **AjaxHandlerTest.php** - AJAX endpoint validation
- **WooCommerceIntegrationTest.php** - Product linking, access grants

### Integration Tests (`tests/Integration/`)
- **ElementorWidgetsTest.php** - Widget rendering with WordPress context
- Tests verify:
  - Access control integration
  - Progress calculation accuracy
  - Cache invalidation
  - Widget fallback behavior
  - Navigation logic

## Running Tests

### PHPUnit Unit Tests
```powershell
# Install dependencies
composer install

# Run all unit tests
vendor\bin\phpunit --testsuite "Simple LMS Unit Tests"

# Run specific test file
vendor\bin\phpunit tests\Unit\AccessControlTest.php

# With code coverage (requires Xdebug)
vendor\bin\phpunit --coverage-html coverage
```

### WordPress Integration Tests (wp-env)
```bash
# Install wp-env globally (first time only)
npm install -g @wordpress/env

# Start WordPress test environment
wp-env start

# Run integration tests
wp-env run tests-cli --path=/var/www/html vendor/bin/phpunit --configuration phpunit-integration.xml

# Stop environment
wp-env stop
```

## Test Environment Setup

### wp-env Configuration
The `.wp-env.json` file configures a complete WordPress environment with:
- WordPress 6.4+
- PHP 8.1
- WooCommerce (latest stable)
- Elementor (latest stable)
- TwentyTwentyFour theme

### Manual Testing Checklist

#### Widget Rendering
- [ ] Lesson content widget shows content with access
- [ ] Lesson content widget shows fallback without access
- [ ] Course navigation renders all modules/lessons
- [ ] Lesson navigation shows prev/next correctly
- [ ] Progress indicators display accurate percentages

#### Access Control
- [ ] Admin users bypass all restrictions
- [ ] Users without tags see "no access" message
- [ ] Expired access correctly revokes permissions
- [ ] WooCommerce purchase grants access automatically

#### Progress Tracking
- [ ] Lesson completion updates progress
- [ ] Course progress percentage calculates correctly
- [ ] Continue learning button links to last viewed lesson
- [ ] Progress cache invalidates on completion

#### Editor Fallbacks
- [ ] Widgets show helpful messages in Elementor editor
- [ ] Bricks elements render preview content
- [ ] Invalid post IDs don't crash editor

## Writing New Tests

### Unit Test Template
```php
<?php
namespace SimpleLMS\Tests\Unit;

use SimpleLMS\Tests\TestCase;
use Brain\Monkey\Functions;

class YourTest extends TestCase {
    public function testSomething(): void {
        Functions\expect('get_user_meta')
            ->once()
            ->andReturn([123]);
            
        $result = \SimpleLMS\your_function(123);
        $this->assertTrue($result);
    }
}
```

### Integration Test Template
```php
<?php
namespace SimpleLMS\Tests\Integration;

use WP_UnitTestCase;

class YourIntegrationTest extends WP_UnitTestCase {
    public function testWithWordPress(): void {
        $postId = $this->factory()->post->create([
            'post_type' => 'course'
        ]);
        
        $this->assertGreaterThan(0, $postId);
    }
}
```

## CI/CD Integration

### GitHub Actions (Future)
Planned workflow for automated testing:
- Run PHPUnit unit tests on every PR
- Run integration tests weekly
- Generate coverage reports
- Test against WordPress 6.0, 6.1, 6.2, 6.3, 6.4
- Test against PHP 8.0, 8.1, 8.2, 8.3

### Local Pre-commit Hook
```bash
# .git/hooks/pre-commit
#!/bin/sh
vendor/bin/phpunit --testsuite "Simple LMS Unit Tests"
```

## Troubleshooting

### wp-env Issues
```bash
# Clear environment and rebuild
wp-env destroy
wp-env start

# Check logs
wp-env logs

# Access WP CLI
wp-env run cli wp --info
```

### PHPUnit Issues
```powershell
# Clear cache
vendor\bin\phpunit --cache-clear

# Verbose output
vendor\bin\phpunit --verbose

# Debug specific test
vendor\bin\phpunit --filter testMethodName --debug
```

## Test Coverage Goals
- **Unit Tests**: 80%+ coverage of core logic
- **Integration Tests**: 100% of critical user flows
- **Manual Testing**: All widgets in both Elementor and Bricks

## Resources
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Brain Monkey](https://giuseppe-mazzapica.gitbook.io/brain-monkey/)
- [WordPress Test Suite](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/)
- [wp-env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/)
