<?php
namespace SimpleLMS\Elementor\Widgets;

use SimpleLMS\Elementor\Elementor_Dynamic_Tags;
use SimpleLMS\Cache_Handler;
use SimpleLMS\Progress_Tracker;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) { exit; }

class Lesson_Progress_Indicator_Widget extends Widget_Base {
    public function get_name(): string { return 'simple_lms_lesson_progress_indicator'; }
    public function get_title(): string { return __('Lesson Progress Indicator', 'simple-lms'); }
    public function get_icon(): string { return 'eicon-skill-bar'; }
    public function get_categories(): array { return ['simple-lms']; }

    protected function register_controls(): void {
        $this->start_controls_section('content', ['label'=>__('Settings','simple-lms'),'tab'=>Controls_Manager::TAB_CONTENT]);
        $this->add_control('lesson_id',['label'=>__('ID lekcji','simple-lms'),'type'=>Controls_Manager::NUMBER,'default'=>0,'description'=>__('Zostaw 0 aby wykryć automatycznie','simple-lms')]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $lesson_id = !empty($settings['lesson_id']) ? absint($settings['lesson_id']) : \SimpleLMS\Elementor\Elementor_Dynamic_Tags::get_current_lesson_id();
        if (!$lesson_id) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="elementor-alert elementor-alert-warning">'.esc_html__('Nie wykryto lekcji.','simple-lms').'</div>';
            }
            return;
        }
        if (get_post_type($lesson_id) !== 'lesson') {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="elementor-alert elementor-alert-warning">'.esc_html__('Podany ID nie jest lekcją.','simple-lms').'</div>';
            }
            return;
        }
        $module_id = (int) get_post_meta($lesson_id, 'lesson_module', true);
        if (!$module_id) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="elementor-alert elementor-alert-info">'.esc_html__('Lekcja nie należy do żadnego MODULE.','simple-lms').'</div>';
            }
            return;
        }
        $lessons = Cache_Handler::getModuleLessons($module_id);
        if (empty($lessons)) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="elementor-alert elementor-alert-info">'.esc_html__('Brak lekcji w tym module.','simple-lms').'</div>';
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
        echo '<div class="lesson-position" style="margin-bottom:8px">'.esc_html(sprintf(__('Lekcja %d z %d','simple-lms'), $position, $total)).'</div>';
        echo '<div class="module-progress-bar" style="height:8px;background:#eee;border-radius:4px;overflow:hidden">';
        echo '<div style="height:100%; width:'.esc_attr($percent).'%; background:#4CAF50"></div>';
        echo '</div>';
        echo '</div>';
    }
}
