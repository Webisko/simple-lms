<?php
/**
 * Course Progress Bar Widget
 * Displays course completion progress with visual bar
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
 * Course Progress Bar Widget
 */
class Course_Progress_Widget extends Widget_Base {

    /**
     * Get widget name
     */
    public function get_name(): string {
        return 'simple-lms-course-progress';
    }

    /**
     * Get widget title
     */
    public function get_title(): string {
        return __('Pasek postępu kursu', 'simple-lms');
    }

    /**
     * Get widget icon
     */
    public function get_icon(): string {
        return 'eicon-skill-bar';
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
        return ['course', 'progress', 'bar', 'kurs', 'postęp', 'pasek', 'completion', 'ukończenie'];
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
            'show_text',
            [
                'label' => __('Pokaż tekst postępu', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_percentage',
            [
                'label' => __('Pokaż procent', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'text_format',
            [
                'label' => __('Format tekstu', 'simple-lms'),
                'type' => Controls_Manager::SELECT,
                'default' => 'both',
                'options' => [
                    'both' => __('completed / Wszystkie lekcje', 'simple-lms'),
                    'completed' => __('Tylko completed', 'simple-lms'),
                    'remaining' => __('Pozostałe do ukończenia', 'simple-lms'),
                ],
                'condition' => [
                    'show_text' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'hide_when_complete',
            [
                'label' => __('Ukryj gdy 100% completed', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => '',
                'return_value' => 'yes',
            ]
        );

        $this->end_controls_section();

        // Style section - Progress Bar
        $this->start_controls_section(
            'bar_style_section',
            [
                'label' => __('Pasek postępu', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'bar_height',
            [
                'label' => __('Wysokość paska', 'simple-lms'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 5,
                        'max' => 50,
                    ],
                    '%' => [
                        'min' => 1,
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
                    'size' => 20,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-progress-bar' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'bar_bg_color',
            [
                'label' => __('Background color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#e0e0e0',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-progress-bar' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'bar_fill_color',
            [
                'label' => __('Kolor postępu', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#4CAF50',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-progress-fill' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'bar_border_radius',
            [
                'label' => __('Border radius', 'simple-lms'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'default' => [
                    'top' => '10',
                    'right' => '10',
                    'bottom' => '10',
                    'left' => '10',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-progress-bar' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .simple-lms-progress-fill' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'bar_shadow',
                'selector' => '{{WRAPPER}} .simple-lms-progress-bar',
            ]
        );

        $this->end_controls_section();

        // Style section - Text
        $this->start_controls_section(
            'text_style_section',
            [
                'label' => __('Tekst postępu', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_text' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'text_color',
            [
                'label' => __('Text color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#333',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-progress-text' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'text_typography',
                'selector' => '{{WRAPPER}} .simple-lms-progress-text',
            ]
        );

        $this->add_responsive_control(
            'text_margin',
            [
                'label' => __('Odstęp od paska', 'simple-lms'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 20,
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
                    '{{WRAPPER}} .simple-lms-progress-text' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'text_align',
            [
                'label' => __('Wyrównanie tekstu', 'simple-lms'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __('Do lewej', 'simple-lms'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('Do środka', 'simple-lms'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => __('Do prawej', 'simple-lms'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'default' => 'left',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-progress-text' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style section - Percentage
        $this->start_controls_section(
            'percentage_style_section',
            [
                'label' => __('Procent', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_percentage' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'percentage_position',
            [
                'label' => __('Pozycja procentu', 'simple-lms'),
                'type' => Controls_Manager::SELECT,
                'default' => 'inside',
                'options' => [
                    'inside' => __('Wewnątrz paska', 'simple-lms'),
                    'outside' => __('Obok paska', 'simple-lms'),
                ],
            ]
        );

        $this->add_control(
            'percentage_color',
            [
                'label' => __('Kolor', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-progress-percentage' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'percentage_typography',
                'selector' => '{{WRAPPER}} .simple-lms-progress-percentage',
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

        if (!$course_id || get_post_type($course_id) !== 'course') {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="elementor-alert elementor-alert-warning">';
                echo esc_html__('Cannot detect valid course. Make sure the widget is used on a course page or set correct ID.', 'simple-lms');
                echo '</div>';
            }
            return;
        }

        // Get course progress
        $progress = $this->get_course_progress($course_id);

        // Hide if complete and setting enabled
        if ($settings['hide_when_complete'] === 'yes' && $progress['percentage'] >= 100) {
            return;
        }

        $percentage_position = $settings['percentage_position'] ?? 'inside';

        echo '<div class="simple-lms-progress-wrapper">';

        // Show text above bar
        if ($settings['show_text'] === 'yes') {
            echo '<div class="simple-lms-progress-text">';
            echo $this->get_progress_text($progress, $settings['text_format']);
            echo '</div>';
        }

        // Progress bar container
        echo '<div class="simple-lms-progress-container" style="display: flex; align-items: center; gap: 10px;">';

        // Progress bar
        echo '<div class="simple-lms-progress-bar" style="flex: 1; position: relative; overflow: hidden;">';
        echo '<div class="simple-lms-progress-fill" style="width: ' . esc_attr($progress['percentage']) . '%; height: 100%; transition: width 0.5s ease;">';
        
        // Percentage inside bar
        if ($settings['show_percentage'] === 'yes' && $percentage_position === 'inside') {
            echo '<span class="simple-lms-progress-percentage" style="position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); font-weight: bold; white-space: nowrap;">';
            echo esc_html($progress['percentage']) . '%';
            echo '</span>';
        }
        
        echo '</div>';
        echo '</div>';

        // Percentage outside bar
        if ($settings['show_percentage'] === 'yes' && $percentage_position === 'outside') {
            echo '<span class="simple-lms-progress-percentage" style="font-weight: bold; white-space: nowrap;">';
            echo esc_html($progress['percentage']) . '%';
            echo '</span>';
        }

        echo '</div>'; // .simple-lms-progress-container
        echo '</div>'; // .simple-lms-progress-wrapper
    }

    /**
     * Get course progress data
     */
    private function get_course_progress(int $course_id): array {
        if (!is_user_logged_in()) {
            return [
                'completed' => 0,
                'total' => 0,
                'percentage' => 0,
            ];
        }

        $user_id = get_current_user_id();

        // Use unified API for total lessons
        $total_lessons = \SimpleLMS\Progress_Tracker::getTotalLessonsCount($course_id);
        if ($total_lessons === 0) {
            return ['completed'=>0,'total'=>0,'remaining'=>0,'percentage'=>0];
        }

        // Use unified API for completed lessons
        $completed_lessons = \SimpleLMS\Progress_Tracker::getCompletedLessonsCount($user_id, $course_id);

        $percentage = round(($completed_lessons / $total_lessons) * 100);

        return [
            'completed' => $completed_lessons,
            'total' => $total_lessons,
            'remaining' => $total_lessons - $completed_lessons,
            'percentage' => $percentage,
        ];
    }

    /**
     * Get progress text based on format
     */
    private function get_progress_text(array $progress, string $format): string {
        switch ($format) {
            case 'completed':
                return sprintf(
                    __('Ukończono: %d lekcji', 'simple-lms'),
                    $progress['completed']
                );
            case 'remaining':
                return sprintf(
                    __('Pozostało: %d lekcji', 'simple-lms'),
                    $progress['remaining']
                );
            case 'both':
            default:
                return sprintf(
                    __('%d of %d lessons completed', 'simple-lms'),
                    $progress['completed'],
                    $progress['total']
                );
        }
    }

    /**
     * Render widget output in the editor
     */
    protected function content_template(): void {
        ?>
        <#
        var showText = settings.show_text === 'yes';
        var showPercentage = settings.show_percentage === 'yes';
        var percentagePosition = settings.percentage_position || 'inside';
        #>
        <div class="simple-lms-progress-wrapper">
            <# if (showText) { #>
                <div class="simple-lms-progress-text">
                    <?php echo esc_html__('15 of 20 lessons completed', 'simple-lms'); ?>
                </div>
            <# } #>
            
            <div class="simple-lms-progress-container" style="display: flex; align-items: center; gap: 10px;">
                <div class="simple-lms-progress-bar" style="flex: 1; position: relative; overflow: hidden;">
                    <div class="simple-lms-progress-fill" style="width: 75%; height: 100%;">
                        <# if (showPercentage && percentagePosition === 'inside') { #>
                            <span class="simple-lms-progress-percentage" style="position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); font-weight: bold;">75%</span>
                        <# } #>
                    </div>
                </div>
                
                <# if (showPercentage && percentagePosition === 'outside') { #>
                    <span class="simple-lms-progress-percentage" style="font-weight: bold;">75%</span>
                <# } #>
            </div>
        </div>
        <?php
    }
}
