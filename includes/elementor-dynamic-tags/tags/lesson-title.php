<?php
/**
 * Lesson Title Dynamic Tag
 * Displays the title of the current lesson
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
 * Lesson Title Tag
 */
class Lesson_Title_Tag extends Tag {

    /**
     * Get tag name
     */
    public function get_name(): string {
        return 'simple-lms-lesson-title';
    }

    /**
     * Get tag title
     */
    public function get_title(): string {
        return __('Lesson Title', 'simple-lms');
    }

    /**
     * Get tag group
     */
    public function get_group(): string {
        return 'simple-lms';
    }

    /**
     * Get tag categories
     */
    public function get_categories(): array {
        return [
            \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY,
            \Elementor\Modules\DynamicTags\Module::URL_CATEGORY,
        ];
    }

    /**
     * Register controls
     */
    protected function register_controls(): void {
        // Lesson ID override
        $this->add_control(
            'lesson_id',
            [
                'label' => __('Lesson ID (optional)', 'simple-lms'),
                'type' => Controls_Manager::NUMBER,
                'description' => __('Leave empty to use current context', 'simple-lms'),
            ]
        );

        // Link to lesson
        $this->add_control(
            'link',
            [
                'label' => __('Link to Lesson', 'simple-lms'),
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
                'description' => __('Text to show if no lesson is found', 'simple-lms'),
            ]
        );
    }

    /**
     * Render tag output
     */
    public function render(): void {
        $settings = $this->get_settings();
        
        // Get lesson ID from settings or context
        $lesson_id = !empty($settings['lesson_id']) 
            ? absint($settings['lesson_id']) 
            : Elementor_Dynamic_Tags::get_current_lesson_id();

        if (!$lesson_id) {
            echo esc_html($settings['fallback_text'] ?? '');
            return;
        }

        $lesson = get_post($lesson_id);
        if (!$lesson || $lesson->post_type !== 'lesson') {
            echo esc_html($settings['fallback_text'] ?? '');
            return;
        }

        $title = get_the_title($lesson);

        // Output with or without link
        if ($settings['link'] === 'yes') {
            $url = get_permalink($lesson_id);
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
     * Render for URL category
     */
    public function render_url(): string {
        $settings = $this->get_settings();
        
        $lesson_id = !empty($settings['lesson_id']) 
            ? absint($settings['lesson_id']) 
            : Elementor_Dynamic_Tags::get_current_lesson_id();

        if (!$lesson_id) {
            return '';
        }

        return get_permalink($lesson_id);
    }
}
