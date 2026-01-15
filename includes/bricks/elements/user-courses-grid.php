<?php
namespace SimpleLMS\Bricks\Elements;
use SimpleLMS\Progress_Tracker;

if (!defined('ABSPATH')) exit;

class User_Courses_Grid extends \Bricks\Element {
    public $category = 'simple-lms';
    public $name = 'simple-lms-user-courses-grid';
    public $icon = 'ti-layout-grid2';

    public function get_label() {
        return esc_html__('User Courses Grid', 'simple-lms');
    }

    public function set_controls() {
        $this->controls['columns'] = ['tab'=>'content','label'=>esc_html__('Columns','simple-lms'),'type'=>'select','options'=>['1'=>'1','2'=>'2','3'=>'3','4'=>'4'],'default'=>'3'];
    }

    public function render() {
        if (!is_user_logged_in()) return;
        $user_id = get_current_user_id();
        $user_courses = get_user_meta($user_id, 'enrolled_courses', true);
        if (empty($user_courses) || !is_array($user_courses)) return;
        $columns = $this->settings['columns'] ?? '3';
        echo '<div class="simple-lms-user-courses" style="display:grid;grid-template-columns:repeat('.$columns.',1fr);gap:20px">';
        foreach ($user_courses as $course_id) {
            $course = get_post($course_id);
            if (!$course) continue;
            $progress = Progress_Tracker::getCourseProgress($user_id, $course_id);
            echo '<div class="course-card" style="border:1px solid #eee;border-radius:8px;overflow:hidden">';
            if (has_post_thumbnail($course_id)) echo '<div class="course-thumb">'.get_the_post_thumbnail($course_id, 'medium', ['style'=>'width:100%;height:auto']).'</div>';
            echo '<div style="Padding:15px">';
            echo '<h3 style="margin:0 0 10px"><a href="'.esc_url(get_permalink($course)).'" style="color:#333;text-decoration:none">'.esc_html($course->post_title).'</a></h3>';
            echo '<div class="progress-bar" style="height:8px;background:#eee;border-radius:4px;overflow:hidden;margin-bottom:10px"><div style="width:'.$progress.'%;height:100%;background:#4CAF50"></div></div>';
            echo '<span style="font-size:0.9em;color:#666">Progress: '.$progress.'%</span>';
            echo '</div></div>';
        }
        echo '</div>';
    }
}
