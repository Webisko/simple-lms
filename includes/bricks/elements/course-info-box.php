<?php
namespace SimpleLMS\Bricks\Elements;
use SimpleLMS\Cache_Handler;
use SimpleLMS\Progress_Tracker;

if (!defined('ABSPATH')) exit;

class Course_Info_Box extends \Bricks\Element {
    public $category = 'simple-lms';
    public $name = 'simple-lms-course-info-box';
    public $icon = 'ti-info-alt';

    public function get_label() {
        return esc_html__('Course Info Box', 'simple-lms');
    }

    public function set_controls() {
        $this->controls['courseId'] = ['tab'=>'content','label'=>esc_html__('Course ID','simple-lms'),'type'=>'number','default'=>0];
        $this->controls['layout'] = ['tab'=>'content','label'=>esc_html__('Layout','simple-lms'),'type'=>'select','options'=>['vertical'=>'Vertical','horizontal'=>'Horizontal'],'default'=>'vertical'];
    }

    public function render() {
        $settings = $this->settings;
        $course_id = !empty($settings['courseId']) ? absint($settings['courseId']) : \SimpleLMS\Bricks\Bricks_Integration::get_current_course_id();
        if (!$course_id) return;
        $modules = Cache_Handler::getCourseModules($course_id);
        $total_lessons = 0; foreach ($modules as $m) { $total_lessons += count(Cache_Handler::getModuleLessons($m->ID)); }
        $progress = is_user_logged_in() ? Progress_Tracker::getCourseProgress(get_current_user_id(), $course_id) : 0;
        $layout = $settings['layout'] ?? 'vertical';
        echo '<div class="simple-lms-course-info layout-'.$layout.'" style="Padding:16px;border:1px solid #eee;border-radius:6px">';
        echo '<div class="info-item" style="margin-bottom:10px"><strong>Moduley:</strong> '.count($modules).'</div>';
        echo '<div class="info-item" style="margin-bottom:10px"><strong>Lekcje:</strong> '.$total_lessons.'</div>';
        if (is_user_logged_in()) echo '<div class="info-item"><strong>Progress:</strong> '.esc_html($progress).'%</div>';
        echo '</div>';
    }
}
