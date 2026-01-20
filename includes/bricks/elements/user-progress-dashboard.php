<?php
namespace SimpleLMS\Bricks\Elements;
use SimpleLMS\Progress_Tracker;
use SimpleLMS\Cache_Handler;

if (!defined('ABSPATH')) exit;

class User_Progress_Dashboard extends \Bricks\Element {
    public $category = 'simple-lms';
    public $name = 'simple-lms-user-progress-dashboard';
    public $icon = 'ti-dashboard';

    public function get_label() {
        return esc_html__('User Progress Dashboard', 'simple-lms');
    }

    public function set_controls() {
        $this->controls['title'] = ['tab'=>'content','label'=>esc_html__('Dashboard Title','simple-lms'),'type'=>'text','default'=>'My Progress Dashboard'];
    }

    public function render() {
        if (!is_user_logged_in()) return;
        $user_id = get_current_user_id();
        $user_courses = get_user_meta($user_id, 'enrolled_courses', true);
        if (empty($user_courses) || !is_array($user_courses)) {
            echo '<div class="simple-lms-dashboard" style="Padding:20px;background:#f9f9f9;border-radius:8px"><p>Nie jesteś zapisany na żaden kurs.</p></div>';
            return;
        }
        echo '<div class="simple-lms-user-dashboard" style="Padding:20px;background:#f9f9f9;border-radius:8px">';
        if (!empty($this->settings['title'])) echo '<h2 style="margin:0 0 20px">'.esc_html($this->settings['title']).'</h2>';
        echo '<div class="dashboard-stats" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;margin-bottom:30px">';
        $total_progress = 0;
        foreach ($user_courses as $course_id) { $total_progress += Progress_Tracker::getCourseProgress($user_id, $course_id); }
        $avg_progress = count($user_courses) > 0 ? round($total_progress / count($user_courses)) : 0;
        echo '<div style="Padding:20px;background:#fff;border-radius:6px;text-align:center"><div style="font-size:32px;font-weight:700;color:#2196F3">'.count($user_courses).'</div><div style="color:#666">Zapisanych kursów</div></div>';
        echo '<div style="Padding:20px;background:#fff;border-radius:6px;text-align:center"><div style="font-size:32px;font-weight:700;color:#4CAF50">'.$avg_progress.'%</div><div style="color:#666">Medium postęp</div></div>';
        echo '</div>';
        echo '<div class="dashboard-courses">';
        foreach ($user_courses as $course_id) {
            $course = get_post($course_id);
            if (!$course) continue;
            $progress = Progress_Tracker::getCourseProgress($user_id, $course_id);
            $modules = Cache_Handler::getCourseModules($course_id);
            $total_lessons = 0; foreach ($modules as $m) { $total_lessons += count(Cache_Handler::getModuleLessons($m->ID)); }
            echo '<div class="course-item" style="Padding:20px;background:#fff;border-radius:6px;margin-bottom:15px">';
            echo '<div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:15px">';
            echo '<div><h3 style="margin:0 0 5px"><a href="'.esc_url(get_permalink($course)).'" style="color:#333;text-decoration:none">'.esc_html($course->post_title).'</a></h3>';
            echo '<div style="font-size:0.9em;color:#666">'.count($modules).' modułów • '.$total_lessons.' lessons</div></div>';
            echo '<div style="font-size:24px;font-weight:700;color:#2196F3">'.$progress.'%</div></div>';
            echo '<div class="progress-bar" style="height:10px;background:#eee;border-radius:5px;overflow:hidden"><div style="width:'.$progress.'%;height:100%;background:#4CAF50;transition:width 0.3s"></div></div>';
            echo '</div>';
        }
        echo '</div></div>';
    }
}
