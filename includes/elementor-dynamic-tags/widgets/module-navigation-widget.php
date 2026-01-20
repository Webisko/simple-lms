<?php
namespace SimpleLMS\Elementor\Widgets;

use SimpleLMS\Elementor\Elementor_Dynamic_Tags;
use SimpleLMS\Cache_Handler;
use SimpleLMS\Progress_Tracker;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;

if (!defined('ABSPATH')) { exit; }

class Module_Navigation_Widget extends Widget_Base {
    public function get_name(): string { return 'simple_lms_module_navigation'; }
    public function get_title(): string { return __('Module Navigation', 'simple-lms'); }
    public function get_icon(): string { return 'eicon-nav-menu'; }
    public function get_categories(): array { return ['simple-lms']; }

    protected function register_controls(): void {
        $this->start_controls_section('content', ['label'=>__('Settings','simple-lms'),'tab'=>Controls_Manager::TAB_CONTENT]);
        $this->add_control('course_id',['label'=>__('Course ID','simple-lms'),'type'=>Controls_Manager::NUMBER,'default'=>0,'description'=>__('Leave 0 to detect automatically','simple-lms')]);
        $this->add_control('layout',['label'=>__('Layout','simple-lms'),'type'=>Controls_Manager::SELECT,'default'=>'list','options'=>[
            'list'=>__('List','simple-lms'),
            'grid'=>__('Grid','simple-lms'),
        ]]);
        $this->add_responsive_control('columns', ['label'=>__('Columns (grid)','simple-lms'),'type'=>Controls_Manager::SELECT,'default'=>'2','options'=>['1'=>'1','2'=>'2','3'=>'3','4'=>'4'],'condition'=>['layout'=>'grid']]);
        $this->add_responsive_control('item_gap', ['label'=>__('Items gap','simple-lms'),'type'=>Controls_Manager::SLIDER,'size_units'=>['px','em','rem'],'selectors'=>[
            '{{WRAPPER}} .simple-lms-module-navigation'=>'gap: {{SIZE}}{{UNIT}};',
        ]]);
        $this->end_controls_section();

        // Module styles
        $this->start_controls_section('module_style', ['label'=>__('Module header','simple-lms'),'tab'=>Controls_Manager::TAB_STYLE]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name'=>'module_title_typography','selector'=>'{{WRAPPER}} .simple-lms-module-navigation .module-title']);
        $this->add_control('module_title_color', ['label'=>__('Text color','simple-lms'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .simple-lms-module-navigation .module-title'=>'color: {{VALUE}};']]);
        $this->add_control('module_background', ['label'=>__('Module background','simple-lms'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .simple-lms-module-navigation .module-item'=>'background: {{VALUE}};']]);
        $this->add_control('module_border_color', ['label'=>__('Module border','simple-lms'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .simple-lms-module-navigation .module-item'=>'border-color: {{VALUE}};']]);
        $this->add_responsive_control('module_spacing', ['label'=>__('Module spacing','simple-lms'),'type'=>Controls_Manager::SLIDER,'size_units'=>['px','em','rem'],'selectors'=>['{{WRAPPER}} .simple-lms-module-navigation .module-item'=>'margin-bottom: {{SIZE}}{{UNIT}};']]);
        $this->end_controls_section();

        // Lesson styles
        $this->start_controls_section('lesson_style', ['label'=>__('Lessons','simple-lms'),'tab'=>Controls_Manager::TAB_STYLE]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name'=>'lesson_text_typography','selector'=>'{{WRAPPER}} .simple-lms-module-navigation .lesson-title']);
        $this->add_responsive_control('lesson_spacing', ['label'=>__('Lesson spacing','simple-lms'),'type'=>Controls_Manager::SLIDER,'size_units'=>['px','em','rem'],'selectors'=>['{{WRAPPER}} .simple-lms-module-navigation .lesson-item'=>'margin-bottom: {{SIZE}}{{UNIT}};']]);
        $this->add_control('indicator_size', ['label'=>__('Indicator size','simple-lms'),'type'=>Controls_Manager::SLIDER,'size_units'=>['px','em','rem'],'range'=>['px'=>['min'=>8,'max'=>32,'step'=>1]],'selectors'=>[
            '{{WRAPPER}} .simple-lms-module-navigation .lesson-icon'=>'font-size: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
        ]]);
        $this->add_control('completed_icon', ['label'=>__('Completed icon','simple-lms'),'type'=>Controls_Manager::ICONS,'default'=>['value'=>'eicon-check','library'=>'elementor']]);
        $this->add_control('completed_icon_color', ['label'=>__('Completed icon color','simple-lms'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .simple-lms-module-navigation .lesson-item.completed .lesson-icon'=>'color: {{VALUE}};']]);
        $this->add_control('completed_text_color', ['label'=>__('Completed text color','simple-lms'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .simple-lms-module-navigation .lesson-item.completed .lesson-title'=>'color: {{VALUE}};']]);
        $this->add_control('completed_background', ['label'=>__('Completed background','simple-lms'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .simple-lms-module-navigation .lesson-item.completed'=>'background: {{VALUE}};']]);
        $this->add_control('incomplete_icon', ['label'=>__('Incomplete icon','simple-lms'),'type'=>Controls_Manager::ICONS,'default'=>['value'=>'eicon-circle-o','library'=>'elementor']]);
        $this->add_control('incomplete_icon_color', ['label'=>__('Incomplete icon color','simple-lms'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .simple-lms-module-navigation .lesson-item.incomplete .lesson-icon'=>'color: {{VALUE}};']]);
        $this->add_control('incomplete_text_color', ['label'=>__('Incomplete text color','simple-lms'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .simple-lms-module-navigation .lesson-item.incomplete .lesson-title'=>'color: {{VALUE}};']]);
        $this->add_control('incomplete_background', ['label'=>__('Incomplete background','simple-lms'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .simple-lms-module-navigation .lesson-item.incomplete'=>'background: {{VALUE}};']]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $course_id = !empty($settings['course_id']) ? absint($settings['course_id']) : Elementor_Dynamic_Tags::get_current_course_id();

        if (!$course_id) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="elementor-alert elementor-alert-warning">'.esc_html__('Course not detected.','simple-lms').'</div>';
            }
            return;
        }

        $modules = Cache_Handler::getCourseModules($course_id);
        if (empty($modules)) {
            echo '<div class="elementor-alert elementor-alert-info">'.esc_html__('No modules in course.','simple-lms').'</div>';
            return;
        }

        $current_user_id = get_current_user_id();

        $layout = !empty($settings['layout']) ? $settings['layout'] : 'list';
        $columns = !empty($settings['columns']) ? $settings['columns'] : '2';
        $container_classes = ['simple-lms-module-navigation', 'layout-'.$layout, 'columns-'.$columns];

        $completed_icon_html = Icons_Manager::render_icon($settings['completed_icon'], ['aria-hidden'=>'true', 'class'=>'lesson-icon'], 'span');
        $incomplete_icon_html = Icons_Manager::render_icon($settings['incomplete_icon'], ['aria-hidden'=>'true', 'class'=>'lesson-icon'], 'span');

        echo '<div class="'.esc_attr(implode(' ', $container_classes)).'">';
        foreach ($modules as $module) {
            $lessons = Cache_Handler::getModuleLessons($module->ID);
            echo '<div class="module-item">';
            echo '<div class="module-title">'.esc_html(get_the_title($module->ID)).'</div>';

            if (!empty($lessons)) {
                echo '<ul class="lessons">';
                foreach ($lessons as $lesson) {
                    $is_completed = Progress_Tracker::isLessonCompleted($current_user_id, $lesson->ID);
                    $lesson_link = get_permalink($lesson->ID);
                    $lesson_classes = $is_completed ? 'lesson-item completed' : 'lesson-item incomplete';

                    echo '<li class="'.esc_attr($lesson_classes).'">';
                    echo $is_completed ? $completed_icon_html : $incomplete_icon_html;
                    echo '<a class="lesson-title" href="'.esc_url($lesson_link).'">'.esc_html(get_the_title($lesson->ID)).'</a>';
                    echo '</li>';
                }
                echo '</ul>';
            } else {
                echo '<div class="lessons-empty">'.esc_html__('No lessons in this module.','simple-lms').'</div>';
            }

            echo '</div>';
        }
        echo '</div>';
        echo '<style>
.simple-lms-module-navigation{display:flex;flex-direction:column;gap:16px}
.simple-lms-module-navigation.layout-grid{display:grid}
.simple-lms-module-navigation.layout-grid.columns-1{grid-template-columns:1fr}
.simple-lms-module-navigation.layout-grid.columns-2{grid-template-columns:repeat(2,1fr)}
.simple-lms-module-navigation.layout-grid.columns-3{grid-template-columns:repeat(3,1fr)}
.simple-lms-module-navigation.layout-grid.columns-4{grid-template-columns:repeat(4,1fr)}
@media(max-width:768px){.simple-lms-module-navigation.layout-grid{grid-template-columns:1fr}}
.simple-lms-module-navigation .module-item{padding:14px;border:1px solid #e5e5e5;border-radius:8px;display:flex;flex-direction:column;gap:10px}
.simple-lms-module-navigation .lessons{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px}
.simple-lms-module-navigation .lesson-item{display:flex;align-items:center;gap:8px;padding:8px;border-radius:6px;transition:background-color 0.2s ease}
.simple-lms-module-navigation .lesson-icon{display:inline-flex;align-items:center;justify-content:center;font-size:14px;color:#666}
.simple-lms-module-navigation .lesson-title{text-decoration:none;color:inherit}
.simple-lms-module-navigation .lesson-item.incomplete{background:#f7f7f7}
.simple-lms-module-navigation .lesson-item.completed{background:#edf7ed}
.simple-lms-module-navigation .lesson-item.completed .lesson-title{color:#2e7d32}
.simple-lms-module-navigation .lessons-empty{font-size:0.9em;opacity:0.75;margin-top:8px}
</style>';
    }
}
