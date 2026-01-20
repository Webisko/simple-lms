<?php
namespace SimpleLMS\Elementor\Widgets;

use SimpleLMS\Access_Control;
use SimpleLMS\Progress_Tracker;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) { exit; }

class User_Courses_Grid_Widget extends Widget_Base {
    public function get_name(): string { return 'simple_lms_user_courses_grid'; }
    public function get_title(): string { return __('User Courses Grid', 'simple-lms'); }
    public function get_icon(): string { return 'eicon-gallery-grid'; }
    public function get_categories(): array { return ['simple-lms']; }

    protected function register_controls(): void {
        $this->start_controls_section('content', ['label' => __('Settings', 'simple-lms'), 'tab' => Controls_Manager::TAB_CONTENT]);
        $this->add_responsive_control('columns', [
            'label' => __('Columns', 'simple-lms'),
            'type' => Controls_Manager::SELECT,
            'default' => '3',
            'options' => ['1'=>'1','2'=>'2','3'=>'3','4'=>'4']
        ]);
        $this->add_control('show_progress', ['label'=>__('Show progress','simple-lms'),'type'=>Controls_Manager::SWITCHER,'default'=>'yes','return_value'=>'yes']);
        $this->add_control('show_continue', ['label'=>__('Show Continue button','simple-lms'),'type'=>Controls_Manager::SWITCHER,'default'=>'yes','return_value'=>'yes']);
        $this->end_controls_section();

        $this->start_controls_section('card_style', ['label'=>__('Card','simple-lms'),'tab'=>Controls_Manager::TAB_STYLE]);
        $this->add_responsive_control('card_gap', ['label'=>__('Spacing','simple-lms'),'type'=>Controls_Manager::SLIDER,'size_units'=>['px','em','rem'],'selectors'=>['{{WRAPPER}} .user-courses-grid'=>'gap: {{SIZE}}{{UNIT}};']]);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), ['name'=>'card_shadow','selector'=>'{{WRAPPER}} .course-card']);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name'=>'card_border','selector'=>'{{WRAPPER}} .course-card']);
        $this->add_control('card_radius',['label'=>__('Border radius','simple-lms'),'type'=>Controls_Manager::DIMENSIONS,'size_units'=>['px','%','em','rem'],'selectors'=>['{{WRAPPER}} .course-card'=>'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->end_controls_section();
    }

    protected function render(): void {
        if (!is_user_logged_in()) { echo '<div class="elementor-alert elementor-alert-info">'.esc_html__('Log in to see your courses.','simple-lms').'</div>'; return; }
        $settings = $this->get_settings_for_display();
        $user_id = get_current_user_id();
        $courses = Access_Control::getUserCourses($user_id);
        if (empty($courses)) { echo '<div class="elementor-alert elementor-alert-info">'.esc_html__('You have no courses.','simple-lms').'</div>'; return; }
        $cols = $settings['columns'] ?? '3';
        echo '<div class="user-courses-grid columns-'.esc_attr($cols).'" style="display:grid">';
        foreach ($courses as $course) {
            $thumb = get_the_post_thumbnail_url($course->ID, 'medium') ?: ''; 
            $progress = Progress_Tracker::getCourseProgress($user_id, $course->ID);
            echo '<div class="course-card" style="display:flex; flex-direction:column;">';
            if ($thumb) echo '<div class="course-thumb" style="background-image:url('.esc_url($thumb).');background-size:cover;background-position:center;height:160px"></div>';
            echo '<div class="course-body" style="Padding:12px">';
            echo '<div class="course-title" style="font-weight:600; margin-bottom:8px">'.esc_html(get_the_title($course->ID)).'</div>';
            if ($settings['show_progress']==='yes') {
                echo '<div class="course-progress" style="height:6px;background:#eee;border-radius:4px;overflow:hidden;margin-bottom:10px">';
                echo '<div style="height:100%; width:'.esc_attr($progress).'% ; background:#4CAF50"></div>';
                echo '</div>';
            }
            if ($settings['show_continue']==='yes') {
                $continue_lesson_id = $this->get_continue_lesson($user_id, $course->ID);
                if ($continue_lesson_id && get_post_type($continue_lesson_id) === 'lesson') {
                    $continue_url = get_permalink($continue_lesson_id);
                    echo '<a class="continue-btn" href="'.esc_url($continue_url).'" style="display:inline-block;Padding:10px 16px;background:#2196F3;color:#fff;text-decoration:none;border-radius:4px">'.esc_html__('Continue','simple-lms').'</a>';
                } else {
                    echo '<span class="continue-btn disabled" style="display:inline-block;Padding:10px 16px;background:#9e9e9e;color:#fff;border-radius:4px">'.esc_html__('No lessons to continue','simple-lms').'</span>';
                }
            }
            echo '</div></div>';
        }
        echo '</div>';
        echo '<style>.user-courses-grid.columns-1{grid-template-columns:1fr}.user-courses-grid.columns-2{grid-template-columns:repeat(2,1fr)}.user-courses-grid.columns-3{grid-template-columns:repeat(3,1fr)}.user-courses-grid.columns-4{grid-template-columns:repeat(4,1fr)}@media(max-width:768px){.user-courses-grid{grid-template-columns:1fr}}</style>';
    }

    private function get_continue_lesson($user_id, $course_id): int {
        $last = Progress_Tracker::getLastViewedLesson($user_id, $course_id);
        if ($last && !Progress_Tracker::isLessonCompleted($user_id, $last)) return $last;
        // first incomplete
        $modules = \SimpleLMS\Cache_Handler::getCourseModules($course_id);
        foreach ($modules as $m) {
            $lessons = \SimpleLMS\Cache_Handler::getModuleLessons($m->ID);
            foreach ($lessons as $l) { if (!Progress_Tracker::isLessonCompleted($user_id, $l->ID)) return $l->ID; }
        }
        // fallback: first lesson
        foreach ($modules as $m) { $lessons = \SimpleLMS\Cache_Handler::getModuleLessons($m->ID); if (!empty($lessons)) return $lessons[0]->ID; }
        // no lesson available
        return 0;
    }
}
