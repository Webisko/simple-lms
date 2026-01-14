/**
 * Simple LMS - Meta Boxes JavaScript
 * 
 * Handles meta box interactions in the WordPress admin.
 * 
 * @package SimpleLMS
 * @since 1.4.0
 */

(function($) {
    'use strict';

    const SimpleLmsMetaBoxes = {
        
        init: function() {
            this.bindEvents();
            this.initMediaUploader();
            this.initVideoPreview();
        },

        bindEvents: function() {
            // Video URL preview
            $('#lesson_video_url').on('blur', this.generateVideoPreview.bind(this));
            
            // Video type change
            $('#lesson_video_type').on('change', this.handleVideoTypeChange.bind(this));
            
            // Duration input validation
            $('#lesson_duration').on('input', this.validateDuration);
            
            // Attachment management
            $(document).on('click', '.add-attachment', this.addAttachment.bind(this));
            $(document).on('click', '.remove-attachment', this.removeAttachment);
        },

        initMediaUploader: function() {
            // WordPress media uploader for attachments
            let mediaUploader;
            
            $(document).on('click', '.upload-attachment-btn', function(e) {
                e.preventDefault();
                
                const $button = $(this);
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: 'Select Attachment',
                    button: {
                        text: 'Add Attachment'
                    },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();
                    SimpleLmsMetaBoxes.handleAttachmentSelected(attachment, $button);
                });
                
                mediaUploader.open();
            });
        },

        handleAttachmentSelected: function(attachment, $button) {
            const $container = $button.closest('.attachment-item');
            $container.find('.attachment-id').val(attachment.id);
            $container.find('.attachment-title').text(attachment.title);
            $container.find('.attachment-preview').attr('src', attachment.icon).show();
        },

        initVideoPreview: function() {
            const videoUrl = $('#lesson_video_url').val();
            if (videoUrl) {
                this.generateVideoPreview();
            }
        },

        generateVideoPreview: function() {
            const videoUrl = $('#lesson_video_url').val();
            const $preview = $('#video-preview');
            
            if (!videoUrl) {
                $preview.hide();
                return;
            }
            
            // Generate preview via AJAX
            $.ajax({
                url: simpleLMS.ajaxurl,
                type: 'POST',
                data: {
                    action: 'generate_video_preview',
                    video_url: videoUrl,
                    nonce: simpleLMS.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $preview.html(response.data.html).fadeIn();
                    }
                }
            });
        },

        handleVideoTypeChange: function() {
            const videoType = $('#lesson_video_type').val();
            const $urlField = $('#lesson_video_url').closest('tr');
            
            if (videoType === 'none') {
                $urlField.hide();
            } else {
                $urlField.show();
            }
        },

        validateDuration: function() {
            const $input = $(this);
            let value = $input.val();
            
            // Only allow numbers
            value = value.replace(/[^0-9]/g, '');
            $input.val(value);
        },

        addAttachment: function(e) {
            e.preventDefault();
            
            const $container = $('.lesson-attachments-container');
            const $template = $('.attachment-item-template').clone();
            
            $template.removeClass('attachment-item-template hidden');
            $template.addClass('attachment-item');
            $container.append($template);
        },

        removeAttachment: function(e) {
            e.preventDefault();
            
            const $item = $(this).closest('.attachment-item');
            $item.fadeOut(300, function() {
                $(this).remove();
            });
        }
    };

    // Initialize on DOM ready
    $(document).ready(function() {
        SimpleLmsMetaBoxes.init();
    });

})(jQuery);
