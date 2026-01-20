<?php
namespace SimpleLMS\Elementor\Widgets;

/**
 * Lesson Title Widget
 * Displays the title of the current lesson
 *
 * @package SimpleLMS\Elementor
 */

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use SimpleLMS\Elementor\Elementor_Dynamic_Tags;

if (!defined('ABSPATH')) {
    exit;
}

// Ensure Elementor is loaded
if (!class_exists('\Elementor\Widget_Base')) {
    return;
}

/**
 * Lesson Title Widget
 */
class Lesson_Title_Widget extends Widget_Base {

    /**
     * Get widget name
     */
    public function get_name(): string {
        return 'simple-lms-lesson-title';
    }

    /**
     * Get widget title
     */
    public function get_title(): string {
        return __('Lesson Title', 'simple-lms');
    }

    /**
     * Get widget icon
     */
    public function get_icon(): string {
        return 'eicon-heading';
    }

    /**
     * Get widget categories
     */
    public function get_categories(): array {
        return ['simple-lms'];
    }

    /**
     * Get widget keywords
     */
    public function get_keywords(): array {
        return ['lesson', 'title', 'heading', 'lekcja', 'tytuł', 'nagłówek'];
    }

    /**
     * Register widget controls
     */
    protected function register_controls(): void {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Settings', 'simple-lms'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'lesson_id',
            [
                'label' => __('Lesson ID (optional)', 'simple-lms'),
                'type' => Controls_Manager::NUMBER,
                'default' => '',
                'description' => __('Leave empty to use current context', 'simple-lms'),
            ]
        );

        $this->add_control(
            'link',
            [
                'label' => __('Link to Lesson', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => '',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'fallback_text',
            [
                'label' => __('Fallback Text', 'simple-lms'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'description' => __('Text to show if no lesson is found', 'simple-lms'),
            ]
        );

        $this->end_controls_section();

        // Style section
        $this->start_controls_section(
            'style_section',
            [
                'label' => __('Style', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'typography',
                'selector' => '{{WRAPPER}} .simple-lms-lesson-title',
            ]
        );

        $this->add_control(
            'color',
            [
                'label' => __('Text Color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-lesson-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output
     */
    protected function render(): void {
        $settings = $this->get_settings_for_display();
        
        // Get lesson ID from settings or context
        $lesson_id = !empty($settings['lesson_id']) 
            ? absint($settings['lesson_id']) 
            : Elementor_Dynamic_Tags::get_current_lesson_id();

        if (!$lesson_id) {
            if (!empty($settings['fallback_text'])) {
                echo '<div class="simple-lms-lesson-title">' . esc_html($settings['fallback_text']) . '</div>';
            }
            return;
        }

        $lesson = get_post($lesson_id);
        if (!$lesson || $lesson->post_type !== 'lesson') {
            if (!empty($settings['fallback_text'])) {
                echo '<div class="simple-lms-lesson-title">' . esc_html($settings['fallback_text']) . '</div>';
            }
            return;
        }

        $title = get_the_title($lesson);

        // Output with or without link
        echo '<div class="simple-lms-lesson-title">';
        if ($settings['link'] === 'yes') {
            $url = get_permalink($lesson_id);
            printf(
                '<a href="%s" style="text-decoration: none; color: inherit;">%s</a>',
                esc_url($url),
                esc_html($title)
            );
        } else {
            echo esc_html($title);
        }
        echo '</div>';
    }
}
