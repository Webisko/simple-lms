<?php
namespace SimpleLMS\Bricks\Elements;

if (!defined('ABSPATH')) exit;

class Lesson_Content extends \Bricks\Element {
    public $category = 'simple-lms';
    public $name = 'simple-lms-lesson-content';
    public $icon = 'ti-text';

    public function get_label() {
        return esc_html__('Lesson Content', 'simple-lms');
    }

    public function set_controls() {
        $this->controls['lessonId'] = [
            'tab' => 'content',
            'label' => esc_html__('Lesson ID', 'simple-lms'),
            'type' => 'number',
            'default' => 0,
        ];
    }

    public function render() {
        $settings = $this->settings;
        $lesson_id = !empty($settings['lessonId']) ? absint($settings['lessonId']) : get_the_ID();
        if (function_exists('SimpleLMS\\Compat\\Multilingual_Compat::map_post_id')) {
            $lesson_id = \SimpleLMS\Compat\Multilingual_Compat::map_post_id($lesson_id, 'lesson');
        }
        if (get_post_type($lesson_id) !== 'lesson') return;
        echo '<div ' . $this->render_attributes('_root') . '>';
        echo apply_filters('the_content', get_post_field('post_content', $lesson_id));
        echo '</div>';
    }
}
