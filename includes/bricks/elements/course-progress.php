<?php
namespace SimpleLMS\Bricks\Elements;
use SimpleLMS\Progress_Tracker;

if (!defined('ABSPATH')) exit;

class Course_Progress extends \Bricks\Element {
    public $category = 'simple-lms';
    public $name = 'simple-lms-course-progress';
    public $icon = 'ti-bar-chart';

    public function get_label() {
        return esc_html__('Course Progress Bar', 'simple-lms');
    }

    public function set_controls() {
        $this->controls['courseId'] = ['tab'=>'content','label'=>esc_html__('Course ID','simple-lms'),'type'=>'number','default'=>0];
        $this->controls['height'] = ['tab'=>'content','label'=>esc_html__('Height','simple-lms'),'type'=>'number','default'=>20,'css'=>[['property'=>'height','selector'=>'.progress-bar']]];
        $this->controls['bgColor'] = ['tab'=>'content','label'=>esc_html__('Background','simple-lms'),'type'=>'color','default'=>['hex'=>'#e0e0e0'],'css'=>[['property'=>'background-color','selector'=>'.progress-bar']]];
        $this->controls['fillColor'] = ['tab'=>'content','label'=>esc_html__('Fill Color','simple-lms'),'type'=>'color','default'=>['hex'=>'#4CAF50'],'css'=>[['property'=>'background-color','selector'=>'.progress-fill']]];
    }

    public function render() {
        $settings = $this->settings;
        $course_id = !empty($settings['courseId']) ? absint($settings['courseId']) : \SimpleLMS\Bricks\Bricks_Integration::get_current_course_id();
        if (!$course_id || !is_user_logged_in()) return;
        $progress = Progress_Tracker::getCourseProgress(get_current_user_id(), $course_id);
        echo '<div class="simple-lms-progress-wrapper"><div class="progress-bar" style="border-radius:4px;overflow:hidden"><div class="progress-fill" style="height:100%;width:'.esc_attr($progress).'%"></div></div><div class="progress-text" style="margin-top:8px">'.esc_html($progress).'% '.esc_html__('completed','simple-lms').'</div></div>';
    }
}
