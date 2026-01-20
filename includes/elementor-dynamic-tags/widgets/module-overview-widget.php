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

class Module_Overview_Widget extends Widget_Base {
    public function get_name(): string { return 'simple_lms_module_overview'; }
    public function get_title(): string { return __('Module Overview', 'simple-lms'); }
    public function get_icon(): string { return 'eicon-nav-menu'; }
    public function get_categories(): array { return ['simple-lms']; }

    protected function register_controls(): void {
        $this->start_controls_section('content', ['label'=>__('Settings','simple-lms'),'tab'=>Controls_Manager::TAB_CONTENT]);
        $this->add_control('module_id',['label'=>__('Module ID','simple-lms'),'type'=>Controls_Manager::NUMBER,'default'=>0,'description'=>__('Leave 0 to detect automatically','simple-lms')]);
        $this->add_control('display_mode',['label'=>__('Display mode','simple-lms'),'type'=>Controls_Manager::SELECT,'default'=>'list','options'=>[
            'list'=>__('List','simple-lms'),
            'grid'=>__('Grid','simple-lms'),
        ]]);
        $this->add_responsive_control('columns', ['label'=>__('Columns (grid)','simple-lms'),'type'=>Controls_Manager::SELECT,'default'=>'2','options'=>['1'=>'1','2'=>'2','3'=>'3','4'=>'4'],'condition'=>['display_mode'=>'grid']]);
        $this->end_controls_section();

        // Module styles
        $this->start_controls_section('module_style', ['label'=>__('Module header','simple-lms'),'tab'=>Controls_Manager::TAB_STYLE]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name'=>'module_title_typography','selector'=>'{{WRAPPER}} .simple-lms-module-overview .module-title']);
        $this->add_control('module_title_color', ['label'=>__('Title color','simple-lms'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .simple-lms-module-overview .module-title'=>'color: {{VALUE}};']]);
        $this->add_control('module_background', ['label'=>__('Module background','simple-lms'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .simple-lms-module-overview .module-item'=>'background: {{VALUE}};']]);
        $this->add_control('module_border_color', ['label'=>__('Module border','simple-lms'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .simple-lms-module-overview .module-item'=>'border-color: {{VALUE}};']]);
        $this->end_controls_section();

        // Lesson styles
        $this->start_controls_section('lesson_style', ['label'=>__('Lessons','simple-lms'),'tab'=>Controls_Manager::TAB_STYLE]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name'=>'lesson_text_typography','selector'=>'{{WRAPPER}} .simple-lms-module-overview .lesson-title']);
        $this->add_responsive_control('lesson_spacing', ['label'=>__('Lesson spacing','simple-lms'),'type'=>Controls_Manager::SLIDER,'size_units'=>['px','em','rem'],'selectors'=>['{{WRAPPER}} .simple-lms-module-overview .lesson-item'=>'margin-bottom: {{SIZE}}{{UNIT}};']]);
        $this->add_control('indicator_size', ['label'=>__('Indicator size','simple-lms'),'type'=>Controls_Manager::SLIDER,'size_units'=>['px','em','rem'],'range'=>['px'=>['min'=>8,'max'=>32,'step'=>1]],'selectors'=>[
            '{{WRAPPER}} .simple-lms-module-overview .lesson-icon'=>'font-size: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
        ]]);
        $this->add_control('completed_icon', ['label'=>__('Completed icon','simple-lms'),'type'=>Controls_Manager::ICONS,'default'=>['value'=>'eicon-check','library'=>'elementor']]);
        $this->add_control('completed_icon_color', ['label'=>__('Completed icon color','simple-lms'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .simple-lms-module-overview .lesson-item.completed .lesson-icon'=>'color: {{VALUE}};']]);
        $this->add_control('completed_text_color', ['label'=>__('Completed text color','simple-lms'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .simple-lms-module-overview .lesson-item.completed .lesson-title'=>'color: {{VALUE}};']]);
        $this->add_control('completed_background', ['label'=>__('Completed background','simple-lms'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .simple-lms-module-overview .lesson-item.completed'=>'background: {{VALUE}};']]);
        $this->add_control('incomplete_icon', ['label'=>__('Incomplete icon','simple-lms'),'type'=>Controls_Manager::ICONS,'default'=>['value'=>'eicon-circle-o','library'=>'elementor']]);
        $this->add_control('incomplete_icon_color', ['label'=>__('Incomplete icon color','simple-lms'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .simple-lms-module-overview .lesson-item.incomplete .lesson-icon'=>'color: {{VALUE}};']]);
        $this->add_control('incomplete_text_color', ['label'=>__('Incomplete text color','simple-lms'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .simple-lms-module-overview .lesson-item.incomplete .lesson-title'=>'color: {{VALUE}};']]);
        $this->add_control('incomplete_background', ['label'=>__('Incomplete background','simple-lms'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .simple-lms-module-overview .lesson-item.incomplete'=>'background: {{VALUE}};']]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $module_id = !empty($settings['module_id']) ? absint($settings['module_id']) : $this->get_current_module_id();

        if (!$module_id) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="elementor-alert elementor-alert-warning">'.esc_html__('Module not detected.','simple-lms').'</div>';
            }
            return;
        }

        $lessons = Cache_Handler::getModuleLessons($module_id);
        if (empty($lessons)) {
            echo '<div class="elementor-alert elementor-alert-info">'.esc_html__('No lessons in this module.','simple-lms').'</div>';
            return;
        }

        $display_mode = !empty($settings['display_mode']) ? $settings['display_mode'] : 'list';
        $columns = !empty($settings['columns']) ? $settings['columns'] : '2';
        $current_user_id = get_current_user_id();

        $completed_icon_html = Icons_Manager::render_icon($settings['completed_icon'], ['aria-hidden'=>'true', 'class'=>'completion-status completed'], 'span');
        $incomplete_icon_html = Icons_Manager::render_icon($settings['incomplete_icon'], ['aria-hidden'=>'true', 'class'=>'completion-status incomplete'], 'span');

        $container_classes = ['simple-lms-module-overview', 'mode-'.$display_mode];
        if ($display_mode === 'grid') {
            $container_classes[] = 'columns-'.$columns;
        }

        echo '<div class="'.esc_attr(implode(' ', $container_classes)).'">';
        echo '<div class="simple-lms-accordion-item">';
        echo '<div class="accordion-header"><h3 class="module-title">'.esc_html(get_the_title($module_id)).'</h3></div>';
        echo '<div class="accordion-content">';

        if (!empty($lessons)) {
            echo '<ul class="lessons-list">';
            foreach ($lessons as $lesson) {
                $is_completed = Progress_Tracker::isLessonCompleted($current_user_id, $lesson->ID);
                $lesson_link = get_permalink($lesson->ID);
                $lesson_classes = $is_completed ? 'lesson-item completed-lesson' : 'lesson-item';

                echo '<li class="'.esc_attr($lesson_classes).'" data-lesson-id="'.esc_attr($lesson->ID).'">';
                echo '<a href="'.esc_url($lesson_link).'" class="lesson-link">';
                echo $is_completed ? $completed_icon_html : $incomplete_icon_html;
                echo '<span class="lesson-title">'.esc_html(get_the_title($lesson->ID)).'</span>';
                echo '</a>';
                echo '</li>';
            }
            echo '</ul>';
        }

        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo $this->get_styles($display_mode, $columns);
    }

    private function get_current_module_id(): int {
        $current_id = get_the_ID();
        if (get_post_type($current_id) === 'lesson') {
            return (int) get_post_meta($current_id, 'lesson_module', true);
        }
        if (get_post_type($current_id) === 'module') {
            return $current_id;
        }
        return 0;
    }

    private function get_styles($display_mode, $columns): string {
        $grid_styles = '';
        if ($display_mode === 'grid') {
            $grid_styles = '
.simple-lms-module-overview.mode-grid{display:grid;gap:16px}
.simple-lms-module-overview.mode-grid.columns-1{grid-template-columns:1fr}
.simple-lms-module-overview.mode-grid.columns-2{grid-template-columns:repeat(2,1fr)}
.simple-lms-module-overview.mode-grid.columns-3{grid-template-columns:repeat(3,1fr)}
.simple-lms-module-overview.mode-grid.columns-4{grid-template-columns:repeat(4,1fr)}
@media(max-width:768px){.simple-lms-module-overview.mode-grid{grid-template-columns:1fr}}
';
        }

        return '<style>
.simple-lms-module-overview{display:flex;flex-direction:column;gap:16px}
'.$grid_styles.'
.simple-lms-module-overview .simple-lms-accordion-item{border:1px solid #e5e5e5;border-radius:8px;overflow:hidden}
.simple-lms-module-overview .accordion-header{background-color:#f5f5f5;padding:15px 20px;display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
.simple-lms-module-overview .module-title{margin:0;font-weight:600}
.simple-lms-module-overview .accordion-content{background-color:#ffffff;padding:15px 20px}
.simple-lms-module-overview .lessons-list{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:10px}
.simple-lms-module-overview .lesson-item{display:flex;align-items:center;gap:10px;padding:10px;border-radius:6px;transition:background-color 0.2s}
.simple-lms-module-overview .lesson-item.completed-lesson{background-color:#edf7ed}
.simple-lms-module-overview .lesson-item .lesson-link{display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit;width:100%}
.simple-lms-module-overview .completion-status{display:inline-flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
.simple-lms-module-overview .lesson-title{word-break:break-word}
</style>';
    }
}
