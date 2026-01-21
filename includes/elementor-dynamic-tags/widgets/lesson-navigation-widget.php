<?php
namespace SimpleLMS\Elementor\Widgets;

/**
 * Lesson Navigation Widget
 * Previous/Next lesson navigation buttons
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
 * Lesson Navigation Widget
 */
class Lesson_Navigation_Widget extends Widget_Base {

    /**
     * Get widget name
     */
    public function get_name(): string {
        return 'simple-lms-lesson-navigation';
    }

    /**
     * Get widget title
     */
    public function get_title(): string {
        return __('Lesson Navigation', 'simple-lms');
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
        return ['lesson', 'navigation', 'next', 'previous', 'lesson', 'nawigacja', 'następna', 'poprzednia'];
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
                'label' => __('Lesson ID (optional)', 'simple-lms'),
                'type' => Controls_Manager::NUMBER,
                'default' => '',
                'description' => __('Leave empty to automatically detect current lesson', 'simple-lms'),
            ]
        );

        $this->add_control(
            'show_prev',
            [
                'label' => __('Show Previous button', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'prev_text',
            [
                'label' => __('Previous button text', 'simple-lms'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Previous lesson', 'simple-lms'),
                'condition' => [
                    'show_prev' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_next',
            [
                'label' => __('Show Next button', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'next_text',
            [
                'label' => __('Next button text', 'simple-lms'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Next lesson', 'simple-lms'),
                'condition' => [
                    'show_next' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_icons',
            [
                'label' => __('Show icons', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'return_value' => 'yes',
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'prev_icon',
            [
                'label' => __('Previous icon', 'simple-lms'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-arrow-left',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'show_icons' => 'yes',
                    'show_prev' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'next_icon',
            [
                'label' => __('Next icon', 'simple-lms'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-arrow-right',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'show_icons' => 'yes',
                    'show_next' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'icon_position',
            [
                'label' => __('Icon position', 'simple-lms'),
                'type' => Controls_Manager::SELECT,
                'default' => 'outside',
                'options' => [
                    'outside' => __('Outside (← [] text | text [] →)', 'simple-lms'),
                    'inside' => __('From inside ([] ← text | text → [])', 'simple-lms'),
                ],
                'condition' => [
                    'show_icons' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'layout',
            [
                'label' => __('Button layout', 'simple-lms'),
                'type' => Controls_Manager::SELECT,
                'default' => 'space-between',
                'options' => [
                    'space-between' => __('Stretched (left-right)', 'simple-lms'),
                    'flex-start' => __('Left', 'simple-lms'),
                    'center' => __('Center', 'simple-lms'),
                    'flex-end' => __('Right', 'simple-lms'),
                ],
            ]
        );

        $this->add_responsive_control(
            'gap',
            [
                'label' => __('Spacing between buttons', 'simple-lms'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 10,
                    ],
                    'rem' => [
                        'min' => 0,
                        'max' => 10,
                    ],
                ],
                'default' => [
                    'size' => 15,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-nav-buttons' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style section - Icons
        $this->start_controls_section(
            'icon_style_section',
            [
                'label' => __('Icons', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_icons' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'icon_size',
            [
                'label' => __('Icon size', 'simple-lms'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 60,
                    ],
                    'em' => [
                        'min' => 0.5,
                        'max' => 4,
                    ],
                    'rem' => [
                        'min' => 0.5,
                        'max' => 4,
                    ],
                ],
                'default' => [
                    'size' => 16,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-nav-icon' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .simple-lms-nav-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'icon_spacing',
            [
                'label' => __('Spacing from text', 'simple-lms'),
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
                    '{{WRAPPER}} .simple-lms-nav-prev .simple-lms-nav-icon:first-child' => 'margin-right: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .simple-lms-nav-prev .simple-lms-nav-icon:last-child' => 'margin-left: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .simple-lms-nav-next .simple-lms-nav-icon:first-child' => 'margin-right: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .simple-lms-nav-next .simple-lms-nav-icon:last-child' => 'margin-left: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'icon_color',
            [
                'label' => __('Icon color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-nav-icon' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .simple-lms-nav-icon svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'icon_hover_color',
            [
                'label' => __('Icon color (hover)', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-nav-btn:hover .simple-lms-nav-icon' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .simple-lms-nav-btn:hover .simple-lms-nav-icon svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style section - Buttons
        $this->start_controls_section(
            'button_style_section',
            [
                'label' => __('Buttons', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'selector' => '{{WRAPPER}} .simple-lms-nav-btn',
            ]
        );

        $this->add_responsive_control(
            'button_Padding',
            [
                'label' => __('Padding', 'simple-lms'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', 'rem'],
                'default' => [
                    'top' => '12',
                    'right' => '24',
                    'bottom' => '12',
                    'left' => '24',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-nav-btn' => 'Padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'button_border_radius',
            [
                'label' => __('Border radius', 'simple-lms'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'default' => [
                    'top' => '4',
                    'right' => '4',
                    'bottom' => '4',
                    'left' => '4',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-nav-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->start_controls_tabs('button_style_tabs');

        // Normal state
        $this->start_controls_tab(
            'button_normal_tab',
            [
                'label' => __('Normal', 'simple-lms'),
            ]
        );

        $this->add_control(
            'button_color',
            [
                'label' => __('Text color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-nav-btn:not(:disabled)' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_bg_color',
            [
                'label' => __('Background color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#2196F3',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-nav-btn:not(:disabled)' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'button_border',
                'selector' => '{{WRAPPER}} .simple-lms-nav-btn:not(:disabled)',
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
            'button_hover_color',
            [
                'label' => __('Text color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-nav-btn:not(:disabled):hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_hover_bg_color',
            [
                'label' => __('Background color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#1976D2',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-nav-btn:not(:disabled):hover' => 'background-color: {{VALUE}};',
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
            'button_disabled_color',
            [
                'label' => __('Text color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#999999',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-nav-btn:disabled' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_disabled_bg_color',
            [
                'label' => __('Background color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#e0e0e0',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-nav-btn:disabled' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'button_shadow',
                'selector' => '{{WRAPPER}} .simple-lms-nav-btn:not(:disabled)',
                'separator' => 'before',
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

        if (!$lesson_id || get_post_type($lesson_id) !== 'lesson') {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="elementor-alert elementor-alert-warning">';
                echo esc_html__('Cannot detect valid lesson. Make sure the widget is used on a lesson page or set correct ID.', 'simple-lms');
                echo '</div>';
            }
            return;
        }

        // Get previous and next lessons
        $prev_lesson = \SimpleLMS\Lesson_Helper::getPreviousLesson($lesson_id);
        $next_lesson = \SimpleLMS\Lesson_Helper::getNextLesson($lesson_id);

        $layout = $settings['layout'] ?? 'space-between';
        $show_prev = $settings['show_prev'] === 'yes';
        $show_next = $settings['show_next'] === 'yes';
        $show_icons = $settings['show_icons'] === 'yes';
        $icon_position = $settings['icon_position'] ?? 'outside';

        echo '<div class="simple-lms-nav-buttons" style="display: flex; justify-content: ' . esc_attr($layout) . '; flex-wrap: wrap;">';

        // Previous button
        if ($show_prev) {
            $prev_url = ($prev_lesson && get_post_type($prev_lesson->ID) === 'lesson') ? get_permalink($prev_lesson->ID) : '#';
            $prev_disabled = !$prev_lesson;
            
            echo '<a href="' . esc_url($prev_url) . '" class="simple-lms-nav-btn simple-lms-nav-prev" ' . ($prev_disabled ? 'disabled' : '') . ' style="display: inline-flex; align-items: center; text-decoration: none; cursor: ' . ($prev_disabled ? 'not-allowed' : 'pointer') . '; border: none; transition: all 0.3s ease;' . ($prev_disabled ? ' pointer-events: none;' : '') . '">';
            
            // Icon on the outside (left for prev button)
            if ($show_icons && $icon_position === 'outside') {
                echo '<span class="simple-lms-nav-icon">';
                \Elementor\Icons_Manager::render_icon($settings['prev_icon'], ['aria-hidden' => 'true']);
                echo '</span>';
            }
            
            echo '<span class="simple-lms-nav-text">' . esc_html($settings['prev_text']) . '</span>';
            
            // Icon on the inside (right for prev button)
            if ($show_icons && $icon_position === 'inside') {
                echo '<span class="simple-lms-nav-icon">';
                \Elementor\Icons_Manager::render_icon($settings['prev_icon'], ['aria-hidden' => 'true']);
                echo '</span>';
            }
            
            echo '</a>';
        }

        // Next button
        if ($show_next) {
            $next_url = ($next_lesson && get_post_type($next_lesson->ID) === 'lesson') ? get_permalink($next_lesson->ID) : '#';
            $next_disabled = !$next_lesson;
            
            echo '<a href="' . esc_url($next_url) . '" class="simple-lms-nav-btn simple-lms-nav-next" ' . ($next_disabled ? 'disabled' : '') . ' style="display: inline-flex; align-items: center; text-decoration: none; cursor: ' . ($next_disabled ? 'not-allowed' : 'pointer') . '; border: none; transition: all 0.3s ease;' . ($next_disabled ? ' pointer-events: none;' : '') . '">';
            
            // Icon on the inside (left for next button)
            if ($show_icons && $icon_position === 'inside') {
                echo '<span class="simple-lms-nav-icon">';
                \Elementor\Icons_Manager::render_icon($settings['next_icon'], ['aria-hidden' => 'true']);
                echo '</span>';
            }
            
            echo '<span class="simple-lms-nav-text">' . esc_html($settings['next_text']) . '</span>';
            
            // Icon on the outside (right for next button)
            if ($show_icons && $icon_position === 'outside') {
                echo '<span class="simple-lms-nav-icon">';
                \Elementor\Icons_Manager::render_icon($settings['next_icon'], ['aria-hidden' => 'true']);
                echo '</span>';
            }
            
            echo '</a>';
        }

        echo '</div>';
    }

    /**
     * Render widget output in the editor
     */
    protected function content_template(): void {
        ?>
        <#
        var layout = settings.layout || 'space-between';
        var showPrev = settings.show_prev === 'yes';
        var showNext = settings.show_next === 'yes';
        var showIcons = settings.show_icons === 'yes';
        var iconPosition = settings.icon_position || 'outside';
        var prevIconHTML = elementor.helpers.renderIcon( view, settings.prev_icon, { 'aria-hidden': true }, 'i' , 'object' );
        var nextIconHTML = elementor.helpers.renderIcon( view, settings.next_icon, { 'aria-hidden': true }, 'i' , 'object' );
        #>
        <div class="simple-lms-nav-buttons" style="display: flex; justify-content: {{{layout}}}; flex-wrap: wrap;">
            <# if (showPrev) { #>
                <a href="#" class="simple-lms-nav-btn simple-lms-nav-prev" style="display: inline-flex; align-items: center; text-decoration: none; cursor: pointer; border: none;">
                    <# if (showIcons && iconPosition === 'outside') { #>
                        <span class="simple-lms-nav-icon">
                            <# if (prevIconHTML && prevIconHTML.rendered) { #>
                                {{{ prevIconHTML.value }}}
                            <# } #>
                        </span>
                    <# } #>
                    
                    <span class="simple-lms-nav-text">{{{settings.prev_text}}}</span>
                    
                    <# if (showIcons && iconPosition === 'inside') { #>
                        <span class="simple-lms-nav-icon">
                            <# if (prevIconHTML && prevIconHTML.rendered) { #>
                                {{{ prevIconHTML.value }}}
                            <# } #>
                        </span>
                    <# } #>
                </a>
            <# } #>
            
            <# if (showNext) { #>
                <a href="#" class="simple-lms-nav-btn simple-lms-nav-next" style="display: inline-flex; align-items: center; text-decoration: none; cursor: pointer; border: none;">
                    <# if (showIcons && iconPosition === 'inside') { #>
                        <span class="simple-lms-nav-icon">
                            <# if (nextIconHTML && nextIconHTML.rendered) { #>
                                {{{ nextIconHTML.value }}}
                            <# } #>
                        </span>
                    <# } #>
                    
                    <span class="simple-lms-nav-text">{{{settings.next_text}}}</span>
                    
                    <# if (showIcons && iconPosition === 'outside') { #>
                        <span class="simple-lms-nav-icon">
                            <# if (nextIconHTML && nextIconHTML.rendered) { #>
                                {{{ nextIconHTML.value }}}
                            <# } #>
                        </span>
                    <# } #>
                </a>
            <# } #>
        </div>
        <?php
    }
}
