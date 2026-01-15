<?php
namespace SimpleLMS\Elementor\Widgets;

/**
 * Lesson Content Widget
 * Displays the full WYSIWYG content of a lesson
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
 * Lesson Content Widget
 */
class Lesson_Content_Widget extends Widget_Base {

    /**
     * Get widget name
     */
    public function get_name(): string {
        return 'simple-lms-lesson-content';
    }

    /**
     * Get widget title
     */
    public function get_title(): string {
        return __('Lesson content', 'simple-lms');
    }

    /**
     * Get widget icon
     */
    public function get_icon(): string {
        return 'eicon-post-content';
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
        return ['lesson', 'content', 'lesson', 'treść', 'simple', 'lms'];
    }

    /**
     * Register widget controls
     */
    protected function register_controls(): void {
        // Content section
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
                'label' => __('ID lessons (opcjonalne)', 'simple-lms'),
                'type' => Controls_Manager::NUMBER,
                'default' => '',
                'description' => __('Leave empty to automatically detect current lesson', 'simple-lms'),
            ]
        );

        $this->add_control(
            'show_title',
            [
                'label' => __('Show Lesson Title', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => '',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'lesson_fallback_text',
            [
                'label' => __('Fallback text', 'simple-lms'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => __('No lesson content.', 'simple-lms'),
                'description' => __('Displayed when lesson is not found', 'simple-lms'),
            ]
        );

        $this->end_controls_section();

        // Style section - Title
        $this->start_controls_section(
            'title_style_section',
            [
                'label' => __('Title', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
                // Use strict conditions to fully remove the section when disabled
                'conditions' => [
                    'terms' => [
                        [
                            'name' => 'show_title',
                            'operator' => '==',
                            'value' => 'yes',
                        ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => __('Kolor', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-lesson-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'selector' => '{{WRAPPER}} .simple-lms-lesson-title',
            ]
        );

        $this->add_responsive_control(
            'title_spacing',
            [
                'label' => __('Distance from content', 'simple-lms'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-lesson-title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style section - Content
        $this->start_controls_section(
            'content_style_section',
            [
                'label' => __('Content', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'content_color',
            [
                'label' => __('Text color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-lesson-content' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'content_typography',
                'selector' => '{{WRAPPER}} .simple-lms-lesson-content',
            ]
        );

        $this->add_responsive_control(
            'content_align',
            [
                'label' => __('Alignment', 'simple-lms'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __('Do lewej', 'simple-lms'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('Inward', 'simple-lms'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => __('Do prawej', 'simple-lms'),
                        'icon' => 'eicon-text-align-right',
                    ],
                    'justify' => [
                        'title' => __('Wyjustuj', 'simple-lms'),
                        'icon' => 'eicon-text-align-justify',
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-lesson-content' => 'text-align: {{VALUE}};',
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
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="simple-lms-lesson-content elementor-alert elementor-alert-info">';
                echo esc_html($settings['lesson_fallback_text']);
                echo '</div>';
            }
            return;
        }

        $lesson = get_post($lesson_id);

        if (!$lesson || $lesson->post_type !== 'lesson') {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="simple-lms-lesson-content elementor-alert elementor-alert-warning">';
                echo esc_html($settings['lesson_fallback_text']);
                echo '</div>';
            }
            return;
        }

        echo '<div class="simple-lms-lesson-content-wrapper">';

        // Show title if enabled
        if ($settings['show_title'] === 'yes') {
            echo '<h2 class="simple-lms-lesson-title">';
            echo esc_html($lesson->post_title);
            echo '</h2>';
        }

        // Output lesson content with WordPress filters
        echo '<div class="simple-lms-lesson-content">';
        
        // Apply the_content filters to handle shortcodes, embeds, etc.
        $content = apply_filters('the_content', $lesson->post_content);
        $content = str_replace(']]>', ']]&gt;', $content);
        
        echo $content;
        
        echo '</div>';

        echo '</div>';
    }

    /**
     * Render widget output in the editor (plain content)
     */
    protected function content_template(): void {
        ?>
        <# 
        var fallbackText = settings.lesson_fallback_text || '<?php echo esc_js(__('No lesson content.', 'simple-lms')); ?>';
        #>
        <div class="simple-lms-lesson-content-wrapper">
            <# if (settings.show_title === 'yes') { #>
                <h2 class="simple-lms-lesson-title">
                    <?php echo esc_html__('Lesson Title', 'simple-lms'); ?>
                </h2>
            <# } #>
            <div class="simple-lms-lesson-content elementor-alert elementor-alert-info">
                <p><?php echo esc_html__('Lesson content preview (displayed on page)', 'simple-lms'); ?></p>
            </div>
        </div>
        <?php
    }
}
