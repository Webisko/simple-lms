<?php
/**
 * Breadcrumbs Navigation Widget for Elementor
 *
 * Displays navigation path: Course > Module > Lesson with clickable links
 *
 * @package SimpleLMS
 */

namespace SimpleLMS\Elementor\Widgets;

use SimpleLMS\Cache_Handler;
use SimpleLMS\Elementor\Elementor_Dynamic_Tags;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Breadcrumbs Navigation Widget
 */
class Breadcrumbs_Widget extends Widget_Base {

    /**
     * Get widget name
     */
    public function get_name(): string {
        return 'simple_lms_breadcrumbs';
    }

    /**
     * Get widget title
     */
    public function get_title(): string {
        return __('Course Breadcrumbs', 'simple-lms');
    }

    /**
     * Get widget icon
     */
    public function get_icon(): string {
        return 'eicon-navigation-horizontal';
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
        return ['lms', 'breadcrumbs', 'navigation', 'course', 'module', 'lesson', 'simple lms'];
    }

    /**
     * Register widget controls
     */
    protected function register_controls(): void {
        
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Settings', 'simple-lms'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'separator',
            [
                'label' => __('Separator', 'simple-lms'),
                'type' => Controls_Manager::SELECT,
                'default' => '>',
                'options' => [
                    '>' => '>',
                    '/' => '/',
                    '→' => '→',
                    '•' => '•',
                    '|' => '|',
                    '::' => '::',
                    'custom' => __('Własny', 'simple-lms'),
                ],
            ]
        );

        $this->add_control(
            'custom_separator',
            [
                'label' => __('Własny separator', 'simple-lms'),
                'type' => Controls_Manager::TEXT,
                'default' => '>',
                'condition' => [
                    'separator' => 'custom',
                ],
            ]
        );

        $this->add_control(
            'separator_icon',
            [
                'label' => __('Użyj ikony jako separator', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => '',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'separator_icon_select',
            [
                'label' => __('Ikona separatora', 'simple-lms'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-chevron-right',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'separator_icon' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_home',
            [
                'label' => __('Pokaż link "Strona główna"', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => '',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'home_text',
            [
                'label' => __('Tekst "Strona główna"', 'simple-lms'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Strona główna', 'simple-lms'),
                'condition' => [
                    'show_home' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_course',
            [
                'label' => __('Pokaż kurs', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_module',
            [
                'label' => __('Pokaż moduł', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_lesson',
            [
                'label' => __('Pokaż lekcję', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
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
                ],
                'default' => 'left',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-breadcrumbs' => 'justify-content: {{VALUE}};',
                ],
                'selectors_dictionary' => [
                    'left' => 'flex-start',
                    'center' => 'center',
                    'right' => 'flex-end',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section - General
        $this->start_controls_section(
            'general_style_section',
            [
                'label' => __('Ogólne', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'container_Padding',
            [
                'label' => __('Padding', 'simple-lms'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-breadcrumbs' => 'Padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'container_bg_color',
            [
                'label' => __('Background color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-breadcrumbs' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'container_border_radius',
            [
                'label' => __('Border radius', 'simple-lms'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-breadcrumbs' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'items_spacing',
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
                        'max' => 3,
                    ],
                    'rem' => [
                        'min' => 0,
                        'max' => 3,
                    ],
                ],
                'default' => [
                    'size' => 8,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-breadcrumbs' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section - Links
        $this->start_controls_section(
            'links_style_section',
            [
                'label' => __('Linki', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'links_typography',
                'selector' => '{{WRAPPER}} .breadcrumb-item a',
            ]
        );

        $this->start_controls_tabs('links_style_tabs');

        $this->start_controls_tab(
            'links_normal_tab',
            [
                'label' => __('Normalny', 'simple-lms'),
            ]
        );

        $this->add_control(
            'links_color',
            [
                'label' => __('Kolor', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#3498db',
                'selectors' => [
                    '{{WRAPPER}} .breadcrumb-item a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'links_hover_tab',
            [
                'label' => __('Hover', 'simple-lms'),
            ]
        );

        $this->add_control(
            'links_hover_color',
            [
                'label' => __('Kolor', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#2980b9',
                'selectors' => [
                    '{{WRAPPER}} .breadcrumb-item a:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();

        // Style Section - Current Item
        $this->start_controls_section(
            'current_style_section',
            [
                'label' => __('Aktualny element', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'current_typography',
                'selector' => '{{WRAPPER}} .breadcrumb-item.current',
            ]
        );

        $this->add_control(
            'current_color',
            [
                'label' => __('Kolor', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#2c3e50',
                'selectors' => [
                    '{{WRAPPER}} .breadcrumb-item.current' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section - Separator
        $this->start_controls_section(
            'separator_style_section',
            [
                'label' => __('Separator', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'separator_typography',
                'selector' => '{{WRAPPER}} .breadcrumb-separator',
                'condition' => [
                    'separator_icon!' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'separator_color',
            [
                'label' => __('Kolor', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#95a5a6',
                'selectors' => [
                    '{{WRAPPER}} .breadcrumb-separator' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'separator_icon_size',
            [
                'label' => __('Rozmiar ikony', 'simple-lms'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 8,
                        'max' => 30,
                    ],
                    'em' => [
                        'min' => 0.5,
                        'max' => 2,
                    ],
                    'rem' => [
                        'min' => 0.5,
                        'max' => 2,
                    ],
                ],
                'default' => [
                    'size' => 12,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .breadcrumb-separator i' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .breadcrumb-separator svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'separator_icon' => 'yes',
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

        // Get current post
        $post_id = get_queried_object_id();
        $post_type = get_post_type($post_id);

        if (!in_array($post_type, ['course', 'module', 'lesson'])) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="elementor-alert elementor-alert-warning">';
                echo esc_html__('This widget works only on course, module, or lesson pages.', 'simple-lms');
                echo '</div>';
            }
            return;
        }

        $breadcrumbs = $this->build_breadcrumbs($post_id, $post_type, $settings);

        if (empty($breadcrumbs)) {
            return;
        }

        $this->render_breadcrumbs($breadcrumbs, $settings);
    }

    /**
     * Build breadcrumbs array
     */
    private function build_breadcrumbs($post_id, $post_type, $settings): array {
        $breadcrumbs = [];

        // Home
        if ($settings['show_home'] === 'yes') {
            $breadcrumbs[] = [
                'title' => $settings['home_text'],
                'url' => home_url('/'),
                'current' => false,
            ];
        }

        // Build path based on post type
        if ($post_type === 'lesson') {
            $module_id = get_post_meta($post_id, 'lesson_module', true);
            
            if ($module_id) {
                $course_id = get_post_meta($module_id, 'module_course', true);
                
                // Course
                if ($course_id && $settings['show_course'] === 'yes') {
                    $breadcrumbs[] = [
                        'title' => get_the_title($course_id),
                        'url' => get_permalink($course_id),
                        'current' => false,
                    ];
                }
                
                // Module
                if ($settings['show_module'] === 'yes') {
                    $breadcrumbs[] = [
                        'title' => get_the_title($module_id),
                        'url' => get_permalink($module_id),
                        'current' => false,
                    ];
                }
            }
            
            // Current lesson
            if ($settings['show_lesson'] === 'yes') {
                $breadcrumbs[] = [
                    'title' => get_the_title($post_id),
                    'url' => '',
                    'current' => true,
                ];
            }
            
        } elseif ($post_type === 'module') {
            $course_id = get_post_meta($post_id, 'module_course', true);
            
            // Course
            if ($course_id && $settings['show_course'] === 'yes') {
                $breadcrumbs[] = [
                    'title' => get_the_title($course_id),
                    'url' => get_permalink($course_id),
                    'current' => false,
                ];
            }
            
            // Current module
            if ($settings['show_module'] === 'yes') {
                $breadcrumbs[] = [
                    'title' => get_the_title($post_id),
                    'url' => '',
                    'current' => true,
                ];
            }
            
        } elseif ($post_type === 'course') {
            // Current course
            if ($settings['show_course'] === 'yes') {
                $breadcrumbs[] = [
                    'title' => get_the_title($post_id),
                    'url' => '',
                    'current' => true,
                ];
            }
        }

        return $breadcrumbs;
    }

    /**
     * Render breadcrumbs HTML
     */
    private function render_breadcrumbs($breadcrumbs, $settings): void {
        $separator = $settings['separator'] === 'custom' 
            ? $settings['custom_separator'] 
            : $settings['separator'];
        
        $use_icon = $settings['separator_icon'] === 'yes';

        echo '<nav class="simple-lms-breadcrumbs" aria-label="breadcrumb">';
        
        $total = count($breadcrumbs);
        $index = 0;

        foreach ($breadcrumbs as $crumb) {
            $index++;
            $is_last = $index === $total;

            echo '<span class="breadcrumb-item' . ($crumb['current'] ? ' current' : '') . '">';

            if ($crumb['current'] || empty($crumb['url'])) {
                echo esc_html($crumb['title']);
            } else {
                echo '<a href="' . esc_url($crumb['url']) . '">' . esc_html($crumb['title']) . '</a>';
            }

            echo '</span>';

            // Separator (not after last item)
            if (!$is_last) {
                echo '<span class="breadcrumb-separator">';
                
                if ($use_icon) {
                    \Elementor\Icons_Manager::render_icon($settings['separator_icon_select'], ['aria-hidden' => 'true']);
                } else {
                    echo esc_html($separator);
                }
                
                echo '</span>';
            }
        }

        echo '</nav>';

        // Add inline styles
        ?>
        <style>
            .simple-lms-breadcrumbs {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
            }
            .simple-lms-breadcrumbs a {
                text-decoration: none;
                transition: color 0.3s ease;
            }
            .simple-lms-breadcrumbs a:hover {
                text-decoration: underline;
            }
            .simple-lms-breadcrumbs .breadcrumb-item.current {
                font-weight: 600;
            }
        </style>
        <?php
    }
}
