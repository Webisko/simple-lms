<?php
/**
 * Course Title Dynamic Tag
 * Displays the title of the current course
 *
 * @package SimpleLMS\Elementor
 */

namespace SimpleLMS\Elementor\Tags;

use Elementor\Core\DynamicTags\Tag;
use Elementor\Controls_Manager;
use SimpleLMS\Elementor\Elementor_Dynamic_Tags;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Course Title Tag
 */
class Course_Title_Tag extends Tag {

    /**
     * Get tag name
     */
    public function get_name(): string {
        return 'simple-lms-course-title';
    }

    /**
     * Get tag title
     */
    public function get_title(): string {
        return __('Course Title', 'simple-lms');
    }

    /**
     * Get tag group
     */
    public function get_group(): string {
        return 'simple-lms';
    }

    /**
     * Get tag categories (where it can be used)
     */
    public function get_categories(): array {
        return [
            \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY,
            \Elementor\Modules\DynamicTags\Module::URL_CATEGORY,
        ];
    }

    /**
     * Register controls (tag settings)
     */
    protected function register_controls(): void {
        // Course ID override (optional)
        $this->add_control(
            'course_id',
            [
                'label' => __('Course ID (optional)', 'simple-lms'),
                'type' => Controls_Manager::NUMBER,
                'description' => __('Leave empty to use current context', 'simple-lms'),
            ]
        );

        // Link to course
        $this->add_control(
            'link',
            [
                'label' => __('Link to Course', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => '',
                'return_value' => 'yes',
            ]
        );

        // Fallback text
        $this->add_control(
            'fallback_text',
            [
                'label' => __('Fallback Text', 'simple-lms'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'description' => __('Text to show if no course is found', 'simple-lms'),
            ]
        );
    }

    /**
     * Render tag output
     */
    public function render(): void {
        $settings = $this->get_settings();
        
        // Get course ID from settings or context
        $course_id = !empty($settings['course_id']) 
            ? absint($settings['course_id']) 
            : Elementor_Dynamic_Tags::get_current_course_id();

        if (!$course_id) {
            echo esc_html($settings['fallback_text'] ?? '');
            return;
        }

        $course = get_post($course_id);
        if (!$course || $course->post_type !== 'course') {
            echo esc_html($settings['fallback_text'] ?? '');
            return;
        }

        $title = get_the_title($course);

        // Output with or without link
        if ($settings['link'] === 'yes') {
            $url = get_permalink($course_id);
            printf(
                '<a href="%s">%s</a>',
                esc_url($url),
                esc_html($title)
            );
        } else {
            echo esc_html($title);
        }
    }

    /**
     * Render for URL category (when used in link fields)
     */
    public function render_url(): string {
        $settings = $this->get_settings();
        
        $course_id = !empty($settings['course_id']) 
            ? absint($settings['course_id']) 
            : Elementor_Dynamic_Tags::get_current_course_id();

        if (!$course_id) {
            return '';
        }

        return get_permalink($course_id);
    }
}
