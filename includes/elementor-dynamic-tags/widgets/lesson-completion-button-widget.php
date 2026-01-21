<?php
namespace SimpleLMS\Elementor\Widgets;

/**
 * Lesson Completion Button Widget
 * Interactive button to mark lesson as complete/incomplete
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
 * Lesson Completion Button Widget
 */
class Lesson_Completion_Button_Widget extends Widget_Base {

    /**
     * Get widget name
     */
    public function get_name(): string {
        return 'simple-lms-lesson-completion-button';
    }

    /**
     * Get widget title
     */
    public function get_title(): string {
        return __('Lesson completion button', 'simple-lms');
    }

    /**
     * Get widget icon
     */
    public function get_icon(): string {
        return 'eicon-button';
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
        return ['lesson', 'complete', 'button', 'lesson', 'ukończenie', 'przycisk', 'toggle'];
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
            'complete_text',
            [
                'label' => __('Text: Mark as completed', 'simple-lms'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Mark as completed', 'simple-lms'),
            ]
        );

        $this->add_control(
            'incomplete_text',
            [
                'label' => __('Text: Mark as incomplete', 'simple-lms'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Mark as incomplete', 'simple-lms'),
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
            'icon_position',
            [
                'label' => __('Icon position', 'simple-lms'),
                'type' => Controls_Manager::SELECT,
                'default' => 'left',
                'options' => [
                    'left' => __('Left', 'simple-lms'),
                    'right' => __('Right', 'simple-lms'),
                ],
                'condition' => [
                    'show_icon' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'button_align',
            [
                'label' => __('Alignment', 'simple-lms'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __('Left', 'simple-lms'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('Center', 'simple-lms'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => __('Right', 'simple-lms'),
                        'icon' => 'eicon-text-align-right',
                    ],
                    'justify' => [
                        'title' => __('Full width', 'simple-lms'),
                        'icon' => 'eicon-text-align-justify',
                    ],
                ],
                'default' => 'left',
            ]
        );

        $this->end_controls_section();

        // Style section - Button
        $this->start_controls_section(
            'button_style_section',
            [
                'label' => __('Button', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'selector' => '{{WRAPPER}} .simple-lms-completion-btn',
            ]
        );

        $this->add_responsive_control(
            'button_Padding',
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
                    '{{WRAPPER}} .simple-lms-completion-btn' => 'Padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                    '{{WRAPPER}} .simple-lms-completion-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->start_controls_tabs('button_style_tabs');

        // Normal state - Incomplete
        $this->start_controls_tab(
            'button_incomplete_tab',
            [
                'label' => __('Incomplete', 'simple-lms'),
            ]
        );

        $this->add_control(
            'incomplete_color',
            [
                'label' => __('Text color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-completion-btn:not(.completed)' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'incomplete_bg_color',
            [
                'label' => __('Background color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#2196F3',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-completion-btn:not(.completed)' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'incomplete_border',
                'selector' => '{{WRAPPER}} .simple-lms-completion-btn:not(.completed)',
            ]
        );

        $this->end_controls_tab();

        // Completed state
        $this->start_controls_tab(
            'button_complete_tab',
            [
                'label' => __('Completed', 'simple-lms'),
            ]
        );

        $this->add_control(
            'complete_color',
            [
                'label' => __('Text color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-completion-btn.completed' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'complete_bg_color',
            [
                'label' => __('Background color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#4CAF50',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-completion-btn.completed' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'complete_border',
                'selector' => '{{WRAPPER}} .simple-lms-completion-btn.completed',
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
            'hover_color',
            [
                'label' => __('Text color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-completion-btn:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'hover_bg_color',
            [
                'label' => __('Background color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-completion-btn:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'button_shadow',
                'selector' => '{{WRAPPER}} .simple-lms-completion-btn',
                'separator' => 'before',
            ]
        );

        $this->end_controls_section();

        // Style section - Icon
        $this->start_controls_section(
            'icon_style_section',
            [
                'label' => __('Icon', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_icon' => 'yes',
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
                        'max' => 50,
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
                    '{{WRAPPER}} .simple-lms-completion-icon' => 'font-size: {{SIZE}}{{UNIT}};',
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
                        'max' => 30,
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
                    '{{WRAPPER}} .simple-lms-completion-btn.icon-left .simple-lms-completion-icon' => 'margin-right: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .simple-lms-completion-btn.icon-right .simple-lms-completion-icon' => 'margin-left: {{SIZE}}{{UNIT}};',
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
                echo '<div class="elementor-alert elementor-alert-warning">';
                echo esc_html__('Cannot detect lesson. Make sure the widget is used on a lesson page.', 'simple-lms');
                echo '</div>';
            }
            return;
        }

        // Check if lesson is completed
        $is_completed = \SimpleLMS\Lesson_Helper::isLessonCompleted($lesson_id);
        
        $button_text = $is_completed ? $settings['incomplete_text'] : $settings['complete_text'];
        $button_class = 'simple-lms-completion-btn';
        if ($is_completed) {
            $button_class .= ' completed';
        }
        
        $icon_position = $settings['icon_position'] ?? 'left';
        $button_class .= ' icon-' . $icon_position;

        $align = $settings['button_align'] ?? 'left';
        $wrapper_style = '';
        if ($align === 'center') {
            $wrapper_style = 'text-align: center;';
        } elseif ($align === 'right') {
            $wrapper_style = 'text-align: right;';
        } elseif ($align === 'justify') {
            $wrapper_style = '';
            $button_class .= ' btn-block';
        }

        echo '<div class="simple-lms-completion-wrapper" style="' . esc_attr($wrapper_style) . '">';
        echo '<button type="button" class="' . esc_attr($button_class) . '" data-lesson-id="' . esc_attr($lesson_id) . '" style="display: inline-flex; align-items: center; justify-content: center; cursor: pointer; border: none; transition: all 0.3s ease;' . ($align === 'justify' ? ' width: 100%;' : '') . '">';
        
        // Icon before text
        if ($settings['show_icon'] === 'yes' && $icon_position === 'left') {
            echo '<span class="simple-lms-completion-icon">';
            echo $is_completed ? '✓' : '○';
            echo '</span>';
        }
        
        echo '<span class="simple-lms-completion-text">' . esc_html($button_text) . '</span>';
        
        // Icon after text
        if ($settings['show_icon'] === 'yes' && $icon_position === 'right') {
            echo '<span class="simple-lms-completion-icon">';
            echo $is_completed ? '✓' : '○';
            echo '</span>';
        }
        
        echo '</button>';
        echo '</div>';

        // Add inline JS for toggle functionality
        ?>
        <script>
        (function() {
            const btn = document.querySelector('.simple-lms-completion-btn[data-lesson-id="<?php echo esc_js($lesson_id); ?>"]');
            if (!btn || !window.simpleLms) return;

            btn.addEventListener('click', function() {
                const lessonId = this.dataset.lessonId;
                const isCompleted = this.classList.contains('completed');
                
                // Disable button during request
                this.disabled = true;
                this.style.opacity = '0.6';

                // AJAX request
                fetch(simpleLms.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'simple_lms_toggle_lesson_completion',
                        nonce: simpleLms.nonce,
                        lesson_id: lessonId,
                        completed: isCompleted ? '0' : '1'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Toggle button state
                        this.classList.toggle('completed');
                        const icon = this.querySelector('.simple-lms-completion-icon');
                        const text = this.querySelector('.simple-lms-completion-text');
                        
                        if (this.classList.contains('completed')) {
                            if (icon) icon.textContent = '✓';
                            if (text) text.textContent = '<?php echo esc_js($settings['incomplete_text']); ?>';
                        } else {
                            if (icon) icon.textContent = '○';
                            if (text) text.textContent = '<?php echo esc_js($settings['complete_text']); ?>';
                        }

                        // Trigger custom event for other widgets to update
                        document.dispatchEvent(new CustomEvent('simpleLmsLessonToggled', {
                            detail: { lessonId: lessonId, completed: !isCompleted }
                        }));
                    }
                })
                .catch(error => console.error('Error:', error))
                .finally(() => {
                    this.disabled = false;
                    this.style.opacity = '1';
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
        <#
        var showIcon = settings.show_icon === 'yes';
        var iconPosition = settings.icon_position || 'left';
        var align = settings.button_align || 'left';
        var wrapperStyle = '';
        var buttonStyle = 'display: inline-flex; align-items: center; justify-content: center; cursor: pointer; border: none;';
        
        if (align === 'center') {
            wrapperStyle = 'text-align: center;';
        } else if (align === 'right') {
            wrapperStyle = 'text-align: right;';
        } else if (align === 'justify') {
            buttonStyle += ' width: 100%;';
        }
        #>
        <div class="simple-lms-completion-wrapper" style="{{{wrapperStyle}}}">
            <button type="button" class="simple-lms-completion-btn icon-{{{iconPosition}}}" style="{{{buttonStyle}}}">
                <# if (showIcon && iconPosition === 'left') { #>
                    <span class="simple-lms-completion-icon">○</span>
                <# } #>
                
                <span class="simple-lms-completion-text">{{{settings.complete_text}}}</span>
                
                <# if (showIcon && iconPosition === 'right') { #>
                    <span class="simple-lms-completion-icon">○</span>
                <# } #>
            </button>
        </div>
        <?php
    }
}
