<?php 
function at_option($key, $default = '')
{
    if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        $at = new WC_Africas_Talking_Gateway();
        $value = $at->get_option($key, $default);
    } else {
        $value = get_option('at_api_'.$key);
    }

    return $value;
}

function africastalking_post_id_by_meta_key_and_value($key, $value)
{
    global $wpdb;
    $meta = $wpdb->get_results("SELECT * FROM `" . $wpdb->postmeta . "` WHERE meta_key='" . $key . "' AND meta_value='" . $value . "'");
    if (is_array($meta) && !empty($meta) && isset($meta[0])) {
        $meta = $meta[0];
    }

    if (is_object($meta)) {
        return $meta->post_id;
    } else {
        return false;
    }
}
