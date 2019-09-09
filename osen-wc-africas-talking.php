<?php
/**
 * @package Africas Talking
 * @subpackage Plugin File
 * @author Osen Concepts < hi@osen.co.ke >
 * @since 0.19.08
 *
 * Plugin Name: Africas Talking for WordPress
 * Plugin URI:  https://africastalking.org
 * Description: This plugin extends WordPress and WooCommerce functionality to integrate Lipa Na Africa\'s Talking C2B by Africas Talking for making and receiving online payments.
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

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

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
    } else {
        exit(wp_redirect(admin_url('admin.php?page=africastalking_options')));
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
            $this->method_title       = __("Africa's Talking", 'woocommerce');
            $this->method_description = ($this->get_option('enabled') == 'yes')
            ? 'Receive payments through Africa\'s Talking.'
            : __('<p>Log into your <a href="https://account.africastalking.com" target="_blank">Africas Talking Account</a> and copy your API key here.:</p>
			<p>Remember to <a title="' . __('Navigate to page and click Save Changes', 'woocommerce') . '" href="' . admin_url('options-permalink.php') . '">flush your rewrite rules</a>.</p>', 'woocommerce');

            // vertical tab title
            $this->title = __("Lipa Na Africa\'s Talking C2B", 'woocommerce');

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
                    'default'  => __('Africa\'s Talking C2B', 'woocommerce'),
                ),
                'shortcode'          => array(
                    'title'    => __('SMS Sender ID', 'woocommerce'),
                    'type'     => 'text',
                    'desc_tip' => __('This is your Africa\'s Talking Sender ID.', 'woocommerce'),
                    'default'  => 'AT2FA',
                ),
                'username'           => array(
                    'title'       => __('AT Username', 'woocommerce'),
                    'type'        => 'text',
                    'description' => __('Your App Consumer Secret From Safaricom Daraja.', 'woocommerce'),
                    'default'     => __('sandbox', 'woocommerce'),
                    'desc_tip'    => true,
                ),
                'key'                => array(
                    'title'       => __('AT API Key', 'woocommerce'),
                    'type'        => 'text',
                    'description' => __('Your App Consumer Key From Safaricom Daraja.', 'woocommerce'),
                    'default'     => __('0be438d7976ba7613238370ea8f84e3eaa93b23e59cb0d132a1aa72260bfc795', 'woocommerce'),
                    'desc_tip'    => true,
                ),
                'description'        => array(
                    'title'       => __('Method Description', 'woocommerce'),
                    'type'        => 'textarea',
                    'css'         => 'height: 140px',
                    'description' => __('Payment method description that the customer will see on your checkout.', 'woocommerce'),
                    'default'     => __('Confirm your phone number above before pressing the button below.
Your phone number MUST be registered with M-PESA for this to work.
You will get a prompt on your phone asking you to confirm the payment.
Enter your service (M-PESA) PIN to proceed.
If you don\'t see the pop up, please upgrade your SIM card by dialing *234*1*6#.
You will receive a confirmation message shortly thereafter.', 'woocommerce'),
                    'desc_tip'    => true,
                ),
                'instructions'       => array(
                    'title'       => __('Thank You Instructions', 'woocommerce'),
                    'type'        => 'textarea',
                    'description' => __('Instructions that will be added to the thank you page.', 'woocommerce'),
                    'default'     => __('Thank you for buying from us. You will receive a confirmation message from us shortly.', 'woocommerce'),
                    'desc_tip'    => true,
                ),
                'enable_for_methods' => array(
                    'title'             => __('Enable for shipping methods', 'woocommerce'),
                    'type'              => 'multiselect',
                    'class'             => 'wc-enhanced-select',
                    'css'               => 'width: 400px;',
                    'default'           => '',
                    'description'       => __('If Africa\'s Talking C2B is only available for certain methods, set it up here. Leave blank to enable for all methods.', 'woocommerce'),
                    'options'           => $shipping_methods,
                    'desc_tip'          => true,
                    'custom_attributes' => array(
                        'data-placeholder' => __('Select shipping methods', 'woocommerce'),
                    ),
                ),
                'enable_for_virtual' => array(
                    'title'   => __('Accept for virtual orders', 'woocommerce'),
                    'label'   => __('Accept Africa\'s Talking C2B if the order is virtual', 'woocommerce'),
                    'type'    => 'checkbox',
                    'default' => 'yes',
                ),
            );
        }

        // Response handled for payment gateway
        public function process_payment($order_id)
        {
            $username   = $this->get_option('username');
            $apiKey     = $this->get_option('key');
            $AT         = new AfricasTalking\SDK\AfricasTalking($username, $apiKey);

            $currency   = get_woocommerce_currency_symbol();
            $order      = new WC_Order($order_id);
            $total      = $order->get_total();
            $phone      = $order->get_billing_phone();
            $first_name = $order->get_billing_first_name();
            $last_name  = $order->get_billing_last_name();
            $reference  = 'ORDER#' . $order_id;

            $payments   = $AT->payments();
            $result     = $payments->mobileCheckout(
                array(
                    "productName"  => $reference,
                    "phoneNumber"  => $phone,
                    "currencyCode" => $currency,
                    "amount"       => round($total),
                )
            );

            if ($result) {
                if ($result['status'] == 'error') {
                    $error_message = 'Africa\'s Talking C2B Error ' . $result['data'] . ': ' . $result['data'];
                    $order->update_status('failed', __($error_message, 'woocommerce'));
                    wc_add_notice(__('Failed! ', 'woocommerce') . $error_message, 'error');
                    return array(
                        'result'   => 'fail',
                        'redirect' => '',
                    );
                } else {
                    /**
                     * Temporarily set status as "on-hold", incase the Africa\'s Talking C2B API times out before processing our request
                     */
                    $order->update_status('on-hold', __('Awaiting Africa\'s Talking confirmation of payment from ' . $phone . '.', 'woocommerce'));

                    /**
                     * Reduce stock levels
                     */
                    wc_reduce_stock_levels($order_id);

                    /**
                     * Remove contents from cart
                     */
                    WC()->cart->empty_cart();

                    // Insert the payment into the database
                    $post_id = wp_insert_post(
                        array(
                            'post_title'   => 'Mobile Checkout',
                            'post_content' => "Response: " . json_encode($result),
                            'post_status'  => 'publish',
                            'post_type'    => 'at_ipn',
                            'post_author'  => is_user_logged_in() ? get_current_user_id() : $this->get_option('accountant'),
                        )
                    );

                    update_post_meta($post_id, '_customer', "{$first_name} {$last_name}");
                    update_post_meta($post_id, '_phone', $phone);
                    update_post_meta($post_id, '_order_id', $order_id);
                    update_post_meta($post_id, '_amount', $total);
                    update_post_meta($post_id, '_reference', $reference);
                    update_post_meta($post_id, '_receipt', 'N/A');
                    update_post_meta($post_id, '_order_status', 'on-hold');

                    $this->instructions .= '<p>Awaiting Africa\'s Talking confirmation of payment from ' . $phone . ' for request ' . $request_id . '. Check your phone for the STK Prompt.</p>';

                    $order      = wc_get_order($order_id);

                    $sms      = $AT->sms();

                    $message    = "Hi {$first_name} {$last_name}, your order {$reference} of KSh {$total} has been received and being processed.";
                    $from       = at_option('shortcode');

                    try {
                        // Thats it, hit send and we'll take care of the rest
                        $result = $sms->send([
                            'to'      => $phone,
                            'message' => $message,
                            'from'    => at_option('shortcode'),
                        ]);
                    } catch (Exception $e) {
                        $result = [
                            'status' => 'error',
                            'data'   => $e->getMessage(),
                        ];
                    }

                    // Return thankyou redirect
                    return array(
                        'result'   => 'success',
                        'redirect' => $this->get_return_url($order),
                    );
                }
            } else {
                $error_message = __('Could not connect to Daraja', 'woocommerce');

                $order->update_status('failed', $error_message);
                wc_add_notice(__('Failed! ', 'woocommerce') . $error_message, 'error');

                return array(
                    'result'   => 'fail',
                    'redirect' => '',
                );
            }
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
