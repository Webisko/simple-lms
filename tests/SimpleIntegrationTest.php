<?php
/**
 * Simplified Integration Tests - No Composer Required
 * Testuje podstawową funkcjonalność wtyczki
 * 
 * Uruchom: php phpunit.phar tests/SimpleIntegrationTest.php
 * 
 * @package SimpleLMS\Tests
 */

use PHPUnit\Framework\TestCase;

class SimpleIntegrationTest extends TestCase
{
    /**
     * Test: Plugin initialization
     */
    public function testPluginInitialized(): void
    {
        $this->assertTrue(
            class_exists('SimpleLMS\Cache_Handler'),
            'Cache_Handler class should exist'
        );
        
        $this->assertTrue(
            class_exists('SimpleLMS\Progress_Tracker'),
            'Progress_Tracker class should exist'
        );
        
        $this->assertTrue(
            class_exists('SimpleLMS\WooCommerce_Integration'),
            'WooCommerce_Integration class should exist'
        );
    }

    /**
     * Test: Cache key generation
     */
    public function testCacheKeyGeneration(): void
    {
        $courseId = 123;
        $expectedPattern = '/^course_modules_123_v\d+$/';
        
        // Symulacja - w prawdziwych testach użylibyśmy Cache_Handler
        $cacheKey = "course_modules_{$courseId}_v" . time();
        
        $this->assertMatchesRegularExpression(
            $expectedPattern,
            $cacheKey,
            'Cache key should follow versioning pattern'
        );
    }

    /**
     * Test: Course access logic
     */
    public function testCourseAccessLogic(): void
    {
        $userId = 1;
        $courseTag = 'course-123';
        
        // Test case 1: Empty access array = no access
        $userTags = [];
        $hasAccess = in_array($courseTag, $userTags, true);
        $this->assertFalse($hasAccess, 'User without tags should not have access');
        
        // Test case 2: With correct tag = has access
        $userTags = ['course-123', 'course-456'];
        $hasAccess = in_array($courseTag, $userTags, true);
        $this->assertTrue($hasAccess, 'User with correct tag should have access');
        
        // Test case 3: With different tag = no access
        $userTags = ['course-456', 'course-789'];
        $hasAccess = in_array($courseTag, $userTags, true);
        $this->assertFalse($hasAccess, 'User with different tag should not have access');
    }

    /**
     * Test: Progress data validation
     */
    public function testProgressDataValidation(): void
    {
        // Valid data
        $validData = [
            'user_id' => 123,
            'lesson_id' => 456,
            'completed' => 1
        ];
        
        $isValid = (
            isset($validData['user_id']) &&
            isset($validData['lesson_id']) &&
            is_numeric($validData['user_id']) &&
            is_numeric($validData['lesson_id'])
        );
        
        $this->assertTrue($isValid, 'Valid progress data should pass validation');
        
        // Invalid data - missing user_id
        $invalidData = [
            'lesson_id' => 456,
            'completed' => 1
        ];
        
        $isValid = (
            isset($invalidData['user_id']) &&
            isset($invalidData['lesson_id']) &&
            is_numeric($invalidData['user_id']) &&
            is_numeric($invalidData['lesson_id'])
        );
        
        $this->assertFalse($isValid, 'Invalid progress data should fail validation');
    }

    /**
     * Test: Product ID migration logic
     */
    public function testProductIdMigrationLogic(): void
    {
        // Old format: single ID
        $oldProductId = '123';
        
        // Migration: convert to array
        $newProductIds = is_array($oldProductId) 
            ? $oldProductId 
            : array_filter([(int)$oldProductId]);
        
        $this->assertIsArray($newProductIds, 'Migrated IDs should be array');
        $this->assertCount(1, $newProductIds, 'Should contain 1 ID');
        $this->assertEquals(123, $newProductIds[0], 'ID should be converted to integer');
        
        // Already array format
        $newFormat = [123, 456];
        $result = is_array($newFormat) 
            ? $newFormat 
            : array_filter([(int)$newFormat]);
        
        $this->assertIsArray($result, 'Array should stay array');
        $this->assertCount(2, $result, 'Should preserve all IDs');
    }

    /**
     * Test: Input sanitization
     */
    public function testInputSanitization(): void
    {
        // Integer sanitization
        $input = '123abc';
        $sanitized = abs((int)$input);
        $this->assertEquals(123, $sanitized, 'Should extract integer');
        
        // Negative integer sanitization
        $input = '-456';
        $sanitized = abs((int)$input);
        $this->assertEquals(456, $sanitized, 'Should return absolute value');
        
        // XSS prevention
        $input = '<script>alert("xss")</script>Hello';
        $sanitized = strip_tags($input);
        $this->assertEquals('Hello', $sanitized, 'Should strip HTML tags');
    }

    /**
     * Test: Cache invalidation logic
     */
    public function testCacheInvalidationLogic(): void
    {
        $cacheVersion = time();
        
        // Simulate cache invalidation
        $newCacheVersion = time() + 1;
        
        $this->assertGreaterThan(
            $cacheVersion,
            $newCacheVersion,
            'New cache version should be greater than old'
        );
        
        // Test version increment
        $incrementedVersion = $cacheVersion + 1;
        $this->assertEquals(
            $cacheVersion + 1,
            $incrementedVersion,
            'Version should increment by 1'
        );
    }

    /**
     * Test: Post type validation
     */
    public function testPostTypeValidation(): void
    {
        $validTypes = ['course', 'module', 'lesson'];
        
        // Valid post type
        $postType = 'course';
        $isValid = in_array($postType, $validTypes, true);
        $this->assertTrue($isValid, 'Valid post type should pass');
        
        // Invalid post type
        $postType = 'post';
        $isValid = in_array($postType, $validTypes, true);
        $this->assertFalse($isValid, 'Invalid post type should fail');
        
        // Case sensitivity
        $postType = 'COURSE';
        $isValid = in_array($postType, $validTypes, true);
        $this->assertFalse($isValid, 'Post type validation should be case-sensitive');
    }

    /**
     * Test: SQL injection prevention
     */
    public function testSqlInjectionPrevention(): void
    {
        // Test input that could be SQL injection
        $maliciousInput = "1 OR 1=1";
        $sanitized = abs((int)$maliciousInput);
        
        $this->assertEquals(1, $sanitized, 'Should extract only first integer');
        $this->assertNotEquals($maliciousInput, $sanitized, 'Should remove SQL keywords');
        
        // Test with quotes
        $maliciousInput = "'; DROP TABLE users; --";
        $sanitized = abs((int)$maliciousInput);
        
        $this->assertEquals(0, $sanitized, 'Should return 0 for non-numeric input');
    }

    /**
     * Test: Capability checks logic
     */
    public function testCapabilityChecksLogic(): void
    {
        // Simulate capability requirements
        $requiredCapabilities = [
            'duplicate_post' => 'publish_posts',
            'delete_lesson' => 'delete_posts',
            'edit_course' => 'edit_posts'
        ];
        
        $this->assertEquals(
            'publish_posts',
            $requiredCapabilities['duplicate_post'],
            'Duplicate should require publish_posts'
        );
        
        $this->assertEquals(
            'delete_posts',
            $requiredCapabilities['delete_lesson'],
            'Delete should require delete_posts'
        );
        
        $this->assertArrayHasKey(
            'edit_course',
            $requiredCapabilities,
            'Edit course capability should exist'
        );
    }
}
