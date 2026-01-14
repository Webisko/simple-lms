<?php
namespace SimpleLMS\Bricks\Elements;
use SimpleLMS\Cache_Handler;
use SimpleLMS\Progress_Tracker;

if (!defined('ABSPATH')) exit;

class Lesson_Progress_Indicator extends \Bricks\Element {
    public $category = 'simple-lms';
    public $name = 'simple-lms-lesson-progress-indicator';
    public $icon = 'ti-pie-chart';

    public function get_label() {
        return esc_html__('Lesson Progress Indicator', 'simple-lms');
    }

    public function set_controls() {
        $this->controls['showText'] = ['tab'=>'content','label'=>esc_html__('Show Text','simple-lms'),'type'=>'checkbox','default'=>true];
    }

    public function render() {
        if (!is_user_logged_in()) return;
        $lesson_id = get_the_ID();
        if (get_post_type($lesson_id) !== 'lesson') return;
        $module_id = wp_get_post_parent_id($lesson_id);
        if (!$module_id) return;
        $lessons = Cache_Handler::getModuleLessons($module_id);
        if (empty($lessons)) return;
        $current_position = 0;
        foreach ($lessons as $i => $lesson) {
            if ($lesson->ID == $lesson_id) { $current_position = $i + 1; break; }
        }
        echo '<div class="simple-lms-lesson-progress-indicator" style="padding:10px 16px;background:#f5f5f5;border-radius:4px;display:inline-block">';
        if ($this->settings['showText'] ?? true) echo '<span>Lekcja </span>';
        echo '<strong>'.$current_position.'</strong> / <strong>'.count($lessons).'</strong>';
        echo '</div>';
    }
}
