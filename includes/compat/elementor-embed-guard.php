<?php
/**
 * Elementor embed guard to prevent warnings when URL is empty/invalid.
 */

namespace SimpleLMS\Elementor;

if (!defined('ABSPATH')) {
    exit;
}

class Elementor_Embed_Guard {
    public static function init(): void {
        \add_filter('elementor/frontend/widget/should_render', [__CLASS__, 'guard_embed_widgets'], 10, 2);
    }

    /**
     * Skip rendering for Embed/Video widgets when URL is empty/invalid.
     *
     * @param bool                   $should_render
     * @param \Elementor\Widget_Base $widget
     * @return bool
     */
    public static function guard_embed_widgets($should_render, $widget): bool {
        try {
            $name = method_exists($widget, 'get_name') ? $widget->get_name() : '';
            if (!in_array($name, ['embed', 'video', 'video-playlist'], true)) {
                return $should_render;
            }

            $settings = method_exists($widget, 'get_settings_for_display') ? (array) $widget->get_settings_for_display() : [];

            $url = '';
            foreach (['url', 'external_url', 'link', 'youtube_url', 'vimeo_url'] as $key) {
                if (!empty($settings[$key]) && is_string($settings[$key])) {
                    $url = $settings[$key];
                    break;
                }
            }

            if (!$url || !preg_match('#^https?://#i', $url)) {
                return false; // prevent core from hitting embed.php with invalid data
            }
        } catch (\Throwable $e) {
            return $should_render;
        }

        return $should_render;
    }
}
