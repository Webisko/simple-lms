<?php
namespace SimpleLMS\Elementor\Widgets;

use SimpleLMS\Access_Control;
use SimpleLMS\Progress_Tracker;
use SimpleLMS\Cache_Handler;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

if (!defined('ABSPATH')) { exit; }

class User_Progress_Dashboard_Widget extends Widget_Base {
    public function get_name(): string { return 'simple_lms_user_progress_dashboard'; }
    public function get_title(): string { return __('User Progress Dashboard', 'simple-lms'); }
    public function get_icon(): string { return 'eicon-dashboard'; }
    public function get_categories(): array { return ['simple-lms']; }

    protected function register_controls(): void {
        $this->start_controls_section('content', ['label'=>__('Settings','simple-lms'),'tab'=>Controls_Manager::TAB_CONTENT]);
        $this->add_responsive_control('columns', ['label'=>__('Columns','simple-lms'),'type'=>Controls_Manager::SELECT,'default'=>'2','options'=>['1'=>'1','2'=>'2','3'=>'3']]);
        $this->add_control('show_streak',['label'=>__('Show streak','simple-lms'),'type'=>Controls_Manager::SWITCHER,'default'=>'yes','return_value'=>'yes']);
        $this->add_control('show_last_activity',['label'=>__('Show last activity','simple-lms'),'type'=>Controls_Manager::SWITCHER,'default'=>'yes','return_value'=>'yes']);
        $this->end_controls_section();

        $this->start_controls_section('style', ['label'=>__('Styles','simple-lms'),'tab'=>Controls_Manager::TAB_STYLE]);
        $this->add_responsive_control('grid_gap', ['label'=>__('Grid gap','simple-lms'),'type'=>Controls_Manager::SLIDER,'size_units'=>['px','em','rem'],'selectors'=>['{{WRAPPER}} .simple-lms-user-dashboard'=>'gap: {{SIZE}}{{UNIT}};']]);
        $this->add_control('card_background', ['label'=>__('Card background','simple-lms'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .simple-lms-user-dashboard .course-progress-card'=>'background: {{VALUE}};']]);
        $this->add_control('card_border', ['label'=>__('Card border color','simple-lms'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .simple-lms-user-dashboard .course-progress-card'=>'border-color: {{VALUE}};']]);
        $this->add_responsive_control('card_radius', ['label'=>__('Card radius','simple-lms'),'type'=>Controls_Manager::SLIDER,'size_units'=>['px'],'range'=>['px'=>['min'=>0,'max'=>24,'step'=>1]],'selectors'=>['{{WRAPPER}} .simple-lms-user-dashboard .course-progress-card'=>'border-radius: {{SIZE}}{{UNIT}};']]);
        $this->add_responsive_control('card_padding', ['label'=>__('Card padding','simple-lms'),'type'=>Controls_Manager::DIMENSIONS,'size_units'=>['px','em','rem'],'selectors'=>['{{WRAPPER}} .simple-lms-user-dashboard .card-body'=>'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name'=>'title_typography','selector'=>'{{WRAPPER}} .simple-lms-user-dashboard .card-title']);
        $this->add_control('title_color', ['label'=>__('Title color','simple-lms'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .simple-lms-user-dashboard .card-title'=>'color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name'=>'text_typography','selector'=>'{{WRAPPER}} .simple-lms-user-dashboard .card-meta, {{WRAPPER}} .simple-lms-user-dashboard .card-last-activity']);
        $this->add_control('text_color', ['label'=>__('Text color','simple-lms'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .simple-lms-user-dashboard .card-meta, {{WRAPPER}} .simple-lms-user-dashboard .card-last-activity'=>'color: {{VALUE}};']]);
        $this->add_responsive_control('bar_height', ['label'=>__('Progress bar height','simple-lms'),'type'=>Controls_Manager::SLIDER,'size_units'=>['px'],'range'=>['px'=>['min'=>4,'max'=>24,'step'=>1]],'selectors'=>['{{WRAPPER}} .simple-lms-user-dashboard .card-progress'=>'height: {{SIZE}}{{UNIT}};']]);
        $this->add_control('bar_background', ['label'=>__('Progress bar background','simple-lms'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .simple-lms-user-dashboard .card-progress'=>'background: {{VALUE}};']]);
        $this->add_control('bar_fill', ['label'=>__('Progress bar fill','simple-lms'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .simple-lms-user-dashboard .card-progress .progress-fill'=>'background: {{VALUE}};']]);
        $this->add_control('button_background', ['label'=>__('Button background','simple-lms'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .simple-lms-user-dashboard .btn-continue'=>'background: {{VALUE}};']]);
        $this->add_control('button_text', ['label'=>__('Button text color','simple-lms'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .simple-lms-user-dashboard .btn-continue'=>'color: {{VALUE}};']]);
        $this->end_controls_section();
    }

    protected function render(): void {
        if (!is_user_logged_in()) { echo '<div class="elementor-alert elementor-alert-info">'.esc_html__('Log in to see progress.','simple-lms').'</div>'; return; }
        $settings = $this->get_settings_for_display();
        $user_id = get_current_user_id();
        $courses = Access_Control::getUserCourses($user_id);
        if (empty($courses)) { echo '<div class="elementor-alert elementor-alert-info">'.esc_html__('No courses in account.','simple-lms').'</div>'; return; }
        $cols = $settings['columns'] ?? '2';
        echo '<div class="simple-lms-user-dashboard columns-'.esc_attr($cols).'">';
        foreach ($courses as $course) {
            $progress = Progress_Tracker::getCourseProgress($user_id, $course->ID);
            $completed_lessons = Progress_Tracker::getCompletedLessonsCount($user_id, $course->ID);
            // Use unified API for total lessons
            $total_lessons = Progress_Tracker::getTotalLessonsCount($course->ID);
            $last_lesson = Progress_Tracker::getLastViewedLesson($user_id, $course->ID);
            $last_activity = $last_lesson ? get_post_modified_time('U', true, $last_lesson) : 0;
            $last_activity_text = $last_activity ? human_time_diff($last_activity, current_time('timestamp')) . ' ' . __('ago','simple-lms') : __('no data','simple-lms');
            echo '<div class="course-progress-card">';
            $thumb = get_the_post_thumbnail_url($course->ID, 'medium'); if ($thumb) echo '<div class="card-thumb" style="background-image:url('.esc_url($thumb).');"></div>';
            echo '<div class="card-body">';
            echo '<div class="card-title">'.esc_html(get_the_title($course->ID)).'</div>';
            echo '<div class="card-progress"><div class="progress-fill" style="width:'.esc_attr($progress).'%;"></div></div>';
            echo '<div class="card-meta">'.esc_html(sprintf(__('Completed lessons: %d/%d (%d%%)','simple-lms'), $completed_lessons, $total_lessons, (int)$progress)).'</div>';
            if ($settings['show_last_activity']==='yes') { echo '<div class="card-last-activity">'.esc_html(sprintf(__('Last activity: %s','simple-lms'), $last_activity_text)).'</div>'; }
            $continue_url = $last_lesson ? get_permalink($last_lesson) : get_permalink($course->ID);
            echo '<div class="card-actions">';
            echo '<a class="btn-continue" href="'.esc_url($continue_url).'">'.esc_html__('Continue','simple-lms').'</a>';
            echo '</div></div></div>';
        }
        echo '</div>';
        echo '<style>
.simple-lms-user-dashboard{display:grid;gap:16px}
.simple-lms-user-dashboard.columns-1{grid-template-columns:1fr}
.simple-lms-user-dashboard.columns-2{grid-template-columns:repeat(2,1fr)}
.simple-lms-user-dashboard.columns-3{grid-template-columns:repeat(3,1fr)}
@media(max-width:768px){.simple-lms-user-dashboard{grid-template-columns:1fr}}
.simple-lms-user-dashboard .course-progress-card{border:1px solid #eee;border-radius:8px;overflow:hidden;display:flex;flex-direction:column;background:#fff}
.simple-lms-user-dashboard .card-thumb{background-size:cover;background-position:center;height:140px;width:100%}
.simple-lms-user-dashboard .card-body{padding:12px;display:flex;flex-direction:column;gap:8px}
.simple-lms-user-dashboard .card-title{font-weight:600;margin:0}
.simple-lms-user-dashboard .card-progress{height:8px;background:#eee;border-radius:4px;overflow:hidden}
.simple-lms-user-dashboard .card-progress .progress-fill{height:100%;background:#4CAF50;border-radius:4px}
.simple-lms-user-dashboard .card-meta,.simple-lms-user-dashboard .card-last-activity{font-size:0.9em;opacity:0.9}
.simple-lms-user-dashboard .card-actions{margin-top:4px}
.simple-lms-user-dashboard .btn-continue{display:inline-block;padding:10px 16px;background:#2196F3;color:#fff;text-decoration:none;border-radius:4px}
</style>';
    }
}
