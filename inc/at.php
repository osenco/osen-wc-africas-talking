<?php 

require_once plugin_dir_path(__DIR__).'vendor/autoload.php';
use AfricasTalking\SDK\AfricasTalking;

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

