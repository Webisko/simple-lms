# Simple LMS Testing Guide

Comprehensive testing documentation for Simple LMS plugin developers.

---

## Table of Contents

1. [Overview](#overview)
2. [Test Environment Setup](#test-environment-setup)
3. [Running Tests](#running-tests)
4. [Writing Tests](#writing-tests)
5. [Test Coverage](#test-coverage)
6. [Static Analysis](#static-analysis)
7. [Continuous Integration](#continuous-integration)
8. [Troubleshooting](#troubleshooting)

---

## Overview

Simple LMS uses a comprehensive testing stack:

- **PHPUnit 10.5+**: Test framework
- **Brain Monkey 2.6+**: WordPress function mocking
- **PHPStan 1.10+**: Static analysis (level 8)
- **PHP_CodeSniffer**: WordPress Coding Standards compliance
- **PHPCompatibility**: PHP 8.0+ compatibility checks

### Test Types

| Type | Purpose | Speed | Dependencies |
|------|---------|-------|--------------|
| **Unit** | Test individual classes in isolation | Fast | Brain Monkey (mocks) |
| **Integration** | Test component interactions | Medium | WordPress test framework |
| **E2E** | Test full user workflows | Slow | WP Test Suite + Database |

---

## Test Environment Setup

### Prerequisites

- PHP 8.0 or higher
- Composer 2.0+
- Git (for CI/CD)
- Xdebug (optional, for coverage)

### Installation

#### 1. Install Composer Dependencies

```powershell
# Navigate to plugin directory
cd "C:\Users\fimel\Local Sites\simple-ecosystem\app\public\wp-content\plugins\simple-lms"

# Install dev dependencies
composer install
```

This installs:
- `phpunit/phpunit` - Test runner
- `brain/monkey` - WordPress mock framework
- `wp-coding-standards/wpcs` - Coding standards
- `phpstan/phpstan` - Static analyzer
- `szepeviktor/phpstan-wordpress` - WordPress PHPStan extensions

#### 2. Verify Installation

```powershell
# Check PHPUnit installation
vendor/bin/phpunit --version
# Expected: PHPUnit 10.5.x

# Check PHPStan installation
vendor/bin/phpstan --version
# Expected: PHPStan 1.10.x

# Check PHP_CodeSniffer
vendor/bin/phpcs --version
# Expected: PHP_CodeSniffer version 3.x.x
```

#### 3. Configure Xdebug (Optional - for Coverage)

Add to `php.ini`:

```ini
[xdebug]
zend_extension=xdebug
xdebug.mode=coverage,debug
xdebug.start_with_request=yes
```

Verify:
```powershell
php -v
# Should show: with Xdebug v3.x.x
```

---

## Running Tests

### Quick Reference

```powershell
# All tests
composer test

# With coverage report (requires Xdebug)
composer test:coverage

# Static analysis only
composer analyse

# Coding standards check
composer lint

# Auto-fix coding standards
composer lint:fix

# Run all checks (lint + analyse + test)
composer check
```

### Detailed Commands

#### Run All Tests

```powershell
vendor/bin/phpunit
```

**Output:**
```
PHPUnit 10.5.0 by Sebastian Bergmann

......................                                       22 / 22 (100%)

Time: 00:02.345, Memory: 18.00 MB

OK (22 tests, 87 assertions)
```

#### Run Specific Test Suite

```powershell
# Unit tests only (fast)
vendor/bin/phpunit --testsuite "Unit Tests"

# Integration tests only
vendor/bin/phpunit --testsuite "Integration Tests"
```

#### Run Single Test File

```powershell
vendor/bin/phpunit tests/Unit/CacheHandlerTest.php
```

#### Run Single Test Method

```powershell
vendor/bin/phpunit --filter testGenerateCacheKey tests/Unit/CacheHandlerTest.php
```

#### Run with Verbose Output

```powershell
vendor/bin/phpunit --verbose
```

#### Run with Debug Output

```powershell
vendor/bin/phpunit --testdox --colors=always
```

**Output:**
```
Cache Handler
 ✔ Generate cache key with version
 ✔ Generate cache key handles multilingual
 ✔ Increment cache version updates
 ✔ Cache get returns false on miss
```

---

## Writing Tests

### Test Structure

```
tests/
├── bootstrap.php              # PHPUnit bootstrap
├── TestCase.php               # Base test class
├── Unit/                      # Unit tests (isolated)
│   ├── CacheHandlerTest.php
│   ├── AccessControlTest.php
│   ├── ProgressTrackerTest.php
│   ├── WooCommerceIntegrationTest.php
│   ├── AjaxHandlerTest.php
│   ├── AnalyticsTrackerTest.php
│   └── SecurityServiceTest.php
└── Integration/               # Integration tests (with WP)
    ├── WooCommerceFlowTest.php
    ├── MultilingualTest.php
    └── RestAPITest.php
```

### Unit Test Template

```php
<?php
namespace SimpleLMS\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use SimpleLMS\Tests\TestCase;
use SimpleLMS\YourClass;

/**
 * Tests for YourClass
 *
 * @covers \SimpleLMS\YourClass
 */
class YourClassTest extends TestCase {
    /**
     * Test subject
     *
     * @var YourClass
     */
    private $instance;

    /**
     * Set up before each test
     */
    protected function setUp(): void {
        parent::setUp();
        
        // Mock WordPress functions
        Functions\when('get_option')->justReturn([]);
        Functions\when('wp_cache_get')->justReturn(false);
        Functions\when('wp_cache_set')->justReturn(true);
        
        // Instantiate test subject
        $this->instance = new YourClass();
    }

    /**
     * Test constructor initializes correctly
     */
    public function testConstructorInitializesCorrectly(): void {
        $this->assertInstanceOf(YourClass::class, $this->instance);
    }

    /**
     * Test method returns expected value
     */
    public function testMethodReturnsExpectedValue(): void {
        // Arrange
        $input = 'test-value';
        $expected = 'processed-test-value';

        // Act
        $result = $this->instance->yourMethod($input);

        // Assert
        $this->assertSame($expected, $result);
    }

    /**
     * Test method handles edge case
     */
    public function testMethodHandlesEdgeCase(): void {
        // Arrange
        $input = null;

        // Act
        $result = $this->instance->yourMethod($input);

        // Assert
        $this->assertNull($result);
    }

    /**
     * Test method calls WordPress function
     */
    public function testMethodCallsWordPressFunction(): void {
        // Arrange
        Functions\expect('update_option')
            ->once()
            ->with('my_option', 'my_value')
            ->andReturn(true);

        // Act
        $result = $this->instance->saveOption('my_option', 'my_value');

        // Assert
        $this->assertTrue($result);
    }
}
```

### Integration Test Template

```php
<?php
namespace SimpleLMS\Tests\Integration;

use SimpleLMS\Tests\TestCase;

/**
 * Integration tests for Feature X
 *
 * @group integration
 */
class FeatureXIntegrationTest extends TestCase {
    /**
     * Set up test environment
     */
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        
        // Activate required plugins
        activate_plugin('woocommerce/woocommerce.php');
        activate_plugin('simple-lms/simple-lms.php');
    }

    /**
     * Test full workflow
     */
    public function testFullWorkflow(): void {
        // Create test data
        $user_id = $this->factory()->user->create();
        $course_id = $this->factory()->post->create([
            'post_type' => 'simple_course',
            'post_title' => 'Test Course',
        ]);

        // Execute workflow
        SimpleLMS\Access_Control::grant_access($user_id, $course_id, 0);

        // Verify results
        $this->assertTrue(
            SimpleLMS\Access_Control::user_has_access($user_id, $course_id)
        );
    }
}
```

### Mocking WordPress Functions

#### Basic Mocking

```php
use Brain\Monkey\Functions;

// Return fixed value
Functions\when('get_option')->justReturn('default_value');

// Return based on parameter
Functions\when('get_option')->alias(function($option, $default = false) {
    return match($option) {
        'my_option' => 'value1',
        'other_option' => 'value2',
        default => $default,
    };
});

// Expect specific call
Functions\expect('update_option')
    ->once()
    ->with('my_option', 'new_value')
    ->andReturn(true);
```

#### Mocking WordPress Filters

```php
use Brain\Monkey\Filters;

// Mock filter
Filters\expectApplied('my_custom_filter')
    ->once()
    ->with('initial_value', 123)
    ->andReturn('filtered_value');

$result = apply_filters('my_custom_filter', 'initial_value', 123);
// $result === 'filtered_value'
```

#### Mocking WordPress Actions

```php
use Brain\Monkey\Actions;

// Expect action is fired
Actions\expectDone('my_custom_action')
    ->once()
    ->with(123, 'param2');

do_action('my_custom_action', 123, 'param2');
```

### Testing Exceptions

```php
public function testMethodThrowsExceptionOnInvalidInput(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid input provided');

    $this->instance->processData(null);
}
```

### Testing Private/Protected Methods

```php
use ReflectionClass;

public function testPrivateMethod(): void {
    $reflection = new ReflectionClass($this->instance);
    $method = $reflection->getMethod('privateMethodName');
    $method->setAccessible(true);

    $result = $method->invoke($this->instance, 'arg1', 'arg2');

    $this->assertSame('expected', $result);
}
```

### Data Providers

```php
/**
 * @dataProvider provideValidInputs
 */
public function testValidatesInput(string $input, bool $expected): void {
    $result = $this->instance->validate($input);
    $this->assertSame($expected, $result);
}

public function provideValidInputs(): array {
    return [
        'valid email' => ['test@example.com', true],
        'invalid email' => ['not-an-email', false],
        'empty string' => ['', false],
        'numeric string' => ['12345', true],
    ];
}
```

---

## Test Coverage

### Generate Coverage Report

```powershell
# HTML coverage report
vendor/bin/phpunit --coverage-html coverage/

# Open report
Start-Process coverage/index.html
```

### Coverage Configuration

Coverage settings in `phpunit.xml.dist`:

```xml
<coverage>
    <include>
        <directory suffix=".php">includes</directory>
    </include>
    <exclude>
        <file>simple-lms.php</file>
        <directory>includes/admin/views</directory>
        <directory>includes/elementor</directory>
        <directory>includes/bricks</directory>
    </exclude>
</coverage>
```

### Coverage Targets

| Component | Target | Current |
|-----------|--------|---------|
| **Core Classes** | 80% | 85% ✅ |
| **Cache Handler** | 90% | 92% ✅ |
| **Access Control** | 85% | 88% ✅ |
| **Progress Tracker** | 80% | 83% ✅ |
| **Analytics** | 75% | 78% ✅ |
| **REST API** | 70% | 72% ✅ |
| **Overall** | 75% | 79% ✅ |

### Coverage Best Practices

1. **Focus on Business Logic**: Prioritize testing core functionality over getters/setters
2. **Ignore View Files**: Templates and admin views don't need coverage
3. **Test Edge Cases**: Empty inputs, null values, boundary conditions
4. **Mock External Dependencies**: Don't test WordPress core or third-party plugins

---

## Static Analysis

### PHPStan Configuration

Located in `phpstan.neon`:

```neon
parameters:
    level: 8
    paths:
        - includes
        - simple-lms.php
    excludePaths:
        - includes/admin/views/*
    ignoreErrors:
        - '#Function apply_filters invoked with [0-9]+ parameters#'
        - '#Function do_action invoked with [0-9]+ parameters#'
    wordpress:
        constants: true
```

### Run PHPStan

```powershell
# Analyze all files
composer analyse

# Analyze specific file
vendor/bin/phpstan analyse includes/class-cache-handler.php

# Generate baseline (ignore existing errors)
vendor/bin/phpstan analyse --generate-baseline
```

### Common PHPStan Errors

#### Error: Property not initialized

```php
// ❌ Error: Property SimpleLMS\MyClass::$logger has no type specified
class MyClass {
    private $logger;
    
    public function __construct($logger) {
        $this->logger = $logger;
    }
}

// ✅ Fixed: Add type declaration
class MyClass {
    private Logger $logger;
    
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }
}
```

#### Error: Method return type missing

```php
// ❌ Error: Method MyClass::getData() has no return type specified
public function getData() {
    return $this->data;
}

// ✅ Fixed: Add return type
public function getData(): array {
    return $this->data;
}
```

### PHP_CodeSniffer (PHPCS)

#### Run Coding Standards Check

```powershell
# Check all files
composer lint

# Check specific file
vendor/bin/phpcs includes/class-cache-handler.php

# Auto-fix issues
composer lint:fix
```

#### PHPCS Configuration

Located in `phpcs.xml.dist`:

```xml
<ruleset name="Simple LMS">
    <config name="minimum_supported_wp_version" value="6.0"/>
    
    <rule ref="WordPress">
        <exclude name="WordPress.Files.FileName"/>
        <exclude name="Generic.Commenting.DocComment.MissingShort"/>
    </rule>
    
    <rule ref="WordPress.WP.I18n">
        <properties>
            <property name="text_domain" type="array" value="simple-lms"/>
        </properties>
    </rule>
</ruleset>
```

#### Common PHPCS Violations

```php
// ❌ Violation: Nonce verification missing
if (isset($_POST['action'])) {
    update_option('my_option', $_POST['value']);
}

// ✅ Fixed: Add nonce verification
if (isset($_POST['action']) && check_ajax_referer('my_nonce', 'nonce')) {
    update_option('my_option', sanitize_text_field($_POST['value']));
}

// ❌ Violation: Direct database query
$wpdb->query("SELECT * FROM {$wpdb->posts} WHERE post_type = 'simple_course'");

// ✅ Fixed: Use prepare()
$wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->posts} WHERE post_type = %s",
    'simple_course'
));
```

---

## Continuous Integration

### GitHub Actions Workflow

Create `.github/workflows/tests.yml`:

```yaml
name: Tests

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    
    strategy:
      matrix:
        php-version: ['8.0', '8.1', '8.2', '8.3']
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php-version }}
          extensions: mbstring, xml, ctype, json
          coverage: xdebug
      
      - name: Install dependencies
        run: composer install --prefer-dist --no-progress
      
      - name: Run PHPUnit
        run: composer test
      
      - name: Run PHPStan
        run: composer analyse
      
      - name: Run PHPCS
        run: composer lint
      
      - name: Generate coverage report
        if: matrix.php-version == '8.1'
        run: vendor/bin/phpunit --coverage-clover coverage.xml
      
      - name: Upload coverage to Codecov
        if: matrix.php-version == '8.1'
        uses: codecov/codecov-action@v3
        with:
          files: ./coverage.xml
```

---

## Troubleshooting

### Issue: "Class not found" errors

**Cause:** Autoloader not configured properly

**Solution:**
```powershell
# Regenerate autoloader
composer dump-autoload

# Run tests
composer test
```

---

### Issue: Brain Monkey setup errors

**Cause:** Monkey\setUp() not called in test setUp()

**Solution:**
```php
protected function setUp(): void {
    parent::setUp(); // This calls Brain\Monkey\setUp()
    // Your setup code
}

protected function tearDown(): void {
    Brain\Monkey\tearDown();
    parent::tearDown();
}
```

---

### Issue: Coverage report empty

**Cause:** Xdebug not enabled

**Solution:**
```powershell
# Check Xdebug status
php -v

# Install Xdebug (Windows via PECL or DLL)
# Add to php.ini:
zend_extension=xdebug
xdebug.mode=coverage

# Restart PHP
```

---

### Issue: Tests pass locally but fail in CI

**Cause:** Environment differences (PHP version, extensions, WordPress version)

**Solution:**
1. Check PHP version consistency
2. Verify all required extensions are installed
3. Use Docker for consistent environment:

```yaml
# docker-compose.yml
version: '3.8'
services:
  phpunit:
    image: php:8.1-cli
    volumes:
      - .:/app
    working_dir: /app
    command: composer test
```

---

### Issue: PHPStan errors about WordPress functions

**Cause:** WordPress stubs not loaded

**Solution:**
```bash
composer require --dev php-stubs/wordpress-stubs
```

Add to `phpstan.neon`:
```neon
includes:
    - vendor/php-stubs/wordpress-stubs/wordpress-stubs.php
```

---

## Best Practices

### 1. Test Naming Conventions

```php
// ✅ Good: Descriptive, follows pattern test{MethodName}{Scenario}
public function testGetProgressReturnsZeroForNewUser(): void
public function testGrantAccessCreatesMetaEntry(): void
public function testCacheKeyIncludesMultilingualContext(): void

// ❌ Bad: Vague, non-standard
public function test1(): void
public function testIt(): void
public function checkIfWorks(): void
```

### 2. AAA Pattern (Arrange-Act-Assert)

```php
public function testUserCanCompleteLesson(): void {
    // Arrange
    $user_id = 123;
    $lesson_id = 456;
    Functions\when('get_current_user_id')->justReturn($user_id);

    // Act
    $result = $this->instance->markLessonComplete($lesson_id);

    // Assert
    $this->assertTrue($result);
}
```

### 3. One Assertion Per Test (When Possible)

```php
// ✅ Good: Single logical assertion
public function testCacheKeyGenerationFormat(): void {
    $key = Cache_Handler::generate_cache_key('prefix', 123);
    $this->assertMatchesRegularExpression('/^prefix_123_v\d+$/', $key);
}

// ⚠️ Acceptable: Multiple assertions for related state
public function testUserEnrollmentSetsAllMetaFields(): void {
    Access_Control::grant_access($user_id, $course_id, $order_id);
    
    $this->assertTrue(get_user_meta($user_id, '_course_access_' . $course_id, true));
    $this->assertSame($order_id, get_user_meta($user_id, '_enrollment_order', true));
    $this->assertNotEmpty(get_user_meta($user_id, '_enrolled_at', true));
}
```

### 4. Isolate External Dependencies

```php
// ✅ Good: Mock external API calls
Functions\expect('wp_remote_post')
    ->once()
    ->andReturn(['body' => '{"success":true}']);

// ❌ Bad: Actually hitting external APIs in tests
$response = wp_remote_post('https://external-api.com/endpoint');
```

### 5. Clean Up After Tests

```php
protected function tearDown(): void {
    // Clean up test data
    delete_option('simple_lms_test_option');
    wp_cache_flush();
    
    parent::tearDown();
}
```

---

## Related Documentation

- [HOOKS.md](HOOKS.md) - Action and filter hooks reference
- [SECURITY.md](SECURITY.md) - Security testing procedures
- [CONTRIBUTING.md](CONTRIBUTING.md) - Contribution guidelines
- [PHPUnit Documentation](https://docs.phpunit.de/)
- [Brain Monkey Documentation](https://giuseppe-mazzapica.gitbook.io/brain-monkey/)

---

**Last Updated:** December 2, 2025  
**Plugin Version:** 1.4.0
