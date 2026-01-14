<?php
/**
 * Continue Learning Button Widget for Elementor
 *
 * Smart button that redirects to the last viewed lesson or first incomplete lesson
 *
 * @package SimpleLMS
 */

namespace SimpleLMS\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Continue Learning Button Widget
 */
class Continue_Learning_Button_Widget extends Widget_Base {

    /**
     * Get widget name
     */
    public function get_name(): string {
        return 'simple_lms_continue_learning_button';
    }

    /**
     * Get widget title
     */
    public function get_title(): string {
        return __('Continue Learning Button', 'simple-lms');
    }

    /**
     * Get widget icon
     */
    public function get_icon(): string {
        return 'eicon-play';
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
        return ['lms', 'course', 'continue', 'learning', 'button', 'resume', 'simple lms'];
    }

    /**
     * Register widget controls
     */
    protected function register_controls(): void {
        
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Ustawienia', 'simple-lms'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'course_id',
            [
                'label' => __('ID kursu', 'simple-lms'),
                'type' => Controls_Manager::NUMBER,
                'default' => 0,
                'description' => __('Zostaw 0 aby automatycznie wykryć kurs z aktualnej strony', 'simple-lms'),
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label' => __('Tekst przycisku', 'simple-lms'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Kontynuuj naukę', 'simple-lms'),
                'placeholder' => __('Wpisz tekst...', 'simple-lms'),
            ]
        );

        $this->add_control(
            'completed_text',
            [
                'label' => __('Tekst gdy ukończono', 'simple-lms'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Kurs ukończony!', 'simple-lms'),
                'placeholder' => __('Wpisz tekst...', 'simple-lms'),
            ]
        );

        $this->add_control(
            'no_access_text',
            [
                'label' => __('Tekst gdy brak dostępu', 'simple-lms'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Kup kurs aby rozpocząć', 'simple-lms'),
                'placeholder' => __('Wpisz tekst...', 'simple-lms'),
            ]
        );

        $this->add_control(
            'button_icon',
            [
                'label' => __('Ikona', 'simple-lms'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-play',
                    'library' => 'fa-solid',
                ],
            ]
        );

        $this->add_control(
            'icon_position',
            [
                'label' => __('Pozycja ikony', 'simple-lms'),
                'type' => Controls_Manager::SELECT,
                'default' => 'left',
                'options' => [
                    'left' => __('Przed tekstem', 'simple-lms'),
                    'right' => __('Po tekście', 'simple-lms'),
                ],
            ]
        );

        $this->add_responsive_control(
            'icon_spacing',
            [
                'label' => __('Odstęp ikony', 'simple-lms'),
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
                    'size' => 8,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .continue-btn-icon-left' => 'margin-right: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .continue-btn-icon-right' => 'margin-left: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'hide_when_completed',
            [
                'label' => __('Ukryj gdy kurs ukończony', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => '',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'hide_when_no_access',
            [
                'label' => __('Ukryj gdy brak dostępu', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => '',
                'return_value' => 'yes',
            ]
        );

        $this->add_responsive_control(
            'alignment',
            [
                'label' => __('Wyrównanie', 'simple-lms'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __('Do lewej', 'simple-lms'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('Wyśrodkuj', 'simple-lms'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => __('Do prawej', 'simple-lms'),
                        'icon' => 'eicon-text-align-right',
                    ],
                    'justify' => [
                        'title' => __('Justify', 'simple-lms'),
                        'icon' => 'eicon-text-align-justify',
                    ],
                ],
                'default' => 'left',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-continue-button-wrapper' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section - Button
        $this->start_controls_section(
            'button_style_section',
            [
                'label' => __('Przycisk', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'selector' => '{{WRAPPER}} .simple-lms-continue-btn',
            ]
        );

        $this->add_responsive_control(
            'button_width',
            [
                'label' => __('Szerokość', 'simple-lms'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'auto'],
                'range' => [
                    'px' => [
                        'min' => 100,
                        'max' => 800,
                    ],
                    '%' => [
                        'min' => 10,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => 'auto',
                ],
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-continue-btn' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'button_padding',
            [
                'label' => __('Padding', 'simple-lms'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', 'rem'],
                'default' => [
                    'top' => '15',
                    'right' => '30',
                    'bottom' => '15',
                    'left' => '30',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-continue-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'button_border_radius',
            [
                'label' => __('Zaokrąglenie rogów', 'simple-lms'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-continue-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->start_controls_tabs('button_style_tabs');

        // Normal state
        $this->start_controls_tab(
            'button_normal_tab',
            [
                'label' => __('Normalny', 'simple-lms'),
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label' => __('Kolor tekstu', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-continue-btn' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_bg_color',
            [
                'label' => __('Kolor tła', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#4CAF50',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-continue-btn' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'button_border',
                'selector' => '{{WRAPPER}} .simple-lms-continue-btn',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'button_shadow',
                'selector' => '{{WRAPPER}} .simple-lms-continue-btn',
            ]
        );

        $this->end_controls_tab();

        // Hover state
        $this->start_controls_tab(
            'button_hover_tab',
            [
                'label' => __('Hover', 'simple-lms'),
            ]
        );

        $this->add_control(
            'button_hover_text_color',
            [
                'label' => __('Kolor tekstu', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-continue-btn:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_hover_bg_color',
            [
                'label' => __('Kolor tła', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#45a049',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-continue-btn:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'button_hover_border',
                'selector' => '{{WRAPPER}} .simple-lms-continue-btn:hover',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'button_hover_shadow',
                'selector' => '{{WRAPPER}} .simple-lms-continue-btn:hover',
            ]
        );

        $this->add_control(
            'button_hover_transition',
            [
                'label' => __('Czas przejścia (ms)', 'simple-lms'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 1000,
                    ],
                ],
                'default' => [
                    'size' => 300,
                ],
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-continue-btn' => 'transition: all {{SIZE}}ms ease;',
                ],
            ]
        );

        $this->end_controls_tab();

        // Disabled state
        $this->start_controls_tab(
            'button_disabled_tab',
            [
                'label' => __('Disabled', 'simple-lms'),
            ]
        );

        $this->add_control(
            'button_disabled_text_color',
            [
                'label' => __('Kolor tekstu', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#999999',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-continue-btn.disabled' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_disabled_bg_color',
            [
                'label' => __('Kolor tła', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#e0e0e0',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-continue-btn.disabled' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();

        // Icon Style
        $this->start_controls_section(
            'icon_style_section',
            [
                'label' => __('Ikona', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'icon_size',
            [
                'label' => __('Rozmiar', 'simple-lms'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 100,
                    ],
                    'em' => [
                        'min' => 0.5,
                        'max' => 5,
                    ],
                    'rem' => [
                        'min' => 0.5,
                        'max' => 5,
                    ],
                ],
                'default' => [
                    'size' => 18,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-continue-btn i' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .simple-lms-continue-btn svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
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

        // Get course ID
        $course_id = !empty($settings['course_id']) 
            ? absint($settings['course_id']) 
            : $this->get_current_course_id();

        if (!$course_id || get_post_type($course_id) !== 'course') {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="elementor-alert elementor-alert-warning">';
                echo esc_html__('Nie można wykryć poprawnego kursu. Upewnij się, że widget jest używany na stronie kursu/modułu/lekcji albo ustaw prawidłowy ID.', 'simple-lms');
                echo '</div>';
            }
            return;
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="elementor-alert elementor-alert-info">';
                echo esc_html__('Użytkownik niezalogowany - przycisk będzie ukryty lub pokazywał "Zaloguj się"', 'simple-lms');
                echo '</div>';
            }
            return;
        }

        $user_id = get_current_user_id();

        // Check access using unified helper
        $has_access = \SimpleLMS\Access_Control::userHasCourseAccess($user_id, $course_id);
        
        if (!$has_access) {
            if ($settings['hide_when_no_access'] === 'yes') {
                return;
            }
            
            $this->render_button(
                '#',
                $settings['no_access_text'],
                $settings,
                'disabled no-access'
            );
            return;
        }

        // Get target lesson
        $target_lesson = $this->get_target_lesson($user_id, $course_id);

        if (!$target_lesson || get_post_type($target_lesson) !== 'lesson') {
            // Course completed
            if ($settings['hide_when_completed'] === 'yes') {
                return;
            }
            
            $this->render_button(
                '#',
                $settings['completed_text'],
                $settings,
                'disabled completed'
            );
            return;
        }

        // Render active button
        $this->render_button(
            get_permalink($target_lesson),
            $settings['button_text'],
            $settings,
            'active'
        );
    }

    /**
     * Render button HTML
     */
    private function render_button($url, $text, $settings, $state): void {
        $icon_position = $settings['icon_position'];
        $icon_class = 'continue-btn-icon-' . $icon_position;
        $is_disabled = strpos($state, 'disabled') !== false;

        echo '<div class="simple-lms-continue-button-wrapper">';
        
        if ($is_disabled) {
            echo '<span class="simple-lms-continue-btn ' . esc_attr($state) . '">';
        } else {
            echo '<a href="' . esc_url($url) . '" class="simple-lms-continue-btn ' . esc_attr($state) . '">';
        }

        // Icon before text
        if ($icon_position === 'left' && !empty($settings['button_icon']['value'])) {
            echo '<span class="' . esc_attr($icon_class) . '">';
            \Elementor\Icons_Manager::render_icon($settings['button_icon'], ['aria-hidden' => 'true']);
            echo '</span>';
        }

        echo '<span class="continue-btn-text">' . esc_html($text) . '</span>';

        // Icon after text
        if ($icon_position === 'right' && !empty($settings['button_icon']['value'])) {
            echo '<span class="' . esc_attr($icon_class) . '">';
            \Elementor\Icons_Manager::render_icon($settings['button_icon'], ['aria-hidden' => 'true']);
            echo '</span>';
        }

        if ($is_disabled) {
            echo '</span>';
        } else {
            echo '</a>';
        }
        
        echo '</div>';
    }

    /**
     * Get target lesson for continue learning
     */
    private function get_target_lesson($user_id, $course_id): ?int {
        // Try last viewed lesson first
        $last_viewed = Progress_Tracker::getLastViewedLesson($user_id, $course_id);
        
        if ($last_viewed) {
            // Check if completed - if yes, get next lesson
            if (Progress_Tracker::isLessonCompleted($user_id, $last_viewed)) {
                $next_lesson = $this->get_next_lesson_in_course($last_viewed, $course_id);
                if ($next_lesson) {
                    return $next_lesson;
                }
            } else {
                return $last_viewed;
            }
        }

        // Get first incomplete lesson
        return $this->get_first_incomplete_lesson($user_id, $course_id);
    }

    /**
     * Get next lesson in course after given lesson
     */
    private function get_next_lesson_in_course($lesson_id, $course_id): ?int {
        $modules = Cache_Handler::getCourseModules($course_id);
        
        $found = false;
        foreach ($modules as $module) {
            $lessons = Cache_Handler::getModuleLessons($module->ID);
            
            foreach ($lessons as $lesson) {
                if ($found) {
                    return $lesson->ID;
                }
                if ($lesson->ID === $lesson_id) {
                    $found = true;
                }
            }
        }
        
        return null;
    }

    /**
     * Get first incomplete lesson in course
     */
    private function get_first_incomplete_lesson($user_id, $course_id): ?int {
        $modules = Cache_Handler::getCourseModules($course_id);
        
        foreach ($modules as $module) {
            $lessons = Cache_Handler::getModuleLessons($module->ID);
            
            foreach ($lessons as $lesson) {
                if (!Progress_Tracker::isLessonCompleted($user_id, $lesson->ID)) {
                    return $lesson->ID;
                }
            }
        }
        
        return null;
    }

    /**
     * Get current course ID from context
     */
    private function get_current_course_id(): int {
        $post_id = get_queried_object_id();
        $post_type = get_post_type($post_id);

        if ($post_type === 'course') {
            return $post_id;
        }

        if ($post_type === 'module') {
            return (int) get_post_meta($post_id, 'module_course', true);
        }

        if ($post_type === 'lesson') {
            $module_id = (int) get_post_meta($post_id, 'lesson_module', true);
            if ($module_id) {
                return (int) get_post_meta($module_id, 'module_course', true);
            }
        }

        return 0;
    }
}
