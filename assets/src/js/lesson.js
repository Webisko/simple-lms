/**
 * Simple LMS - Lesson Page JavaScript
 * 
 * Handles lesson-specific functionality like video players,
 * attachments, and completion tracking.
 * 
 * @package SimpleLMS
 * @since 1.4.0
 */

import '../css/lesson.css';

(function($) {
    'use strict';

    const SimpleLmsLesson = {
        
        init: function() {
            this.initVideoPlayer();
            this.initAttachments();
            this.bindEvents();
        },

        initVideoPlayer: function() {
            const $videoContainer = $('.simple-lms-video-container');
            if (!$videoContainer.length) return;

            // Initialize video player based on type
            const videoType = $videoContainer.data('type');
            
            if (videoType === 'youtube') {
                this.initYouTubePlayer($videoContainer);
            } else if (videoType === 'vimeo') {
                this.initVimeoPlayer($videoContainer);
            }
        },

        initYouTubePlayer: function($container) {
            // YouTube player initialization
            const videoId = $container.data('video-id');
            if (!videoId) return;

            // Load YouTube IFrame API
            if (!window.YT) {
                const tag = document.createElement('script');
                tag.src = 'https://www.youtube.com/iframe_api';
                const firstScriptTag = document.getElementsByTagName('script')[0];
                firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
            }
        },

        initVimeoPlayer: function($container) {
            // Vimeo player initialization
            const videoId = $container.data('video-id');
            if (!videoId) return;

            // Load Vimeo Player API if needed
            if (typeof Vimeo === 'undefined') {
                const script = document.createElement('script');
                script.src = 'https://player.vimeo.com/api/player.js';
                document.head.appendChild(script);
            }
        },

        initAttachments: function() {
            // Track attachment downloads
            $('.lesson-attachment-link').on('click', function(e) {
                const attachmentId = $(this).data('attachment-id');
                
                // Track download via AJAX
                $.ajax({
                    url: simpleLms.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'track_attachment_download',
                        attachment_id: attachmentId,
                        lesson_id: simpleLms.lessonId,
                        nonce: simpleLms.nonce
                    }
                });
            });
        },

        bindEvents: function() {
            // Additional lesson-specific event handlers
            $(document).on('lesson:completed', this.handleLessonCompleted.bind(this));
        },

        handleLessonCompleted: function() {
            // Show completion message or redirect
            const $completionMsg = $('.lesson-completion-message');
            if ($completionMsg.length) {
                $completionMsg.fadeIn();
            }
        }
    };

    // Initialize on DOM ready
    $(document).ready(function() {
        SimpleLmsLesson.init();
    });

    // Expose to global scope
    window.SimpleLmsLesson = SimpleLmsLesson;

})(jQuery);
