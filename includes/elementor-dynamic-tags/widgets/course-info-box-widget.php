<?php
/**
 * Course Info Box Widget
 * Displays compact course information card
 *
 * @package SimpleLMS\Elementor
 */

namespace SimpleLMS\Elementor\Widgets;

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
 * Course Info Box Widget
 */
class Course_Info_Box_Widget extends Widget_Base {

    /**
     * Get widget name
     */
    public function get_name(): string {
        return 'simple-lms-course-info-box';
    }

    /**
     * Get widget title
     */
    public function get_title(): string {
        return __('Kafelek informacyjny kursu', 'simple-lms');
    }

    /**
     * Get widget icon
     */
    public function get_icon(): string {
        return 'eicon-info-box';
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
        return ['course', 'info', 'box', 'stats', 'kurs', 'informacje', 'kafelek', 'statystyki'];
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
            'course_id',
            [
                'label' => __('ID kursu (opcjonalne)', 'simple-lms'),
                'type' => Controls_Manager::NUMBER,
                'default' => '',
                'description' => __('Leave empty to automatically detect current course', 'simple-lms'),
            ]
        );

        $this->add_control(
            'show_modules_count',
            [
                'label' => __('Pokaż liczbę modułów', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_lessons_count',
            [
                'label' => __('Show lesson count', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_progress',
            [
                'label' => __('Show completion progress', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'return_value' => 'yes',
                'description' => __('Wymaga zalogowania użytkownika', 'simple-lms'),
            ]
        );

        $this->add_control(
            'modules_label',
            [
                'label' => __('Etykieta modułów', 'simple-lms'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Moduły', 'simple-lms'),
                'condition' => [
                    'show_modules_count' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'lessons_label',
            [
                'label' => __('Etykieta lekcji', 'simple-lms'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Lekcje', 'simple-lms'),
                'condition' => [
                    'show_lessons_count' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'progress_label',
            [
                'label' => __('Etykieta postępu', 'simple-lms'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Ukończono', 'simple-lms'),
                'condition' => [
                    'show_progress' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'layout',
            [
                'label' => __('Layout', 'simple-lms'),
                'type' => Controls_Manager::SELECT,
                'default' => 'vertical',
                'options' => [
                    'vertical' => __('Pionowy', 'simple-lms'),
                    'horizontal' => __('Poziomy', 'simple-lms'),
                    'grid' => __('Siatka', 'simple-lms'),
                ],
            ]
        );

        $this->end_controls_section();

        // Style section - Container
        $this->start_controls_section(
            'container_style_section',
            [
                'label' => __('Kontener', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'container_bg_color',
            [
                'label' => __('Background color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-info-box' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'container_Padding',
            [
                'label' => __('Padding', 'simple-lms'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', 'rem'],
                'default' => [
                    'top' => '30',
                    'right' => '30',
                    'bottom' => '30',
                    'left' => '30',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-info-box' => 'Padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'container_border_radius',
            [
                'label' => __('Border radius', 'simple-lms'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'default' => [
                    'top' => '8',
                    'right' => '8',
                    'bottom' => '8',
                    'left' => '8',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-info-box' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'container_border',
                'selector' => '{{WRAPPER}} .simple-lms-info-box',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'container_shadow',
                'selector' => '{{WRAPPER}} .simple-lms-info-box',
            ]
        );

        $this->end_controls_section();

        // Style section - Items
        $this->start_controls_section(
            'items_style_section',
            [
                'label' => __('Elementy', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'items_gap',
            [
                'label' => __('Odstęp między elementami', 'simple-lms'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 5,
                    ],
                    'rem' => [
                        'min' => 0,
                        'max' => 5,
                    ],
                ],
                'default' => [
                    'size' => 20,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .info-box-items' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'item_bg_color',
            [
                'label' => __('Background color elementu', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#f8f9fa',
                'selectors' => [
                    '{{WRAPPER}} .info-item' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'item_Padding',
            [
                'label' => __('Element padding', 'simple-lms'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', 'rem'],
                'default' => [
                    'top' => '15',
                    'right' => '20',
                    'bottom' => '15',
                    'left' => '20',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .info-item' => 'Padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'item_border_radius',
            [
                'label' => __('Border radius elementu', 'simple-lms'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .info-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style section - Typography
        $this->start_controls_section(
            'typography_style_section',
            [
                'label' => __('Typografia', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'value_color',
            [
                'label' => __('Kolor wartości', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#2196F3',
                'selectors' => [
                    '{{WRAPPER}} .info-value' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'value_typography',
                'label' => __('Typografia wartości', 'simple-lms'),
                'selector' => '{{WRAPPER}} .info-value',
            ]
        );

        $this->add_control(
            'label_color',
            [
                'label' => __('Kolor etykiety', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#666666',
                'selectors' => [
                    '{{WRAPPER}} .info-label' => 'color: {{VALUE}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'label_typography',
                'label' => __('Typografia etykiety', 'simple-lms'),
                'selector' => '{{WRAPPER}} .info-label',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output
     */
    protected function render(): void {
        $settings = $this->get_settings_for_display();

        // Get course ID
        $course_id = !empty($settings['course_id']) 
            ? absint($settings['course_id']) 
            : Elementor_Dynamic_Tags::get_current_course_id();

        if (!$course_id || get_post_type($course_id) !== 'course') {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="elementor-alert elementor-alert-warning">';
                echo esc_html__('Cannot detect valid course. Make sure the widget is used on course or lesson page or set correct ID.', 'simple-lms');
                echo '</div>';
            }
            return;
        }

        // Get course data
        $modules = \SimpleLMS\Cache_Handler::getCourseModules($course_id);
        $modules_count = count($modules);
        if ($modules_count === 0) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="elementor-alert elementor-alert-info">'.esc_html__('This course has no modules yet.','simple-lms').'</div>';
            }
        }
        
        // Use unified API for total lessons
        $lessons_count = \SimpleLMS\Progress_Tracker::getTotalLessonsCount($course_id);

        // Get progress if user is logged in
        $progress_percentage = 0;
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            // Use unified Progress_Tracker API
            $progress_percentage = \SimpleLMS\Progress_Tracker::getCourseProgress($user_id, $course_id);
        }

        $layout = $settings['layout'];
        $layout_styles = [
            'vertical' => 'flex-direction: column;',
            'horizontal' => 'flex-direction: row; flex-wrap: wrap;',
            'grid' => 'display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));',
        ];

        echo '<div class="simple-lms-info-box">';
        echo '<div class="info-box-items" style="display: flex; ' . ($layout_styles[$layout] ?? $layout_styles['vertical']) . '">';

        // Modules count
        if ($settings['show_modules_count'] === 'yes') {
            echo '<div class="info-item" style="text-align: center;">';
            echo '<div class="info-value" style="font-size: 2em; font-weight: 700; margin-bottom: 5px;">' . esc_html($modules_count) . '</div>';
            echo '<div class="info-label" style="font-size: 0.9em;">' . esc_html($settings['modules_label']) . '</div>';
            echo '</div>';
        }

        // Lessons count
        if ($settings['show_lessons_count'] === 'yes') {
            echo '<div class="info-item" style="text-align: center;">';
            echo '<div class="info-value" style="font-size: 2em; font-weight: 700; margin-bottom: 5px;">' . esc_html($lessons_count) . '</div>';
            echo '<div class="info-label" style="font-size: 0.9em;">' . esc_html($settings['lessons_label']) . '</div>';
            echo '</div>';
        }

        // Progress
        if ($settings['show_progress'] === 'yes' && is_user_logged_in()) {
            echo '<div class="info-item" style="text-align: center;">';
            echo '<div class="info-value" style="font-size: 2em; font-weight: 700; margin-bottom: 5px;">' . esc_html($progress_percentage) . '%</div>';
            echo '<div class="info-label" style="font-size: 0.9em;">' . esc_html($settings['progress_label']) . '</div>';
            echo '</div>';
        }

        echo '</div>';
        echo '</div>';
    }

    /**
     * Render widget output in the editor
     */
    protected function content_template(): void {
        ?>
        <#
        var layout = settings.layout;
        var layoutStyles = {
            'vertical': 'flex-direction: column;',
            'horizontal': 'flex-direction: row; flex-wrap: wrap;',
            'grid': 'display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));'
        };
        var showModules = settings.show_modules_count === 'yes';
        var showLessons = settings.show_lessons_count === 'yes';
        var showProgress = settings.show_progress === 'yes';
        #>
        <div class="simple-lms-info-box">
            <div class="info-box-items" style="display: flex; {{{layoutStyles[layout]}}}">
                <# if (showModules) { #>
                    <div class="info-item" style="text-align: center;">
                        <div class="info-value" style="font-size: 2em; font-weight: 700; margin-bottom: 5px;">5</div>
                        <div class="info-label" style="font-size: 0.9em;">{{{settings.modules_label}}}</div>
                    </div>
                <# } #>
                
                <# if (showLessons) { #>
                    <div class="info-item" style="text-align: center;">
                        <div class="info-value" style="font-size: 2em; font-weight: 700; margin-bottom: 5px;">24</div>
                        <div class="info-label" style="font-size: 0.9em;">{{{settings.lessons_label}}}</div>
                    </div>
                <# } #>
                
                <# if (showProgress) { #>
                    <div class="info-item" style="text-align: center;">
                        <div class="info-value" style="font-size: 2em; font-weight: 700; margin-bottom: 5px;">65%</div>
                        <div class="info-label" style="font-size: 0.9em;">{{{settings.progress_label}}}</div>
                    </div>
                <# } #>
            </div>
        </div>
        <?php
    }
}
