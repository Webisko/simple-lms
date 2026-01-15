<?php
namespace SimpleLMS\Bricks\Elements;

if (!defined('ABSPATH')) exit;

class Lesson_Video extends \Bricks\Element {
    public $category = 'simple-lms';
    public $name = 'simple-lms-lesson-video';
    public $icon = 'ti-video-camera';

    public function get_label() {
        return esc_html__('Lesson Video', 'simple-lms');
    }

    public function set_controls() {
        $this->controls['lessonId'] = ['tab'=>'content','label'=>esc_html__('Lesson ID','simple-lms'),'type'=>'number','default'=>0];
        $this->controls['aspectRatio'] = ['tab'=>'content','label'=>esc_html__('Aspect Ratio','simple-lms'),'type'=>'select','options'=>['16-9'=>'16:9','4-3'=>'4:3','1-1'=>'1:1','21-9'=>'21:9'],'default'=>'16-9'];
        $this->controls['showPoster'] = ['tab'=>'content','label'=>esc_html__('Show Poster','simple-lms'),'type'=>'checkbox','default'=>true];
    }

    public function render() {
        $settings = $this->settings;
        $lesson_id = !empty($settings['lessonId']) ? absint($settings['lessonId']) : get_the_ID();
        if (function_exists('SimpleLMS\\Compat\\Multilingual_Compat::map_post_id')) {
            $lesson_id = \SimpleLMS\Compat\Multilingual_Compat::map_post_id($lesson_id, 'lesson');
        }
        $video_url = get_post_meta($lesson_id, 'lesson_video_url', true);
        if (!$video_url) return;
        $ratio = $settings['aspectRatio'] ?? '16-9';
        $Padding = ['16-9'=>'56.25%','4-3'=>'75%','1-1'=>'100%','21-9'=>'42.86%'][$ratio] ?? '56.25%';
        echo '<div class="simple-lms-video-wrapper" style="position:relative;Padding-bottom:'.$Padding.';height:0;overflow:hidden">';
        if (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false) {
            $m = [];
            preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/', $video_url, $m);
            $vid = isset($m[1]) ? $m[1] : '';
            if ($vid) {
                echo '<iframe src="https://www.youtube.com/embed/'.$vid.'" style="position:absolute;top:0;left:0;width:100%;height:100%" frameborder="0" allowfullscreen></iframe>';
            }
        } elseif (strpos($video_url, 'vimeo.com')) {
            $m = [];
            preg_match('/vimeo\.com\/(\d+)/', $video_url, $m);
            $vid = isset($m[1]) ? $m[1] : '';
            if ($vid) {
                echo '<iframe src="https://player.vimeo.com/video/'.$vid.'" style="position:absolute;top:0;left:0;width:100%;height:100%" frameborder="0" allowfullscreen></iframe>';
            }
        } else {
            echo '<video controls style="position:absolute;top:0;left:0;width:100%;height:100%"><source src="'.esc_url($video_url).'"></video>';
        }
        echo '</div>';
    }
}
