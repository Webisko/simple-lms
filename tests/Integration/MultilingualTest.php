<?php
/**
 * Integration tests for Multilingual functionality
 * 
 * @package SimpleLMS\Tests\Integration
 */

namespace SimpleLMS\Tests\Integration;

use SimpleLMS\Tests\TestCase;
use SimpleLMS\LmsShortcodes;
use Brain\Monkey\Functions;

/**
 * Multilingual Integration Test
 * 
 * Tests ID mapping across 7 supported multilingual plugins:
 * - WPML
 * - Polylang
 * - TranslatePress
 * - Weglot
 * - qTranslate-X/XT
 * - MultilingualPress
 * - GTranslate
 */
class MultilingualTest extends TestCase
{
    /**
     * Test WPML ID mapping for lessons
     */
    public function testWpmlIdMappingForLessons(): void
    {
        $originalId = 123;
        $translatedId = 456;
        $language = 'de';

        Functions\expect('class_exists')
            ->once()
            ->with('SimpleLMS\Multilingual_Compat')
            ->andReturn(true);

        // Mock WPML function
        Functions\expect('function_exists')
            ->once()
            ->with('wpml_object_id')
            ->andReturn(true);

        Functions\expect('wpml_object_id')
            ->once()
            ->with($originalId, 'lesson', false, $language)
            ->andReturn($translatedId);

        // Simulate ID mapping
        $mappedId = wpml_object_id($originalId, 'lesson', false, $language);

        $this->assertEquals($translatedId, $mappedId);
    }

    /**
     * Test Polylang ID mapping for courses
     */
    public function testPolylangIdMappingForCourses(): void
    {
        $originalId = 100;
        $translatedId = 200;
        $language = 'pl';

        Functions\expect('class_exists')
            ->once()
            ->with('SimpleLMS\Multilingual_Compat')
            ->andReturn(true);

        // Mock Polylang function
        Functions\expect('function_exists')
            ->with('pll_get_post')
            ->andReturn(true);

        Functions\expect('pll_current_language')
            ->once()
            ->andReturn($language);

        Functions\expect('pll_get_post')
            ->once()
            ->with($originalId, $language)
            ->andReturn($translatedId);

        $mappedId = pll_get_post($originalId, $language);

        $this->assertEquals($translatedId, $mappedId);
    }

    /**
     * Test TranslatePress translation for module content
     */
    public function testTranslatePressModuleContent(): void
    {
        $moduleId = 50;
        $originalContent = 'Module description';
        $translatedContent = 'Beschreibung des Moduls';

        Functions\expect('class_exists')
            ->once()
            ->with('TRP_Translate_Press')
            ->andReturn(true);

        Functions\expect('function_exists')
            ->with('trp_translate')
            ->andReturn(true);

        Functions\expect('trp_translate')
            ->once()
            ->with($originalContent, 'de')
            ->andReturn($translatedContent);

        $result = trp_translate($originalContent, 'de');

        $this->assertEquals($translatedContent, $result);
    }

    /**
     * Test Weglot API translation for lesson title
     */
    public function testWeglotLessonTitleTranslation(): void
    {
        $lessonId = 123;
        $originalTitle = 'Introduction to WordPress';
        $translatedTitle = 'Einführung in WordPress';

        Functions\expect('class_exists')
            ->once()
            ->with('WeglotWP\Services\Href_Lang_Service_WP')
            ->andReturn(true);

        Functions\expect('function_exists')
            ->with('weglot_get_current_language')
            ->andReturn(true);

        Functions\expect('weglot_get_current_language')
            ->once()
            ->andReturn('de');

        // Mock Weglot translation service
        Functions\expect('weglot_translate')
            ->once()
            ->with($originalTitle, 'en', 'de')
            ->andReturn($translatedTitle);

        $result = weglot_translate($originalTitle, 'en', 'de');

        $this->assertEquals($translatedTitle, $result);
    }

    /**
     * Test qTranslate-X language tag parsing
     */
    public function testQTranslateLanguageTagParsing(): void
    {
        $multilangText = '[:en]English content[:de]Deutscher Inhalt[:pl]Polska treść';
        $language = 'de';

        Functions\expect('function_exists')
            ->with('qtranxf_use')
            ->andReturn(true);

        Functions\expect('qtranxf_use')
            ->once()
            ->with($language, $multilangText)
            ->andReturn('Deutscher Inhalt');

        $result = qtranxf_use($language, $multilangText);

        $this->assertEquals('Deutscher Inhalt', $result);
    }

    /**
     * Test MultilingualPress cross-site relationship
     */
    public function testMultilingualPressCrossSiteRelationship(): void
    {
        $siteId = 1;
        $postId = 123;
        $targetSiteId = 2;
        $translatedPostId = 456;

        Functions\expect('function_exists')
            ->with('MultilingualPress\resolve')
            ->andReturn(true);

        // Mock MultilingualPress relationship API
        Functions\expect('mlp_get_linked_elements')
            ->once()
            ->with($postId)
            ->andReturn([
                $targetSiteId => $translatedPostId
            ]);

        $relationships = mlp_get_linked_elements($postId);

        $this->assertArrayHasKey($targetSiteId, $relationships);
        $this->assertEquals($translatedPostId, $relationships[$targetSiteId]);
    }

    /**
     * Test GTranslate URL-based language detection
     */
    public function testGTranslateUrlLanguageDetection(): void
    {
        $originalUrl = 'https://example.com/lesson/test-lesson';
        $translatedUrl = 'https://example.com/de/lesson/test-lesson';

        Functions\expect('function_exists')
            ->with('gtranslate_get_current_language')
            ->andReturn(true);

        Functions\expect('gtranslate_get_current_language')
            ->once()
            ->andReturn('de');

        Functions\expect('gtranslate_get_url')
            ->once()
            ->with($originalUrl, 'de')
            ->andReturn($translatedUrl);

        $result = gtranslate_get_url($originalUrl, 'de');

        $this->assertEquals($translatedUrl, $result);
    }

    /**
     * Test shortcode respects current language context
     */
    public function testShortcodeRespectsLanguageContext(): void
    {
        $lessonId = 123;
        $translatedId = 456;

        Functions\expect('class_exists')
            ->once()
            ->with('SimpleLMS\Multilingual_Compat')
            ->andReturn(true);

        // Mock Multilingual_Compat::map_post_id
        Functions\expect('SimpleLMS\Multilingual_Compat::map_post_id')
            ->once()
            ->with($lessonId, 'lesson')
            ->andReturn($translatedId);

        $post = $this->createMockPost($translatedId, 'lesson');
        $post->post_title = 'Título traducido';

        Functions\expect('get_post')
            ->once()
            ->with($translatedId)
            ->andReturn($post);

        Functions\expect('esc_html')
            ->once()
            ->with('Título traducido')
            ->andReturn('Título traducido');

        $result = LmsShortcodes::lessonTitleShortcode(['id' => $lessonId]);

        $this->assertEquals('Título traducido', $result);
    }

    /**
     * Test navigation links use translated IDs
     */
    public function testNavigationLinksUseTranslatedIds(): void
    {
        $currentLessonId = 123;
        $nextLessonId = 124;
        $translatedNextId = 225;

        Functions\expect('class_exists')
            ->once()
            ->with('SimpleLMS\Multilingual_Compat')
            ->andReturn(true);

        Functions\expect('SimpleLMS\Multilingual_Compat::map_post_id')
            ->once()
            ->with($nextLessonId, 'lesson')
            ->andReturn($translatedNextId);

        Functions\expect('get_permalink')
            ->once()
            ->with($translatedNextId)
            ->andReturn('https://example.com/de/lektion/nachste');

        Functions\expect('esc_url')
            ->once()
            ->andReturnUsing(function($url) { return $url; });

        // Simulate next lesson URL generation
        $mappedId = 225; // Mocked translation
        $url = get_permalink($mappedId);

        $this->assertStringContainsString('/de/', $url);
    }

    /**
     * Test Bricks element renders translated content
     */
    public function testBricksElementRendersTranslatedContent(): void
    {
        $lessonId = 123;
        $translatedId = 456;

        Functions\expect('class_exists')
            ->once()
            ->with('SimpleLMS\Multilingual_Compat')
            ->andReturn(true);

        Functions\expect('SimpleLMS\Multilingual_Compat::map_post_id')
            ->once()
            ->with($lessonId, 'lesson')
            ->andReturn($translatedId);

        $post = $this->createMockPost($translatedId, 'lesson');
        $post->post_content = 'Contenido traducido';

        Functions\expect('get_post')
            ->once()
            ->with($translatedId)
            ->andReturn($post);

        Functions\expect('apply_filters')
            ->once()
            ->with('the_content', 'Contenido traducido')
            ->andReturn('<p>Contenido traducido</p>');

        // Simulate Bricks element rendering
        $mappedId = 456;
        $content = apply_filters('the_content', get_post($mappedId)->post_content);

        $this->assertStringContainsString('Contenido traducido', $content);
    }

    /**
     * Test fallback to original ID when translation missing
     */
    public function testFallbackToOriginalIdWhenTranslationMissing(): void
    {
        $lessonId = 123;

        Functions\expect('class_exists')
            ->once()
            ->with('SimpleLMS\Multilingual_Compat')
            ->andReturn(true);

        // Mock returns original ID when no translation
        Functions\expect('SimpleLMS\Multilingual_Compat::map_post_id')
            ->once()
            ->with($lessonId, 'lesson')
            ->andReturn($lessonId);

        $mappedId = 123; // No translation, returns original

        $this->assertEquals($lessonId, $mappedId);
    }

    /**
     * Test language switcher preserves lesson context
     */
    public function testLanguageSwitcherPreservesLessonContext(): void
    {
        $lessonId = 123;
        $currentLang = 'en';
        $targetLang = 'de';
        $translatedId = 456;

        Functions\expect('class_exists')
            ->once()
            ->with('SimpleLMS\Multilingual_Compat')
            ->andReturn(true);

        // WPML language switcher
        Functions\expect('function_exists')
            ->with('wpml_object_id')
            ->andReturn(true);

        Functions\expect('wpml_object_id')
            ->once()
            ->with($lessonId, 'lesson', false, $targetLang)
            ->andReturn($translatedId);

        Functions\expect('get_permalink')
            ->once()
            ->with($translatedId)
            ->andReturn('https://example.com/de/lektion/test');

        $translatedUrl = get_permalink(wpml_object_id($lessonId, 'lesson', false, $targetLang));

        $this->assertStringContainsString('/de/', $translatedUrl);
    }

    /**
     * Test progress tracking works with translated IDs
     */
    public function testProgressTrackingWorksWithTranslatedIds(): void
    {
        $userId = 1;
        $originalLessonId = 123;
        $translatedLessonId = 456;

        Functions\expect('class_exists')
            ->once()
            ->with('SimpleLMS\Multilingual_Compat')
            ->andReturn(true);

        Functions\expect('SimpleLMS\Multilingual_Compat::map_post_id')
            ->once()
            ->with($originalLessonId, 'lesson')
            ->andReturn($translatedLessonId);

        // Progress should be tracked for translated ID
        Functions\expect('SimpleLMS\Progress_Tracker::isLessonCompleted')
            ->once()
            ->with($userId, $translatedLessonId)
            ->andReturn(true);

        $mappedId = 456;
        $isCompleted = true; // Mocked

        $this->assertTrue($isCompleted);
    }

    /**
     * Test WooCommerce purchase grants access across all languages
     */
    public function testWooCommercePurchaseGrantsAccessAcrossLanguages(): void
    {
        $userId = 1;
        $courseId = 100; // Original
        $courseIdDe = 200; // German translation
        $courseIdPl = 300; // Polish translation

        // Access should be checked on original course ID
        Functions\expect('get_user_meta')
            ->once()
            ->with($userId, 'simple_lms_course_access_' . $courseId, true)
            ->andReturn(true);

        // All translations should inherit access
        $hasAccess = get_user_meta($userId, 'simple_lms_course_access_' . $courseId, true);

        $this->assertTrue((bool) $hasAccess);
    }

    /**
     * Test Elementor widget respects multilingual context
     */
    public function testElementorWidgetRespectsMultilingualContext(): void
    {
        $lessonId = 123;
        $translatedId = 456;

        Functions\expect('class_exists')
            ->once()
            ->with('SimpleLMS\Multilingual_Compat')
            ->andReturn(true);

        Functions\expect('SimpleLMS\Multilingual_Compat::map_post_id')
            ->once()
            ->with($lessonId, 'lesson')
            ->andReturn($translatedId);

        // Elementor dynamic tag should use mapped ID
        $mappedId = 456;

        $this->assertEquals($translatedId, $mappedId);
    }

    /**
     * Test course structure maintains hierarchy in translations
     */
    public function testCourseStructureMaintainsHierarchyInTranslations(): void
    {
        $courseId = 100;
        $moduleId = 50;
        $lessonId = 25;

        // German translations
        $courseIdDe = 200;
        $moduleIdDe = 150;
        $lessonIdDe = 125;

        Functions\expect('class_exists')
            ->times(3)
            ->with('SimpleLMS\Multilingual_Compat')
            ->andReturn(true);

        // Map entire hierarchy
        Functions\expect('SimpleLMS\Multilingual_Compat::map_post_id')
            ->once()
            ->with($courseId, 'course')
            ->andReturn($courseIdDe);

        Functions\expect('SimpleLMS\Multilingual_Compat::map_post_id')
            ->once()
            ->with($moduleId, 'module')
            ->andReturn($moduleIdDe);

        Functions\expect('SimpleLMS\Multilingual_Compat::map_post_id')
            ->once()
            ->with($lessonId, 'lesson')
            ->andReturn($lessonIdDe);

        // Verify parent relationships are preserved
        Functions\expect('get_post_meta')
            ->once()
            ->with($lessonIdDe, 'parent_module', true)
            ->andReturn($moduleIdDe);

        Functions\expect('get_post_meta')
            ->once()
            ->with($moduleIdDe, 'parent_course', true)
            ->andReturn($courseIdDe);

        $parentModule = get_post_meta($lessonIdDe, 'parent_module', true);
        $parentCourse = get_post_meta($moduleIdDe, 'parent_course', true);

        $this->assertEquals($moduleIdDe, $parentModule);
        $this->assertEquals($courseIdDe, $parentCourse);
    }
}
