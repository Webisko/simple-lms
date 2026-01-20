<?php
namespace SimpleLMS\Bricks\Elements;

if (!defined('ABSPATH')) exit;

class Lesson_Attachments extends \Bricks\Element {
    public $category = 'simple-lms';
    public $name = 'simple-lms-lesson-attachments';
    public $icon = 'ti-clip';

    public function get_label() {
        return esc_html__('Lesson Attachments', 'simple-lms');
    }

    public function set_controls() {
        $this->controls['lessonId'] = ['tab'=>'content','label'=>esc_html__('Lesson ID','simple-lms'),'type'=>'number','default'=>0];
        $this->controls['layout'] = ['tab'=>'content','label'=>esc_html__('Layout','simple-lms'),'type'=>'select','options'=>['list'=>'List','grid'=>'Grid'],'default'=>'list'];
    }

    public function render() {
        $settings = $this->settings;
        $lesson_id = !empty($settings['lessonId']) ? absint($settings['lessonId']) : get_the_ID();
        $attachments = get_post_meta($lesson_id, 'lesson_attachments', true);
        if (empty($attachments) || !is_array($attachments)) return;
        $layout = $settings['layout'] ?? 'list';
        echo '<div class="simple-lms-attachments layout-'.$layout.'" style="display:'.($layout==='grid'?'grid':'flex').';flex-direction:column;gap:10px">';
        foreach ($attachments as $att_id) {
            $url = wp_get_attachment_url($att_id);
            $name = basename(get_attached_file($att_id));
            $ext = strtoupper(pathinfo($name, PATHINFO_EXTENSION));
            echo '<div class="attachment-item" style="display:flex;align-items:center;gap:10px;Padding:10px;border:1px solid #eee;border-radius:4px">';
            echo '<span class="file-icon" style="Padding:4px 8px;background:#2196F3;color:#fff;border-radius:3px;font-size:0.8em">'.esc_html($ext).'</span>';
            echo '<span class="file-name" style="flex:1">'.esc_html($name).'</span>';
            echo '<a href="'.esc_url($url).'" download style="Padding:6px 12px;background:#4CAF50;color:#fff;text-decoration:none;border-radius:4px">Pobierz</a>';
            echo '</div>';
        }
        echo '</div>';
    }
}
