/**
 * Simple LMS - Settings Page JavaScript
 * 
 * Handles settings page interactions and validations.
 * 
 * @package SimpleLMS
 * @since 1.4.0
 */

(function($) {
    'use strict';

    const SimpleLmsSettings = {
        
        init: function() {
            this.bindEvents();
            this.initConditionalFields();
        },

        bindEvents: function() {
            // GA4 settings toggle
            $('#simple_lms_ga4_enabled').on('change', this.toggleGA4Fields);
            
            // Analytics toggle
            $('#simple_lms_analytics_enabled').on('change', this.toggleAnalyticsFields);
            
            // Form validation
            $('form').on('submit', this.validateForm.bind(this));
        },

        initConditionalFields: function() {
            // Show/hide GA4 fields based on checkbox state
            this.toggleGA4Fields();
            this.toggleAnalyticsFields();
        },

        toggleGA4Fields: function() {
            const isEnabled = $('#simple_lms_ga4_enabled').is(':checked');
            const $ga4Fields = $('#simple_lms_ga4_measurement_id, #simple_lms_ga4_api_secret').closest('tr');
            
            if (isEnabled) {
                $ga4Fields.fadeIn();
            } else {
                $ga4Fields.fadeOut();
            }
        },

        toggleAnalyticsFields: function() {
            const isEnabled = $('#simple_lms_analytics_enabled').is(':checked');
            const $analyticsSection = $('.simple_lms_analytics_section');
            
            if (isEnabled) {
                $analyticsSection.fadeIn();
            } else {
                $analyticsSection.fadeOut();
            }
        },

        validateForm: function(e) {
            // Validate GA4 settings if enabled
            const ga4Enabled = $('#simple_lms_ga4_enabled').is(':checked');
            
            if (ga4Enabled) {
                const measurementId = $('#simple_lms_ga4_measurement_id').val();
                const apiSecret = $('#simple_lms_ga4_api_secret').val();
                
                if (!measurementId || !apiSecret) {
                    e.preventDefault();
                    alert('Please enter both GA4 Measurement ID and API Secret.');
                    return false;
                }
                
                // Validate measurement ID format (G-XXXXXXXXXX)
                if (!/^G-[A-Z0-9]+$/.test(measurementId)) {
                    e.preventDefault();
                    alert('Invalid GA4 Measurement ID format. Should be G-XXXXXXXXXX');
                    return false;
                }
            }
            
            return true;
        }
    };

    // Initialize on DOM ready
    $(document).ready(function() {
        SimpleLmsSettings.init();
    });

})(jQuery);
