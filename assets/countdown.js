/**
 * WooCommerce Discount Display - Countdown Handler
 * Handles live countdown timer for sale end dates
 * Works independently of server time - uses client time for accuracy with caching
 */

(function($) {
    'use strict';

    // Store all active countdown intervals
    var countdownIntervals = [];

    /**
     * Initialize countdown timers on page load
     */
    function initCountdowns() {
        $('.wdd-countdown-container').each(function() {
            initSingleCountdown($(this));
        });
    }

    /**
     * Initialize a single countdown timer
     */
    function initSingleCountdown($container) {
        var endTimestamp = parseInt($container.data('end-timestamp'), 10);
        var thresholdSeconds = parseInt($container.data('threshold'), 10);
        
        if (!endTimestamp) {
            return;
        }

        // Calculate current state using client time
        var now = Math.floor(Date.now() / 1000);
        var timeRemaining = endTimestamp - now;

        // Update display based on current time remaining
        updateCountdownDisplay($container, endTimestamp, thresholdSeconds, timeRemaining);

        // If countdown is active (not expired and within threshold), start live timer
        if (timeRemaining > 0 && timeRemaining <= thresholdSeconds) {
            startLiveCountdown($container, endTimestamp);
        } else if (timeRemaining > thresholdSeconds) {
            // Check periodically if we should switch to countdown mode
            var checkInterval = setInterval(function() {
                var currentNow = Math.floor(Date.now() / 1000);
                var currentRemaining = endTimestamp - currentNow;
                
                if (currentRemaining <= thresholdSeconds) {
                    clearInterval(checkInterval);
                    updateCountdownDisplay($container, endTimestamp, thresholdSeconds, currentRemaining);
                    if (currentRemaining > 0) {
                        startLiveCountdown($container, endTimestamp);
                    }
                }
            }, 60000); // Check every minute
            
            countdownIntervals.push(checkInterval);
        }
    }

    /**
     * Update the countdown display based on time remaining
     */
    function updateCountdownDisplay($container, endTimestamp, thresholdSeconds, timeRemaining) {
        var $countdown = $container.find('.wdd-countdown');
        
        if (timeRemaining <= 0) {
            // Sale has ended
            $countdown.removeClass('wdd-countdown-static wdd-countdown-urgent').addClass('wdd-expired');
            $countdown.html('<span class="wdd-countdown-label">' + getTranslation('expired_text') + '</span>');
        } else if (timeRemaining <= thresholdSeconds) {
            // Switch to urgent countdown mode
            $countdown.removeClass('wdd-countdown-static wdd-expired').addClass('wdd-countdown-urgent');
            $countdown.html(
                '<span class="wdd-countdown-label">' + getTranslation('sale_ends_in_text') + '</span> ' +
                '<span class="wdd-countdown-timer" data-end="' + endTimestamp + '">' + formatTimeRemaining(timeRemaining) + '</span>'
            );
        }
        // If more than threshold, keep the static date display (server-rendered)
    }

    /**
     * Start live countdown timer
     */
    function startLiveCountdown($container, endTimestamp) {
        var $timer = $container.find('.wdd-countdown-timer');
        
        if (!$timer.length) {
            return;
        }

        // Update immediately
        updateTimer($timer, endTimestamp);

        // Update every second
        var interval = setInterval(function() {
            var remaining = updateTimer($timer, endTimestamp);
            
            if (remaining <= 0) {
                clearInterval(interval);
                // Update to expired state
                var $countdown = $container.find('.wdd-countdown');
                $countdown.removeClass('wdd-countdown-urgent').addClass('wdd-expired');
                $countdown.html('<span class="wdd-countdown-label">' + getTranslation('expired_text') + '</span>');
            }
        }, 1000);

        countdownIntervals.push(interval);
    }

    /**
     * Update a single timer element
     */
    function updateTimer($timer, endTimestamp) {
        var now = Math.floor(Date.now() / 1000);
        var timeRemaining = endTimestamp - now;

        if (timeRemaining <= 0) {
            $timer.text('0d 0h 0m 0s');
            return 0;
        }

        $timer.html(formatTimeRemaining(timeRemaining));
        return timeRemaining;
    }

    /**
     * Format time remaining into readable string
     */
    function formatTimeRemaining(seconds) {
        var days = Math.floor(seconds / 86400);
        var hours = Math.floor((seconds % 86400) / 3600);
        var minutes = Math.floor((seconds % 3600) / 60);
        var secs = seconds % 60;

        var parts = [];

        if (days > 0) {
            parts.push('<span class="wdd-time-segment">' + days + 'd</span>');
        }
        
        parts.push('<span class="wdd-time-segment">' + hours + 'h</span>');
        parts.push('<span class="wdd-time-segment">' + minutes + 'm</span>');
        parts.push('<span class="wdd-time-segment">' + secs + 's</span>');

        return parts.join('<span class="wdd-time-separator"> </span>');
    }

    /**
     * Get translation string
     */
    function getTranslation(key) {
        if (typeof wdd_countdown_params !== 'undefined' && wdd_countdown_params[key]) {
            return wdd_countdown_params[key];
        }
        
        // Fallback translations
        var fallbacks = {
            'sale_expires_text': 'Sale expires at:',
            'sale_ends_in_text': 'Sale ends in:',
            'expired_text': 'Sale ended'
        };
        
        return fallbacks[key] || key;
    }

    /**
     * Clean up intervals when leaving page
     */
    function cleanup() {
        countdownIntervals.forEach(function(interval) {
            clearInterval(interval);
        });
        countdownIntervals = [];
    }

    // Initialize on document ready
    $(document).ready(function() {
        initCountdowns();
    });

    // Cleanup on page unload
    $(window).on('beforeunload', cleanup);

    // Re-initialize if content is dynamically loaded (AJAX)
    $(document).on('wdd_content_loaded', function() {
        initCountdowns();
    });

})(jQuery);
