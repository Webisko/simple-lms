<?php
namespace SimpleLMS\Elementor\Widgets;

/**
 * Lesson Featured Image Widget
 * Displays the featured image of a lesson with option to hide when video is present
 *
 * @package SimpleLMS\Elementor
 */

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use SimpleLMS\Elementor\Elementor_Dynamic_Tags;

if (!defined('ABSPATH')) {
    exit;
}

// Ensure Elementor is loaded
if (!class_exists('\Elementor\Widget_Base')) {
    return;
}

/**
 * Lesson Featured Image Widget
 */
class Lesson_Featured_Image_Widget extends Widget_Base {

    /**
     * Get widget name
     */
    public function get_name(): string {
        return 'simple-lms-lesson-featured-image';
    }

    /**
     * Get widget title
     */
    public function get_title(): string {
        return __('Lesson Featured Image', 'simple-lms');
    }

    /**
     * Get widget icon
     */
    public function get_icon(): string {
        return 'eicon-featured-image';
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
        return ['lesson', 'featured', 'image', 'thumbnail', 'lekcja', 'obrazek', 'miniatura'];
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
                'label' => __('Lesson ID', 'simple-lms'),
                'type' => Controls_Manager::NUMBER,
                'default' => 0,
                'description' => __('Leave 0 to automatically detect lesson from current page', 'simple-lms'),
            ]
        );

        $this->add_control(
            'hide_when_video',
            [
                'label' => __('Hide when video present', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => '',
                'return_value' => 'yes',
                'description' => __('Hide featured image when lesson has video', 'simple-lms'),
            ]
        );

        $this->add_control(
            'fallback_image',
            [
                'label' => __('Fallback image', 'simple-lms'),
                'type' => Controls_Manager::MEDIA,
                'description' => __('Image to display when lesson has no featured image', 'simple-lms'),
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Image_Size::get_type(),
            [
                'name' => 'image_size',
                'default' => 'large',
            ]
        );

        $this->add_responsive_control(
            'alignment',
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
                ],
                'default' => 'center',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-featured-image' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style section
        $this->start_controls_section(
            'style_section',
            [
                'label' => __('Image style', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'width',
            [
                'label' => __('Width', 'simple-lms'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'vw'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 1000,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-featured-image img' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'height',
            [
                'label' => __('Height', 'simple-lms'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'vh', 'auto'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 1000,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-featured-image img' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'object_fit',
            [
                'label' => __('Object Fit', 'simple-lms'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    '' => __('Default', 'simple-lms'),
                    'fill' => __('Fill', 'simple-lms'),
                    'cover' => __('Cover', 'simple-lms'),
                    'contain' => __('Contain', 'simple-lms'),
                ],
                'default' => 'cover',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-featured-image img' => 'object-fit: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'border_radius',
            [
                'label' => __('Border radius', 'simple-lms'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-featured-image img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'image_border',
                'selector' => '{{WRAPPER}} .simple-lms-featured-image img',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'image_shadow',
                'selector' => '{{WRAPPER}} .simple-lms-featured-image img',
            ]
        );

        $this->add_control(
            'opacity',
            [
                'label' => __('Opacity', 'simple-lms'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 1,
                        'step' => 0.01,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-featured-image img' => 'opacity: {{SIZE}};',
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
        $lesson_id = $settings['lesson_id'];

        // Auto-detect lesson ID
        if (empty($lesson_id)) {
            $lesson_id = get_the_ID();
        }

        // Verify it's a lesson
        if (get_post_type($lesson_id) !== 'lesson') {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="simple-lms-featured-image">';
                echo '<p>' . esc_html__('This widget works only on lesson pages.', 'simple-lms') . '</p>';
                echo '</div>';
            }
            return;
        }

        // Check if should hide when video present
        if ($settings['hide_when_video'] === 'yes') {
            $video_url = get_post_meta($lesson_id, '_simple_lms_video_url', true);
            $video_embed = get_post_meta($lesson_id, '_simple_lms_video_embed', true);
            
            if (!empty($video_url) || !empty($video_embed)) {
                return; // Don't display image when video is present
            }
        }

        // Get featured image
        $image_id = get_post_thumbnail_id($lesson_id);
        
        // Use fallback if no featured image
        if (!$image_id && !empty($settings['fallback_image']['id'])) {
            $image_id = $settings['fallback_image']['id'];
        }

        if (!$image_id) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="simple-lms-featured-image">';
                echo '<p>' . esc_html__('No featured image set for this lesson.', 'simple-lms') . '</p>';
                echo '</div>';
            }
            return;
        }

        $image_size = $settings['image_size_size'];
        $image_html = wp_get_attachment_image($image_id, $image_size, false, ['class' => 'simple-lms-lesson-image']);

        echo '<div class="simple-lms-featured-image">';
        echo $image_html;
        echo '</div>';
    }
}
