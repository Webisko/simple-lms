<?php
namespace SimpleLMS\Bricks\Elements;
use SimpleLMS\Progress_Tracker;
use SimpleLMS\Cache_Handler;

if (!defined('ABSPATH')) exit;

class Continue_Learning_Button extends \Bricks\Element {
    public $category = 'simple-lms';
    public $name = 'simple-lms-continue-learning';
    public $icon = 'ti-control-play';

    public function get_label() {
        return esc_html__('Continue Learning Button', 'simple-lms');
    }

    public function set_controls() {
        $this->controls['courseId'] = ['tab'=>'content','label'=>esc_html__('Course ID','simple-lms'),'type'=>'number','default'=>0];
        $this->controls['buttonText'] = ['tab'=>'content','label'=>esc_html__('Button Text','simple-lms'),'type'=>'text','default'=>'Continue learning'];
    }

    public function render() {
        if (!is_user_logged_in()) return;
        $settings = $this->settings;
        $course_id = !empty($settings['courseId']) ? absint($settings['courseId']) : \SimpleLMS\Bricks\Bricks_Integration::get_current_course_id();
        if (!$course_id) return;
        $user_id = get_current_user_id();
        $last_lesson = get_user_meta($user_id, 'last_visited_lesson_'.$course_id, true);
        if (!$last_lesson) {
            $modules = Cache_Handler::getCourseModules($course_id);
            if (empty($modules)) return;
            $lessons = Cache_Handler::getModuleLessons($modules[0]->ID);
            $last_lesson = !empty($lessons) ? $lessons[0]->ID : 0;
        }
        if (!$last_lesson) return;
        echo '<a href="'.esc_url(get_permalink($last_lesson)).'" class="simple-lms-continue-btn" style="display:inline-block;Padding:12px 24px;background:#4CAF50;color:#fff;text-decoration:none;border-radius:4px;font-weight:600">'.esc_html($settings['buttonText']??'Continue learning').'</a>';
    }
}
