<?php
/**
 * Course Overview Widget (Accordion)
 * Displays course structure with modules as accordion items
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
 * Course Overview Widget
 */
class Course_Overview_Widget extends Widget_Base {

    /**
     * Get widget name
     */
    public function get_name(): string {
        return 'simple-lms-course-overview';
    }

    /**
     * Get widget title
     */
    public function get_title(): string {
        return __('Przegląd kursu (Akordeon)', 'simple-lms');
    }

    /**
     * Get widget icon
     */
    public function get_icon(): string {
        return 'eicon-accordion';
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
        return ['course', 'overview', 'accordion', 'kurs', 'przegląd', 'akordeon', 'struktura', 'moduły', 'lekcje'];
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
            'show_progress',
            [
                'label' => __('Show completion progress', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_lesson_count',
            [
                'label' => __('Show lesson count', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_unlock_dates',
            [
                'label' => __('Show unlock dates', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'return_value' => 'yes',
                'description' => __('Displays module availability information with drip content', 'simple-lms'),
            ]
        );

        $this->add_control(
            'accordion_open_first',
            [
                'label' => __('Open first module', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'accordion_allow_multiple',
            [
                'label' => __('Allow multiple modules to be opened', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => '',
                'return_value' => 'yes',
            ]
        );

        $this->end_controls_section();

        // Style section - Module Header
        $this->start_controls_section(
            'module_header_style',
            [
                'label' => __('Module header', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'module_header_bg',
            [
                'label' => __('Background color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#f5f5f5',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-accordion-item .accordion-header' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'module_header_color',
            [
                'label' => __('Text color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#333',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-accordion-item .accordion-header' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'module_header_typography',
                'selector' => '{{WRAPPER}} .simple-lms-accordion-item .accordion-header .module-title',
            ]
        );

        $this->add_responsive_control(
            'module_header_Padding',
            [
                'label' => __('Padding', 'simple-lms'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%', 'rem'],
                'default' => [
                    'top' => '15',
                    'right' => '20',
                    'bottom' => '15',
                    'left' => '20',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-accordion-item .accordion-header' => 'Padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'module_header_border_radius',
            [
                'label' => __('Border radius', 'simple-lms'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-accordion-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style section - Lesson List
        $this->start_controls_section(
            'lesson_list_style',
            [
                'label' => __('Lesson list', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'lesson_bg',
            [
                'label' => __('Background color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-accordion-item .accordion-content' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'lesson_text_color',
            [
                'label' => __('Text color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-accordion-item .lesson-link' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'lesson_hover_color',
            [
                'label' => __('Text color (hover)', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-accordion-item .lesson-link:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'lesson_typography',
                'selector' => '{{WRAPPER}} .simple-lms-accordion-item .lesson-link',
            ]
        );

        $this->add_responsive_control(
            'lesson_Padding',
            [
                'label' => __('Element padding', 'simple-lms'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-accordion-item .lesson-item' => 'Padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style section - Progress Indicators
        $this->start_controls_section(
            'progress_style',
            [
                'label' => __('Progress indicators', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_progress' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'progress_complete_color',
            [
                'label' => __('Color - completed', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#4CAF50',
                'selectors' => [
                    '{{WRAPPER}} .completion-status.completed' => 'background-color: {{VALUE}}; border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'progress_incomplete_color',
            [
                'label' => __('Color - incomplete', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ddd',
                'selectors' => [
                    '{{WRAPPER}} .completion-status.incomplete' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'progress_size',
            [
                'label' => __('Indicator size', 'simple-lms'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 40,
                    ],
                    'em' => [
                        'min' => 0.5,
                        'max' => 3,
                    ],
                    'rem' => [
                        'min' => 0.5,
                        'max' => 3,
                    ],
                ],
                'default' => [
                    'size' => 20,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .completion-status' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; line-height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style section - Current Lesson Highlight
        $this->start_controls_section(
            'current_lesson_style',
            [
                'label' => __('Active lesson', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'current_lesson_bg',
            [
                'label' => __('Background color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#e3f2fd',
                'selectors' => [
                    '{{WRAPPER}} .lesson-item.current-lesson' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'current_lesson_border',
            [
                'label' => __('Border color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#2196F3',
                'selectors' => [
                    '{{WRAPPER}} .lesson-item.current-lesson' => 'border-left: 3px solid {{VALUE}};',
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

        // Get course ID from settings or context
        $course_id = !empty($settings['course_id']) 
            ? absint($settings['course_id']) 
            : Elementor_Dynamic_Tags::get_current_course_id();

        if (!$course_id) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="elementor-alert elementor-alert-warning">';
                echo esc_html__('Cannot detect course. Make sure the widget is used on a course page or set the course ID manually.', 'simple-lms');
                echo '</div>';
            }
            return;
        }

        // Get current lesson for highlighting
        $current_lesson_id = get_the_ID();
        $is_lesson_page = get_post_type($current_lesson_id) === 'lesson';

        // Get course modules
        $modules = \SimpleLMS\Cache_Handler::getCourseModules($course_id);

        if (empty($modules)) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="elementor-alert elementor-alert-info">';
                echo esc_html__('This course has no modules yet.', 'simple-lms');
                echo '</div>';
            }
            return;
        }

        // Build unique widget ID for JS
        $widget_id = 'simple-lms-accordion-' . $this->get_id();

        echo '<div class="simple-lms-course-overview-accordion" id="' . esc_attr($widget_id) . '">';
        
        $module_index = 0;
        foreach ($modules as $module) {
            $module_index++;
            $lessons = \SimpleLMS\Cache_Handler::getModuleLessons((int) $module->ID);
            $lessons_count = count($lessons);

            // Check if module is locked
            $module_locked = false;
            $unlock_info = [];
            if (class_exists('SimpleLMS\\Access_Control')) {
                $module_locked = !\SimpleLMS\Access_Control::isModuleUnlocked((int)$module->ID);
                if ($module_locked) {
                    $unlock_info = \SimpleLMS\Access_Control::getModuleUnlockInfo((int)$module->ID);
                }
            }

            $is_open = ($module_index === 1 && $settings['accordion_open_first'] === 'yes') ? 'open' : '';

            echo '<div class="simple-lms-accordion-item' . ($module_locked ? ' locked' : '') . ' ' . $is_open . '" data-module-id="' . esc_attr($module->ID) . '">';
            
            // Module header (clickable)
            echo '<div class="accordion-header">';
            echo '<span class="accordion-icon"></span>';
            echo '<h3 class="module-title">' . esc_html($module->post_title) . '</h3>';
            
            // Lesson count
            if ($settings['show_lesson_count'] === 'yes') {
                echo '<span class="lessons-count">(' . esc_html(\SimpleLMS\LmsShortcodes::getLessonsCountText($lessons_count)) . ')</span>';
            }

            // Unlock date
            if ($settings['show_unlock_dates'] === 'yes' && $module_locked && !empty($unlock_info['unlock_ts'])) {
                $date_str = date_i18n('d.m.Y', (int)$unlock_info['unlock_ts']);
                echo '<span class="unlock-date">' . sprintf(__('Available from: %s', 'simple-lms'), esc_html($date_str)) . '</span>';
            }
            
            echo '</div>';

            // Module content (accordion body)
            echo '<div class="accordion-content">';
            
            if (!empty($lessons)) {
                echo '<ul class="lessons-list">';
                
                foreach ($lessons as $lesson) {
                    $is_current = ($is_lesson_page && $lesson->ID == $current_lesson_id);
                    $is_completed = \SimpleLMS\LmsShortcodes::isLessonCompleted($lesson->ID);
                    
                    $classes = ['lesson-item'];
                    if ($is_current) {
                        $classes[] = 'current-lesson';
                    }
                    if ($is_completed) {
                        $classes[] = 'completed-lesson';
                    }
                    
                    echo '<li class="' . esc_attr(implode(' ', $classes)) . '" data-lesson-id="' . esc_attr($lesson->ID) . '">';
                    echo '<a href="' . esc_url(get_permalink($lesson->ID)) . '" class="lesson-link">';
                    
                    // Completion status
                    if ($settings['show_progress'] === 'yes') {
                        if ($is_completed) {
                            echo '<span class="completion-status completed" data-lesson-id="' . esc_attr($lesson->ID) . '">✓</span>';
                        } else {
                            echo '<span class="completion-status incomplete" data-lesson-id="' . esc_attr($lesson->ID) . '"></span>';
                        }
                    }
                    
                    echo '<span class="lesson-title">' . esc_html($lesson->post_title) . '</span>';
                    echo '</a>';
                    echo '</li>';
                }
                
                echo '</ul>';
            } else {
                echo '<p class="no-lessons">' . esc_html__('Brak lekcji w tym module', 'simple-lms') . '</p>';
            }
            
            echo '</div>'; // .accordion-content
            echo '</div>'; // .simple-lms-accordion-item
        }

        echo '</div>'; // .simple-lms-course-overview-accordion

        // Add inline JS for accordion functionality
        $allow_multiple = $settings['accordion_allow_multiple'] === 'yes' ? 'true' : 'false';
        ?>
        <script>
        (function() {
            const accordion = document.getElementById('<?php echo esc_js($widget_id); ?>');
            if (!accordion) return;

            const allowMultiple = <?php echo $allow_multiple; ?>;
            const headers = accordion.querySelectorAll('.accordion-header');

            headers.forEach(header => {
                header.addEventListener('click', function() {
                    const item = this.closest('.simple-lms-accordion-item');
                    const isOpen = item.classList.contains('open');

                    // Close all if multiple not allowed
                    if (!allowMultiple && !isOpen) {
                        accordion.querySelectorAll('.simple-lms-accordion-item').forEach(i => {
                            i.classList.remove('open');
                        });
                    }

                    // Toggle current
                    item.classList.toggle('open');
                });
            });
        })();
        </script>
        <?php
    }

    /**
     * Render widget output in the editor
     */
    protected function content_template(): void {
        ?>
        <div class="simple-lms-course-overview-accordion">
            <div class="simple-lms-accordion-item open">
                <div class="accordion-header">
                    <span class="accordion-icon"></span>
                    <h3 class="module-title"><?php echo esc_html__('Example Module 1', 'simple-lms'); ?></h3>
                    <# if (settings.show_lesson_count === 'yes') { #>
                        <span class="lessons-count">(3 lekcje)</span>
                    <# } #>
                </div>
                <div class="accordion-content">
                    <ul class="lessons-list">
                        <li class="lesson-item current-lesson">
                            <a href="#" class="lesson-link">
                                <# if (settings.show_progress === 'yes') { #>
                                    <span class="completion-status completed">✓</span>
                                <# } #>
                                <span class="lesson-title"><?php echo esc_html__('Lekcja 1', 'simple-lms'); ?></span>
                            </a>
                        </li>
                        <li class="lesson-item">
                            <a href="#" class="lesson-link">
                                <# if (settings.show_progress === 'yes') { #>
                                    <span class="completion-status incomplete"></span>
                                <# } #>
                                <span class="lesson-title"><?php echo esc_html__('Lekcja 2', 'simple-lms'); ?></span>
                            </a>
                        </li>
                        <li class="lesson-item">
                            <a href="#" class="lesson-link">
                                <# if (settings.show_progress === 'yes') { #>
                                    <span class="completion-status incomplete"></span>
                                <# } #>
                                <span class="lesson-title"><?php echo esc_html__('Lekcja 3', 'simple-lms'); ?></span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="simple-lms-accordion-item">
                <div class="accordion-header">
                    <span class="accordion-icon"></span>
                    <h3 class="module-title"><?php echo esc_html__('Example Module 2', 'simple-lms'); ?></h3>
                    <# if (settings.show_lesson_count === 'yes') { #>
                        <span class="lessons-count">(2 lekcje)</span>
                    <# } #>
                </div>
                <div class="accordion-content">
                    <ul class="lessons-list">
                        <li class="lesson-item">
                            <a href="#" class="lesson-link">
                                <# if (settings.show_progress === 'yes') { #>
                                    <span class="completion-status incomplete"></span>
                                <# } #>
                                <span class="lesson-title"><?php echo esc_html__('Lekcja 1', 'simple-lms'); ?></span>
                            </a>
                        </li>
                        <li class="lesson-item">
                            <a href="#" class="lesson-link">
                                <# if (settings.show_progress === 'yes') { #>
                                    <span class="completion-status incomplete"></span>
                                <# } #>
                                <span class="lesson-title"><?php echo esc_html__('Lekcja 2', 'simple-lms'); ?></span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
}
