<?php
/**
 * Access Status Widget for Elementor
 *
 * Displays user's access status to a course with expiration dates and drip content info
 *
 * @package SimpleLMS
 */

namespace SimpleLMS\Elementor\Widgets;

use SimpleLMS\Access_Control;
use SimpleLMS\Elementor\Elementor_Dynamic_Tags;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Access Status Widget
 */
class Access_Status_Widget extends Widget_Base {

    /**
     * Get widget name
     */
    public function get_name(): string {
        return 'simple_lms_access_status';
    }

    /**
     * Get widget title
     */
    public function get_title(): string {
        return __('Course Access Status', 'simple-lms');
    }

    /**
     * Get widget icon
     */
    public function get_icon(): string {
        return 'eicon-lock-user';
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
        return ['lms', 'course', 'access', 'status', 'lock', 'unlock', 'simple lms'];
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
            'course_id',
            [
                'label' => __('ID kursu', 'simple-lms'),
                'type' => Controls_Manager::NUMBER,
                'default' => 0,
                'description' => __('Leave 0 to automatically detect course from current page', 'simple-lms'),
            ]
        );

        $this->add_control(
            'layout',
            [
                'label' => __('Layout', 'simple-lms'),
                'type' => Controls_Manager::SELECT,
                'default' => 'card',
                'options' => [
                    'card' => __('Karta', 'simple-lms'),
                    'inline' => __('Inline', 'simple-lms'),
                    'badge' => __('Badge', 'simple-lms'),
                ],
            ]
        );

        $this->add_control(
            'show_icon',
            [
                'label' => __('Show icon', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'access_icon',
            [
                'label' => __('Icon (access)', 'simple-lms'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-check-circle',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'show_icon' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'no_access_icon',
            [
                'label' => __('Icon (no access)', 'simple-lms'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-lock',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'show_icon' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'access_text',
            [
                'label' => __('Text (access)', 'simple-lms'),
                'type' => Controls_Manager::TEXT,
                'default' => __('You have access to the course', 'simple-lms'),
            ]
        );

        $this->add_control(
            'no_access_text',
            [
                'label' => __('Text (no access)', 'simple-lms'),
                'type' => Controls_Manager::TEXT,
                'default' => __('No access to course', 'simple-lms'),
            ]
        );

        $this->add_control(
            'show_expiration',
            [
                'label' => __('Show expiration date', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'expiration_prefix',
            [
                'label' => __('Expiration date prefix', 'simple-lms'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Access to:', 'simple-lms'),
                'condition' => [
                    'show_expiration' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'lifetime_text',
            [
                'label' => __('Text (lifetime access)', 'simple-lms'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Lifetime access', 'simple-lms'),
                'condition' => [
                    'show_expiration' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_drip',
            [
                'label' => __('Show drip content info', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'drip_text',
            [
                'label' => __('Tekst drip content', 'simple-lms'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Content unlocked gradually', 'simple-lms'),
                'condition' => [
                    'show_drip' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'alignment',
            [
                'label' => __('Alignment', 'simple-lms'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __('Do lewej', 'simple-lms'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('Center', 'simple-lms'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => __('Do prawej', 'simple-lms'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'default' => 'left',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-access-status' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section - Container
        $this->start_controls_section(
            'container_style_section',
            [
                'label' => __('Kontener', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'container_Padding',
            [
                'label' => __('Padding', 'simple-lms'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', 'rem'],
                'default' => [
                    'top' => '20',
                    'right' => '20',
                    'bottom' => '20',
                    'left' => '20',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-access-status' => 'Padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                    '{{WRAPPER}} .simple-lms-access-status' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'container_border',
                'selector' => '{{WRAPPER}} .simple-lms-access-status',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'container_shadow',
                'selector' => '{{WRAPPER}} .simple-lms-access-status',
            ]
        );

        $this->end_controls_section();

        // Style Section - Access Granted
        $this->start_controls_section(
            'access_style_section',
            [
                'label' => __('Status: Access', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'access_bg_color',
            [
                'label' => __('Background color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#e8f5e9',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-access-status.has-access' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'access_text_color',
            [
                'label' => __('Text color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#2e7d32',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-access-status.has-access' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'access_icon_color',
            [
                'label' => __('Icon color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#4caf50',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-access-status.has-access .access-icon' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .simple-lms-access-status.has-access .access-icon svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section - No Access
        $this->start_controls_section(
            'no_access_style_section',
            [
                'label' => __('Status: No access', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'no_access_bg_color',
            [
                'label' => __('Background color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffebee',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-access-status.no-access' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'no_access_text_color',
            [
                'label' => __('Text color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#c62828',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-access-status.no-access' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'no_access_icon_color',
            [
                'label' => __('Icon color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#f44336',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-access-status.no-access .access-icon' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .simple-lms-access-status.no-access .access-icon svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section - Typography
        $this->start_controls_section(
            'typography_style_section',
            [
                'label' => __('Typografia', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'main_text_typography',
                'label' => __('Main text', 'simple-lms'),
                'selector' => '{{WRAPPER}} .access-main-text',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'meta_text_typography',
                'label' => __('Tekst meta (daty)', 'simple-lms'),
                'selector' => '{{WRAPPER}} .access-meta-text',
            ]
        );

        $this->add_responsive_control(
            'icon_size',
            [
                'label' => __('Rozmiar ikony', 'simple-lms'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 16,
                        'max' => 100,
                    ],
                    'em' => [
                        'min' => 1,
                        'max' => 6,
                    ],
                    'rem' => [
                        'min' => 1,
                        'max' => 6,
                    ],
                ],
                'default' => [
                    'size' => 24,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .access-icon i' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .access-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'icon_spacing',
            [
                'label' => __('Spacing ikony', 'simple-lms'),
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
                    'size' => 10,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .access-icon' => 'margin-right: {{SIZE}}{{UNIT}};',
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
            : Elementor_Dynamic_Tags::get_current_course_id();

        if (!$course_id) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="elementor-alert elementor-alert-warning">';
                echo esc_html__('Cannot detect course. Make sure the widget is used on a course page.', 'simple-lms');
                echo '</div>';
            }
            return;
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            $this->render_status(false, $settings, $course_id);
            return;
        }

        $user_id = get_current_user_id();
        $has_access = Access_Control::userHasAccessToCourse($user_id, $course_id);

        $this->render_status($has_access, $settings, $course_id, $user_id);
    }

    /**
     * Render access status
     */
    private function render_status($has_access, $settings, $course_id, $user_id = 0): void {
        $layout = $settings['layout'];
        $show_icon = $settings['show_icon'] === 'yes';
        $show_expiration = $settings['show_expiration'] === 'yes';
        $show_drip = $settings['show_drip'] === 'yes';

        $status_class = $has_access ? 'has-access' : 'no-access';
        $icon = $has_access ? $settings['access_icon'] : $settings['no_access_icon'];
        $text = $has_access ? $settings['access_text'] : $settings['no_access_text'];

        echo '<div class="simple-lms-access-status ' . esc_attr($status_class . ' layout-' . $layout) . '">';

        // Icon and main text
        echo '<div class="access-status-main">';
        
        if ($show_icon) {
            echo '<span class="access-icon">';
            \Elementor\Icons_Manager::render_icon($icon, ['aria-hidden' => 'true']);
            echo '</span>';
        }
        
        echo '<span class="access-main-text">' . esc_html($text) . '</span>';
        echo '</div>';

        // Expiration date (only if has access)
        if ($has_access && $show_expiration && $user_id) {
            $expiration_date = get_user_meta($user_id, 'course_access_expires_' . $course_id, true);
            
            if ($expiration_date && $expiration_date !== 'lifetime') {
                $formatted_date = date_i18n(get_option('date_format'), strtotime($expiration_date));
                echo '<div class="access-meta-text access-expiration">';
                echo esc_html($settings['expiration_prefix']) . ' <strong>' . esc_html($formatted_date) . '</strong>';
                echo '</div>';
            } elseif ($expiration_date === 'lifetime') {
                echo '<div class="access-meta-text access-lifetime">';
                echo esc_html($settings['lifetime_text']);
                echo '</div>';
            }
        }

        // Drip content info
        if ($has_access && $show_drip) {
            $drip_enabled = get_post_meta($course_id, 'course_drip_enabled', true);
            
            if ($drip_enabled) {
                echo '<div class="access-meta-text access-drip">';
                echo '<i class="fas fa-clock"></i> ' . esc_html($settings['drip_text']);
                echo '</div>';
            }
        }

        echo '</div>';

        // Add layout-specific styles
        $this->add_layout_styles($layout);
    }

    /**
     * Add inline styles for layout
     */
    private function add_layout_styles($layout): void {
        ?>
        <style>
            .simple-lms-access-status {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            .simple-lms-access-status.layout-inline {
                flex-direction: row;
                align-items: center;
                flex-wrap: wrap;
            }
            .simple-lms-access-status.layout-badge {
                display: inline-flex;
                Padding: 8px 16px !important;
            }
            .simple-lms-access-status .access-status-main {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .simple-lms-access-status .access-meta-text {
                font-size: 0.9em;
                opacity: 0.9;
            }
            .simple-lms-access-status.layout-inline .access-meta-text {
                margin-left: auto;
            }
        </style>
        <?php
    }
}
