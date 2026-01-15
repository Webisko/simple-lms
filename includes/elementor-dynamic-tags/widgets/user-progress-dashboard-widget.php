<?php
namespace SimpleLMS\Elementor\Widgets;

use SimpleLMS\Access_Control;
use SimpleLMS\Progress_Tracker;
use SimpleLMS\Cache_Handler;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) { exit; }

class User_Progress_Dashboard_Widget extends Widget_Base {
    public function get_name(): string { return 'simple_lms_user_progress_dashboard'; }
    public function get_title(): string { return __('User Progress Dashboard', 'simple-lms'); }
    public function get_icon(): string { return 'eicon-dashboard'; }
    public function get_categories(): array { return ['simple-lms']; }

    protected function register_controls(): void {
        $this->start_controls_section('content', ['label'=>__('Settings','simple-lms'),'tab'=>Controls_Manager::TAB_CONTENT]);
        $this->add_responsive_control('columns', ['label'=>__('Kolumny','simple-lms'),'type'=>Controls_Manager::SELECT,'default'=>'2','options'=>['1'=>'1','2'=>'2','3'=>'3']]);
        $this->add_control('show_streak',['label'=>__('Pokaż streak','simple-lms'),'type'=>Controls_Manager::SWITCHER,'default'=>'yes','return_value'=>'yes']);
        $this->add_control('show_last_activity',['label'=>__('Pokaż ostatnią aktywność','simple-lms'),'type'=>Controls_Manager::SWITCHER,'default'=>'yes','return_value'=>'yes']);
        $this->end_controls_section();
    }

    protected function render(): void {
        if (!is_user_logged_in()) { echo '<div class="elementor-alert elementor-alert-info">'.esc_html__('Zaloguj się, aby zobaczyć postępy.','simple-lms').'</div>'; return; }
        $settings = $this->get_settings_for_display();
        $user_id = get_current_user_id();
        $courses = Access_Control::getUserCourses($user_id);
        if (empty($courses)) { echo '<div class="elementor-alert elementor-alert-info">'.esc_html__('No courses in account.','simple-lms').'</div>'; return; }
        $cols = $settings['columns'] ?? '2';
        echo '<div class="simple-lms-user-dashboard columns-'.esc_attr($cols).'" style="display:grid; gap:16px">';
        foreach ($courses as $course) {
            $progress = Progress_Tracker::getCourseProgress($user_id, $course->ID);
            $completed_lessons = Progress_Tracker::getCompletedLessonsCount($user_id, $course->ID);
            // Use unified API for total lessons
            $total_lessons = Progress_Tracker::getTotalLessonsCount($course->ID);
            $last_lesson = Progress_Tracker::getLastViewedLesson($user_id, $course->ID);
            $last_activity = $last_lesson ? get_post_modified_time('U', true, $last_lesson) : 0;
            $last_activity_text = $last_activity ? human_time_diff($last_activity, current_time('timestamp')) . ' ' . __('temu','simple-lms') : __('brak danych','simple-lms');
            echo '<div class="course-progress-card" style="border:1px solid #eee; border-radius:8px; overflow:hidden">';
            $thumb = get_the_post_thumbnail_url($course->ID, 'medium'); if ($thumb) echo '<div class="card-thumb" style="background-image:url('.esc_url($thumb).'); background-size:cover; background-position:center; height:140px"></div>';
            echo '<div class="card-body" style="Padding:12px">';
            echo '<div class="card-title" style="font-weight:600; margin-bottom:8px">'.esc_html(get_the_title($course->ID)).'</div>';
            echo '<div class="card-progress" style="height:8px; background:#eee; border-radius:4px; overflow:hidden; margin-bottom:10px"><div style="height:100%; width:'.esc_attr($progress).'%; background:#4CAF50"></div></div>';
            echo '<div class="card-meta" style="font-size:0.9em; opacity:0.85">'.esc_html(sprintf(__('completed lekcje: %d/%d (%d%%)','simple-lms'), $completed_lessons, $total_lessons, (int)$progress)).'</div>';
            if ($settings['show_last_activity']==='yes') { echo '<div class="card-last-activity" style="margin-top:6px">'.esc_html(sprintf(__('Ostatnia aktywność: %s','simple-lms'), $last_activity_text)).'</div>'; }
            $continue_url = $last_lesson ? get_permalink($last_lesson) : get_permalink($course->ID);
            echo '<div class="card-actions" style="margin-top:10px">';
            echo '<a class="btn-continue" href="'.esc_url($continue_url).'" style="display:inline-block; Padding:10px 16px; background:#2196F3; color:#fff; text-decoration:none; border-radius:4px">'.esc_html__('Continue','simple-lms').'</a>';
            echo '</div></div></div>';
        }
        echo '</div>';
        echo '<style>.simple-lms-user-dashboard.columns-1{grid-template-columns:1fr}.simple-lms-user-dashboard.columns-2{grid-template-columns:repeat(2,1fr)}.simple-lms-user-dashboard.columns-3{grid-template-columns:repeat(3,1fr)}@media(max-width:768px){.simple-lms-user-dashboard{grid-template-columns:1fr}}</style>';
    }
}
