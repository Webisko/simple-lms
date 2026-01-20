<?php
namespace SimpleLMS\Elementor\Widgets;

use SimpleLMS\Elementor\Elementor_Dynamic_Tags;
use SimpleLMS\Cache_Handler;
use SimpleLMS\Progress_Tracker;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

if (!defined('ABSPATH')) { exit; }

class Lesson_Progress_Indicator_Widget extends Widget_Base {
    public function get_name(): string { return 'simple_lms_lesson_progress_indicator'; }
    public function get_title(): string { return __('Lesson Progress Indicator', 'simple-lms'); }
    public function get_icon(): string { return 'eicon-skill-bar'; }
    public function get_categories(): array { return ['simple-lms']; }

    protected function register_controls(): void {
        $this->start_controls_section('content', ['label'=>__('Settings','simple-lms'),'tab'=>Controls_Manager::TAB_CONTENT]);
        $this->add_control('lesson_id',['label'=>__('Lesson ID','simple-lms'),'type'=>Controls_Manager::NUMBER,'default'=>0,'description'=>__('Leave 0 to detect automatically','simple-lms')]);
        $this->end_controls_section();

        $this->start_controls_section('style_section', ['label'=>__('Styles','simple-lms'),'tab'=>Controls_Manager::TAB_STYLE]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name'=>'label_typography','selector'=>'{{WRAPPER}} .simple-lms-lesson-progress-indicator .lesson-position']);
        $this->add_control('label_color', ['label'=>__('Label color','simple-lms'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .simple-lms-lesson-progress-indicator .lesson-position'=>'color: {{VALUE}};']]);
        $this->add_responsive_control('bar_height', ['label'=>__('Bar height','simple-lms'),'type'=>Controls_Manager::SLIDER,'size_units'=>['px'],'range'=>['px'=>['min'=>4,'max'=>24,'step'=>1]],'selectors'=>['{{WRAPPER}} .simple-lms-lesson-progress-indicator .module-progress-bar'=>'height: {{SIZE}}{{UNIT}};']]);
        $this->add_control('bar_background', ['label'=>__('Bar background','simple-lms'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .simple-lms-lesson-progress-indicator .module-progress-bar'=>'background: {{VALUE}};']]);
        $this->add_control('bar_fill', ['label'=>__('Bar fill','simple-lms'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .simple-lms-lesson-progress-indicator .module-progress-bar .bar-fill'=>'background: {{VALUE}};']]);
        $this->add_responsive_control('bar_radius', ['label'=>__('Bar radius','simple-lms'),'type'=>Controls_Manager::SLIDER,'size_units'=>['px'],'range'=>['px'=>['min'=>0,'max'=>20,'step'=>1]],'selectors'=>[
            '{{WRAPPER}} .simple-lms-lesson-progress-indicator .module-progress-bar'=>'border-radius: {{SIZE}}{{UNIT}};',
            '{{WRAPPER}} .simple-lms-lesson-progress-indicator .module-progress-bar .bar-fill'=>'border-radius: {{SIZE}}{{UNIT}};',
        ]]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $lesson_id = !empty($settings['lesson_id']) ? absint($settings['lesson_id']) : Elementor_Dynamic_Tags::get_current_lesson_id();
        if (!$lesson_id) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="elementor-alert elementor-alert-warning">'.esc_html__('Cannot detect lesson.','simple-lms').'</div>';
            }
            return;
        }
        if (get_post_type($lesson_id) !== 'lesson') {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="elementor-alert elementor-alert-warning">'.esc_html__('Given ID is not a lesson.','simple-lms').'</div>';
            }
            return;
        }
        $module_id = (int) get_post_meta($lesson_id, 'lesson_module', true);
        if (!$module_id) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="elementor-alert elementor-alert-info">'.esc_html__('Lesson does not belong to any module.','simple-lms').'</div>';
            }
            return;
        }
        $lessons = Cache_Handler::getModuleLessons($module_id);
        if (empty($lessons)) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="elementor-alert elementor-alert-info">'.esc_html__('No lessons in this module.','simple-lms').'</div>';
            }
            return;
        }
        $index = 0; $position = 0; $total = count($lessons);
        foreach ($lessons as $l) { $index++; if ($l->ID === $lesson_id) { $position = $index; break; } }
        $user_id = is_user_logged_in() ? get_current_user_id() : 0;
        $completed = 0;
        if ($user_id) { foreach ($lessons as $l) { if (Progress_Tracker::isLessonCompleted($user_id, $l->ID)) { $completed++; } } }
        $percent = $total>0 ? round(($completed/$total)*100) : 0;
        echo '<div class="simple-lms-lesson-progress-indicator">';
        echo '<div class="lesson-position">'.esc_html(sprintf(__('Lesson %d of %d','simple-lms'), $position, $total)).'</div>';
        echo '<div class="module-progress-bar"><div class="bar-fill" style="width:'.esc_attr($percent).'%;"></div></div>';
        echo '</div>';
        echo '<style>
    .simple-lms-lesson-progress-indicator{display:flex;flex-direction:column;gap:8px}
    .simple-lms-lesson-progress-indicator .lesson-position{font-weight:600}
    .simple-lms-lesson-progress-indicator .module-progress-bar{height:8px;background:#eee;border-radius:4px;overflow:hidden}
    .simple-lms-lesson-progress-indicator .module-progress-bar .bar-fill{height:100%;background:#4CAF50;border-radius:4px}
    </style>';
    }
}
