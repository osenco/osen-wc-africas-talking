<?php
/**
 * @package Africas Talking
 * @subpackage Plugin File
 * @author Mauko Maunde < hi@mauko.co.ke >
 * @since 0.19.08
 *
 * Plugin Name: Africas Talking
 * Plugin URI:  https://africastalking.org
 * Description: This plugin extends WordPress and WooCommerce functionality to integrate Lipa Na M-PESA by Africas Talking for making and receiving online payments.
 * Version:     0.19.09
 * Author:      Osen Concepts
 * Author URI:  https://osen.co.ke/
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: osen
 * Domain Path: /languages
 *
 * WC requires at least: 3.0.0
 * WC tested up to: 3.6.5
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AT_VER', '1.19.09');
if (!defined('AT_PLUGIN_FILE')) {
    define('AT_PLUGIN_FILE', __FILE__);
}

// Deactivate plugin if WooCommerce is not active
register_activation_hook(__FILE__, 'wc_africastalking_activation_check');
function wc_africastalking_activation_check()
{
    if (!get_option('wc_africastalking_flush_rewrite_rules_flag')) {
        add_option('wc_africastalking_flush_rewrite_rules_flag', true);
    }
}

add_action('init', 'wc_africastalking_flush_rewrite_rules_maybe', 20);
function wc_africastalking_flush_rewrite_rules_maybe()
{
    if (get_option('wc_africastalking_flush_rewrite_rules_flag')) {
        flush_rewrite_rules();
        delete_option('wc_africastalking_flush_rewrite_rules_flag');
    }
}

// Redirect to configuration page when activated
add_action('activated_plugin', 'wc_africastalking_detect_plugin_activation', 10, 2);
function wc_africastalking_detect_plugin_activation($plugin, $network_activation)
{
    if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) && $plugin == 'osen-wc-africastalking/osen-wc-africastalking.php') {
        exit(wp_redirect(admin_url('admin.php?page=wc-settings&tab=checkout&section=africastalking')));
    }
}

// Flush Permalinks to avail IPN Endpoint /
register_activation_hook(__FILE__, function () {flush_rewrite_rules();});

// Add plugi links for Configuration, API Docs
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'africastalking_action_links');
function africastalking_action_links($links)
{
    return array_merge(
        $links,
        array(
            // '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=africastalking') . '">&nbsp;Configure</a>',
            '<a href="https://build.at-labs.io/discover">&nbsp;API Docs</a>',
        )
    );
}

add_action('admin_footer', function () {
    ?>
	<script src="https://cdn.jsdelivr.net/npm/clipboard@2/dist/clipboard.min.js"></script>
	<script>
		var copy = document.getElementById('africastalking_ipn_url');
    	var clipboard = new ClipboardJS(copy);

		clipboard.on('success', function(e) {
			jQuery('#africastalking_ipn_url').after('<span style="color: green; padding-left: 2px;">Copied!</span>');
		});

		clipboard.on('error', function(e) {
			console.log(e);
		});
	</script>
	<?php
});

/*
 * Register our gateway with woocommerce
 */
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_filter('woocommerce_payment_gateways', 'africastalking_add_to_gateways');
}
function africastalking_add_to_gateways($gateways)
{
    $gateways[] = 'WC_Africas_Talking_Gateway';
    return $gateways;
}

if (!function_exists('at_post_id_by_meta_key_and_value')) {
    function at_post_id_by_meta_key_and_value($key, $value)
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
}

// Define Gateway Class
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('plugins_loaded', 'africastalking_init', 0);
}
function africastalking_init()
{
    class WC_Africas_Talking_Gateway extends WC_Payment_Gateway
    {
        public $shortcode;

        function __construct()
        {
            // global ID
            $this->id                 = "africastalking";
            $this->method_title       = __("Lipa Na M-PESA via Africas Talking", 'woocommerce');
            $this->method_description = ($this->get_option('enabled') == 'yes')
            ? 'Receive payments using your shortcode through Africa\'s Talking.'
            : __('<p>Log into your <a href="https://app.africastalking.com" target="_blank">Africas Talking Account</a> and configure as follows:</p>
			<p>Remember to <a title="' . __('Navigate to page and click Save Changes', 'woocommerce') . '" href="' . admin_url('options-permalink.php') . '">flush your rewrite rules</a>.</p>', 'woocommerce');

            // vertical tab title
            $this->title = __("Lipa Na M-PESA", 'woocommerce');

            // Add Gateway Icon
            $this->icon = apply_filters('woocommerce_mpesa_icon', plugins_url('inc/Africas Talking.png', __FILE__));

            // Set for extra checkout fields
            $this->has_fields = false;

            // Load time variable setting
            $this->init_settings();

            // Init Form fields
            $this->init_form_fields();

            // Turn these settings into variables we can use
            foreach ($this->settings as $setting_key => $value) {
                $this->$setting_key = $value;
            }

            $this->shortcode = $this->get_option('shortcode', '123456');

            // Save settings
            if (is_admin()) {
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            }
        }

        // Administration option fields for this Gateway
        public function init_form_fields()
        {
            $shipping_methods = array();

            foreach (WC()->shipping()->load_shipping_methods() as $method) {
                $shipping_methods[$method->id] = $method->get_method_title();
            }

            $this->form_fields = array(
                'enabled'            => array(
                    'title'   => __('Enable/Disable', 'woocommerce'),
                    'label'   => __('Enable this payment gateway', 'woocommerce'),
                    'type'    => 'checkbox',
                    'default' => 'no',
                ),
                'title'              => array(
                    'title'    => __('Method Title', 'woocommerce'),
                    'type'     => 'text',
                    'desc_tip' => __('Payment title of checkout process.', 'woocommerce'),
                    'default'  => __('Lipa Na M-PESA', 'woocommerce'),
                ),
                'env'                => array(
                    'title'       => __('Environment', 'woocommerce'),
                    'type'        => 'select',
                    'options'     => array(
                        'sandbox' => __('Sandbox', 'woocommerce'),
                        'live'    => __('Live', 'woocommerce'),
                    ),
                    'description' => __('M-PESA Environment', 'woocommerce'),
                    'default'     => 'sandbox',
                    'desc_tip'    => true,
                ),
                'shortcode'          => array(
                    'title'    => __('AT Shortcode', 'woocommerce'),
                    'type'     => 'text',
                    'desc_tip' => __('This is the Till number provided by Africas Talking when you signed up for an account.', 'woocommerce'),
                    'default'  => '',
                ),
                'username'             => array(
                    'title'       => __('AT Username', 'woocommerce'),
                    'type'        => 'text',
                    'description' => __('Your App Consumer Secret From Safaricom Daraja.', 'woocommerce'),
                    'default'     => __('bclwIPkcRqw61yUt', 'woocommerce'),
                    'desc_tip'    => true,
                ),
                'key'                => array(
                    'title'       => __('AT API Key', 'woocommerce'),
                    'type'        => 'text',
                    'description' => __('Your App Consumer Key From Safaricom Daraja.', 'woocommerce'),
                    'default'     => __('9v38Dtu5u2BpsITPmLcXNWGMsjZRWSTG', 'woocommerce'),
                    'desc_tip'    => true,
                ),
                'enable_for_methods' => array(
                    'title'             => __('Enable for shipping methods', 'woocommerce'),
                    'type'              => 'multiselect',
                    'class'             => 'wc-enhanced-select',
                    'css'               => 'width: 400px;',
                    'default'           => '',
                    'description'       => __('If M-PESA is only available for certain methods, set it up here. Leave blank to enable for all methods.', 'woocommerce'),
                    'options'           => $shipping_methods,
                    'desc_tip'          => true,
                    'custom_attributes' => array(
                        'data-placeholder' => __('Select shipping methods', 'woocommerce'),
                    ),
                ),
                'enable_for_virtual' => array(
                    'title'   => __('Accept for virtual orders', 'woocommerce'),
                    'label'   => __('Accept Lipa na M-PESA if the order is virtual', 'woocommerce'),
                    'type'    => 'checkbox',
                    'default' => 'yes',
                ),
                'instructions'       => array(
                    'title'       => __('Thank You Instructions', 'woocommerce'),
                    'type'        => 'textarea',
                    'description' => __('Instructions that will be added to the thank you page.', 'woocommerce'),
                    'default'     => __('Thank you for buying from us. You will receive a confirmation message from us shortly.', 'woocommerce'),
                    'desc_tip'    => true,
                ),
            );
        }

        // Response handled for payment gateway
        public function process_payment($order_id)
        {
            $currency = get_woocommerce_currency_symbol();
            $order    = wc_get_order($order_id);
            $order->update_status('pending', __('Waiting to verify M-PESA payment.', 'woocommerce'));
            $order->reduce_order_stock();
            WC()->cart->empty_cart();
            $order->add_order_note("Awaiting payment confirmation from Africas Talking");
            // Insert the payment into the database

            $post_id = at_post_id_by_meta_key_and_value('_reference', trim($_POST['reference']));

            if (!$post_id) {
                $post_id = wp_insert_post(
                    array(
                        'post_title'  => 'Order ' . time(),
                        'post_status' => 'publish',
                        'post_type'   => 'africastalking_ipn',
                        'post_author' => is_user_logged_in() ? get_current_user_id() : 1,
                    )
                );

                update_post_meta($post_id, '_order_id', $order_id);
                update_post_meta($post_id, '_transaction', $order_id);
                update_post_meta($post_id, '_reference', strip_tags(trim($_POST['reference'])));
                update_post_meta($order_id, '_mpesa_reference', strip_tags(trim($_POST['reference'])));
                update_post_meta($post_id, '_amount', round($amount));
                update_post_meta($post_id, '_order_status', 'on-hold');
            } else {
                update_post_meta($post_id, '_order_id', $order_id);
                $amount                = get_post_meta($post_id, '_amount', true);
                $transaction_reference = get_post_meta($post_id, '_reference', true);
                if ((int) $amount >= $order->get_total()) {
                    $order->add_order_note(__("FULLY PAID: Payment of $currency $amount from " . strip_tags(trim($_POST['phone'])) . " and MPESA reference $transaction_reference confirmed by Africas Talking", 'woocommerce'));
                    // $order->payment_complete();
                    $order->update_status('completed');
                } else {
                    $order->add_order_note(__("PARTLY PAID: Received $currency $amount from " . strip_tags(trim($_POST['phone'])) . " and MPESA reference $transaction_reference", 'woocommerce'));
                    $order->update_status('processing');
                }
            }

            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url($order),
            );
        }

        // Validate OTP
        public function validate_fields()
        {
            if (empty($_POST['reference'])) {
                wc_add_notice('Confirmation Code is required!', 'error');
                return false;
            }

            return true;
        }

        /**
         * Output for the order received page.
         */
        public function thankyou_page()
        {
            if ($this->instructions) {
                echo wpautop(wptexturize($this->instructions));
            }
        }

    }
}

/**
 * Load Extra Plugin Functions
 */
foreach (glob(plugin_dir_path(__FILE__) . 'inc/*.php') as $filename) {
    require_once $filename;
}

/**
 * Load Custom Post Type (Africas Talking Payments) Functionality
 */
foreach (glob(plugin_dir_path(__FILE__) . 'cpt/*.php') as $filename) {
    require_once $filename;
}
