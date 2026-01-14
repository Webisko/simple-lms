<?php
/**
 * Comprehensive integration tests for Simple LMS Plugin
 * Tests all core functionality including Elementor and Bricks integrations
 *
 * @package SimpleLMS\Tests
 */

namespace SimpleLMS\Tests\Integration;

use PHPUnit\Framework\TestCase;

class FullPluginTest extends TestCase {
    
    /**
     * Test if main plugin class exists and initializes
     */
    public function test_plugin_main_class_exists(): void {
        $this->assertTrue(
            class_exists('SimpleLMS\SimpleLMS'),
            'Main SimpleLMS class should exist'
        );
    }
    
    /**
     * Test if all core classes are loaded
     */
    public function test_core_classes_loaded(): void {
        $classes = [
            'SimpleLMS\Cache_Handler',
            'SimpleLMS\Ajax_Handler',
            'SimpleLMS\Rest_API',
            'SimpleLMS\Progress_Tracker',
            'SimpleLMS\Meta_Boxes',
            'SimpleLMS\LmsShortcodes',
            'SimpleLMS\Admin_Customizations',
            'SimpleLMS\WooCommerce_Integration',
            'SimpleLMS\Access_Control',
            'SimpleLMS\Access_Meta_Boxes',
        ];
        
        foreach ($classes as $class) {
            $this->assertTrue(
                class_exists($class),
                "Class {$class} should be loaded"
            );
        }
    }
    
    /**
     * Test Elementor integration classes
     */
    public function test_elementor_integration_classes(): void {
        $classes = [
            'SimpleLMS\Elementor\Elementor_Dynamic_Tags',
            'SimpleLMS\Elementor\Elementor_Embed_Guard',
        ];
        
        foreach ($classes as $class) {
            $this->assertTrue(
                class_exists($class),
                "Elementor class {$class} should be loaded"
            );
        }
    }
    
    /**
     * Test Bricks Builder integration
     */
    public function test_bricks_integration_class(): void {
        $this->assertTrue(
            class_exists('SimpleLMS\Bricks\Bricks_Integration'),
            'Bricks integration class should be loaded'
        );
    }
    
    /**
     * Test if Elementor widgets exist
     */
    public function test_elementor_widgets_exist(): void {
        $widgets = [
            'lesson-content-widget.php',
            'course-overview-widget.php',
            'course-progress-widget.php',
            'lesson-completion-button-widget.php',
            'lesson-navigation-widget.php',
            'lesson-video-widget.php',
            'lesson-attachments-widget.php',
            'course-info-box-widget.php',
            'continue-learning-button-widget.php',
            'access-status-widget.php',
            'course-purchase-widget.php',
            'breadcrumbs-widget.php',
            'user-courses-grid-widget.php',
            'module-navigation-widget.php',
            'lesson-progress-indicator-widget.php',
            'user-progress-dashboard-widget.php',
        ];
        
        foreach ($widgets as $widget) {
            $path = SIMPLE_LMS_PLUGIN_DIR . 'includes/elementor-dynamic-tags/widgets/' . $widget;
            $this->assertFileExists($path, "Elementor widget {$widget} should exist");
        }
    }
    
    /**
     * Test if Bricks elements exist
     */
    public function test_bricks_elements_exist(): void {
        $elements = [
            'lesson-content.php',
            'course-overview.php',
            'course-progress.php',
            'lesson-completion-button.php',
            'lesson-navigation.php',
            'lesson-video.php',
            'lesson-attachments.php',
            'course-info-box.php',
            'continue-learning-button.php',
            'access-status.php',
            'course-purchase.php',
            'breadcrumbs-navigation.php',
            'user-courses-grid.php',
            'module-navigation.php',
            'lesson-progress-indicator.php',
            'user-progress-dashboard.php',
        ];
        
        foreach ($elements as $element) {
            $path = SIMPLE_LMS_PLUGIN_DIR . 'includes/bricks/elements/' . $element;
            $this->assertFileExists($path, "Bricks element {$element} should exist");
        }
    }
    
    /**
     * Test if Dynamic Tags exist
     */
    public function test_elementor_dynamic_tags_exist(): void {
        $tags = [
            'course-title.php',
            'module-title.php',
            'lesson-title.php',
        ];
        
        foreach ($tags as $tag) {
            $path = SIMPLE_LMS_PLUGIN_DIR . 'includes/elementor-dynamic-tags/tags/' . $tag;
            $this->assertFileExists($path, "Dynamic tag {$tag} should exist");
        }
    }
    
    /**
     * Test if all PHP files have valid syntax
     */
    public function test_php_syntax_valid(): void {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(SIMPLE_LMS_PLUGIN_DIR)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $path = $file->getPathname();
                
                // Skip vendor and node_modules
                if (strpos($path, 'vendor') !== false || strpos($path, 'node_modules') !== false) {
                    continue;
                }
                
                exec("php -l " . escapeshellarg($path) . " 2>&1", $output, $return);
                
                $this->assertEquals(
                    0,
                    $return,
                    "PHP syntax error in file: {$path}\n" . implode("\n", $output)
                );
            }
        }
    }
    
    /**
     * Test if constants are defined
     */
    public function test_constants_defined(): void {
        $constants = [
            'SIMPLE_LMS_VERSION',
            'SIMPLE_LMS_PLUGIN_DIR',
            'SIMPLE_LMS_PLUGIN_URL',
            'SIMPLE_LMS_PLUGIN_BASENAME',
        ];
        
        foreach ($constants as $constant) {
            $this->assertTrue(
                defined($constant),
                "Constant {$constant} should be defined"
            );
        }
    }
    
    /**
     * Test if no duplicate function definitions exist
     */
    public function test_no_duplicate_functions(): void {
        $functions = get_defined_functions()['user'];
        $lms_functions = array_filter($functions, function($func) {
            return strpos($func, 'SimpleLMS') !== false;
        });
        
        $unique = array_unique($lms_functions);
        $this->assertCount(
            count($lms_functions),
            $unique,
            'No duplicate function definitions should exist'
        );
    }
    
    /**
     * Test WordPress hooks registration
     */
    public function test_hooks_registered(): void {
        global $wp_filter;
        
        $hooks_to_check = [
            'plugins_loaded',
            'init',
            'wp_enqueue_scripts',
            'admin_enqueue_scripts',
        ];
        
        foreach ($hooks_to_check as $hook) {
            $this->assertTrue(
                isset($wp_filter[$hook]),
                "Hook {$hook} should be registered"
            );
        }
    }
    
    /**
     * Test if assets files exist
     */
    public function test_asset_files_exist(): void {
        $assets = [
            'assets/css/admin-style.css',
            'assets/css/frontend.css',
            'assets/js/admin-script.js',
            'assets/js/frontend.js',
        ];
        
        foreach ($assets as $asset) {
            $path = SIMPLE_LMS_PLUGIN_DIR . $asset;
            $this->assertFileExists($path, "Asset file {$asset} should exist");
        }
    }
    
    /**
     * Test shortcodes registration
     */
    public function test_shortcodes_registered(): void {
        global $shortcode_tags;
        
        $expected_shortcodes = [
            'simple_lms_course_title',
            'simple_lms_course_content',
            'simple_lms_course_navigation',
            'simple_lms_course_overview',
            'simple_lms_lesson_title',
            'simple_lms_lesson_content',
            'simple_lms_lesson_video',
            'simple_lms_next_lesson',
            'simple_lms_previous_lesson',
            'simple_lms_lesson_complete_toggle',
            'simple_lms_access_control',
        ];
        
        foreach ($expected_shortcodes as $shortcode) {
            $this->assertTrue(
                isset($shortcode_tags[$shortcode]),
                "Shortcode {$shortcode} should be registered"
            );
        }
    }
    
    /**
     * Test if no backup files exist
     */
    public function test_no_backup_files(): void {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(SIMPLE_LMS_PLUGIN_DIR)
        );
        
        $backup_patterns = [
            '-backup', '-old', '-copy', '.bak', '~',
            '-fixed', '-clean', '-new'
        ];
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $filename = $file->getFilename();
                foreach ($backup_patterns as $pattern) {
                    $this->assertStringNotContainsString(
                        $pattern,
                        $filename,
                        "No backup files should exist: {$filename}"
                    );
                }
            }
        }
    }
    
    /**
     * Test namespace consistency
     */
    public function test_namespace_consistency(): void {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(SIMPLE_LMS_PLUGIN_DIR . 'includes')
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                
                // Check if file has namespace
                if (preg_match('/^namespace\s+([^;]+);/m', $content, $matches)) {
                    $namespace = trim($matches[1]);
                    
                    // Ensure it starts with SimpleLMS
                    $this->assertStringStartsWith(
                        'SimpleLMS',
                        $namespace,
                        "Namespace in {$file->getFilename()} should start with SimpleLMS"
                    );
                }
            }
        }
    }
    
    /**
     * Test if error_log statements are controlled
     */
    public function test_error_logging_controlled(): void {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(SIMPLE_LMS_PLUGIN_DIR . 'includes')
        );
        
        $uncontrolled_logs = [];
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                $lines = explode("\n", $content);
                
                foreach ($lines as $num => $line) {
                    // Check for var_dump or print_r without error_log
                    if (preg_match('/\bvar_dump\(|print_r\([^)]+\);(?!\s*\/\/)/', $line)) {
                        $uncontrolled_logs[] = $file->getFilename() . ':' . ($num + 1);
                    }
                }
            }
        }
        
        $this->assertEmpty(
            $uncontrolled_logs,
            "Found uncontrolled debug statements in:\n" . implode("\n", $uncontrolled_logs)
        );
    }
}
