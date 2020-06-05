<?php

function mysite_pending($order_id) {
    error_log("$order_id set to PENDING", 0);
    }
    function mysite_failed($order_id) {
    error_log("$order_id set to FAILED", 0);
    }
    function mysite_hold($order_id) {
    error_log("$order_id set to ON HOLD", 0);
    }
    function mysite_processing($order_id) {
    error_log("$order_id set to PROCESSING", 0);
    }
    function mysite_completed($order_id) {
    error_log("$order_id set to COMPLETED", 0);
    }
    function mysite_refunded($order_id) {
    error_log("$order_id set to REFUNDED", 0);
    }
    function mysite_cancelled($order_id) {
    error_log("$order_id set to CANCELLED", 0);
    }

    add_action( ‘woocommerce_order_status_pending’, ‘mysite_pending’);
    add_action( ‘woocommerce_order_status_failed’, ‘mysite_failed’);
    add_action( ‘woocommerce_order_status_on-hold’, ‘mysite_hold’);
    // Note that it’s woocommerce_order_status_on-hold, not on_hold.
    add_action( ‘woocommerce_order_status_processing’, ‘mysite_processing’);
    add_action( ‘woocommerce_order_status_completed’, ‘mysite_completed’);
    add_action( ‘woocommerce_order_status_refunded’, ‘mysite_refunded’);
    add_action( ‘woocommerce_order_status_cancelled’, ‘mysite_cancelled’);
