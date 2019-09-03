<?php
/**
 * @package Africas Talking For WooCommerce
 * @subpackage Plugin Menus
 * @author Mauko Maunde < hi@mauko.co.ke >
 * @since 0.18.01
 */
require_once plugin_dir_path(__DIR__).'vendor/autoload.php';
use AfricasTalking\SDK\AfricasTalking;

// Add admin menus for plugin actions
add_action('admin_menu', 'africastalking_transactions_menu');
function africastalking_transactions_menu()
{
    add_submenu_page(
        'edit.php?post_type=at_ipn',
        'Africa\'s Talking APIs',
        'Explore APIs',
        'manage_options',
        'at_settings',
        'at_admin_settings_page'
    );

    add_submenu_page(
        'edit.php?post_type=at_ipn',
        'Africa\'s Talking Configuration',
        'Configuration',
        'manage_options',
        'africastalking_options',
        'africastalking_transactions_menu_pref'
    );
}

// Redirect to plugin configuration page
function africastalking_transactions_menu_pref()
{
    if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        wp_redirect(
            admin_url(
                'admin.php?page=wc-settings&tab=checkout&section=africastalking'
            )
        );
    } else {
        at_options_page();
    }
}

// Redirect to plugin configuration page
function at_admin_settings_page()
{
    ?>
    <div class="wrap">
        <h1><?php _e('Africas Talking APIs', 'woocommerce');?></h1><?php
global $at_active_tab;
    $at_active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'welcome';?>

        <h2 class="nav-tab-wrapper">
        <?php do_action('at_settings_tab');?>
        </h2>
        <?php do_action('at_settings_content');?>
    </div><?php
}

add_action('at_settings_tab', 'at_welcome_tab', 1);
function at_welcome_tab()
{
    global $at_active_tab;?>
	<a class="nav-tab <?php echo $at_active_tab == 'welcome' || '' ? 'nav-tab-active' : ''; ?>" href="<?php echo admin_url('edit.php?post_type=at_ipn&page=at_settings&tab=welcome'); ?>"><?php _e('Welcome', 'woocommerce');?> </a>
    <a class="nav-tab <?php echo $at_active_tab == 'sms' || '' ? 'nav-tab-active' : ''; ?>" href="<?php echo admin_url('edit.php?post_type=at_ipn&page=at_settings&tab=sms'); ?>"><?php _e('Send SMS', 'woocommerce');?> </a>
    <a class="nav-tab <?php echo $at_active_tab == 'b2c' || '' ? 'nav-tab-active' : ''; ?>" href="<?php echo admin_url('edit.php?post_type=at_ipn&page=at_settings&tab=b2c'); ?>"><?php _e('B2C Payment', 'woocommerce');?> </a>
	<a class="nav-tab <?php echo $at_active_tab == 'airtime' || '' ? 'nav-tab-active' : ''; ?>" href="<?php echo admin_url('edit.php?post_type=at_ipn&page=at_settings&tab=airtime'); ?>"><?php _e('Buy Airtime', 'woocommerce');?> </a>
	<?php
}

add_action('at_settings_content', 'at_welcome_render_page');
function at_welcome_render_page()
{
    global $at_active_tab;
    if ('' || 'welcome' == $at_active_tab) { ?>

	<h3><?php _e('Welcome', 'woocommerce');?></h3>
    <p>Explore the AT APIs available for use, for your convenience.</p>
	<!-- Put your content here -->
	<?php } elseif ('' || 'sms' == $at_active_tab) {

        if (isset($_POST['sms_phone'])) {
            $username = at_option('username');
            $apiKey   = at_option('key');
            $AT       = new AfricasTalking($username, $apiKey);
            $sms      = $AT->sms();

            $recipients = strip_tags(trim($_POST['sms_phone']));
            $message    = strip_tags(trim($_POST['sms_message']));
            $from       = "myShortCode";

            try {
                // Thats it, hit send and we'll take care of the rest
                $result = $sms->send([
                    'to'      => $recipients,
                    'message' => $message,
                    'from'    => $from,
                ]);

                echo '<div class="notice notice-'.$result['status'].' is-dismissible">
                    <p>'.ucfirst($result['data']).'.</p>
                </div>';
            } catch (Exception $e) {
                echo '<div class="notice notice-error is-dismissible">
                    <p>'.ucfirst($e->getMessage()).'.</p>
                </div>';
            }

        }?>

        <h3><?php _e('Send SMS', 'woocommerce');?></h3>
        <form method="post" action="">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <label for="at_shortcode">Phone number</label>
                        </th>
                        <td>
                            <input class="regular-text" type="tel" id="at_shortcode" name="sms_phone" value="+254"/>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="at_shortcode">Your message</label>
                        </th>
                        <td>
                            <textarea class="regular-text" id="at_shortcode" name="sms_message" placeholder="Your message"></textarea>
                        </td>
                    </tr>
                    <tr valign="top">
                </table>
                <?php submit_button('Send message');?>
            </form>
        <?php
    } elseif ('' || 'b2c' == $at_active_tab) {

        if (isset($_POST['sms_phone'])) {
            $username = at_option('username');
            $apiKey   = at_option('key');
            $AT       = new AfricasTalking($username, $apiKey);
            $sms      = $AT->sms();

            $recipients = strip_tags(trim($_POST['sms_phone']));
            $message    = strip_tags(trim($_POST['sms_message']));
            $from       = "myShortCode";

            try {
                // Thats it, hit send and we'll take care of the rest
                $result = $sms->send([
                    'to'      => $recipients,
                    'message' => $message,
                    'from'    => $from,
                ]);

                echo '<div class="notice notice-'.$result['status'].' is-dismissible">
                    <p>'.ucfirst($result['data']).'.</p>
                </div>';
            } catch (Exception $e) {
                echo '<div class="notice notice-error is-dismissible">
                    <p>'.ucfirst($e->getMessage()).'.</p>
                </div>';
            }

        }?>

        <h3><?php _e('Send to phone', 'woocommerce');?></h3>
        <form method="post" action="">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <label for="at_shortcode">Phone number</label>
                        </th>
                        <td>
                            <input class="regular-text" type="tel" id="at_shortcode" name="b2c_phone" value="+254"/>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="at_shortcode">Amount to send</label>
                        </th>
                        <td>
                            <input class="regular-text" type="number" id="at_shortcode" name="b2c_amount" value="100"/>
                        </td>
                    </tr>
                    <tr valign="top">
                </table>
                <?php submit_button('Send now');?>
            </form>
        <?php
    } elseif ('' || 'airtime' == $at_active_tab) {

        if (isset($_POST['sms_phone'])) {
            $username = at_option('username');
            $apiKey   = at_option('key');
            $AT       = new AfricasTalking($username, $apiKey);
            $sms      = $AT->sms();

            $recipients = strip_tags(trim($_POST['sms_phone']));
            $message    = strip_tags(trim($_POST['sms_message']));
            $from       = "myShortCode";

            try {
                // Thats it, hit send and we'll take care of the rest
                $result = $sms->send([
                    'to'      => $recipients,
                    'message' => $message,
                    'from'    => $from,
                ]);

                echo '<div class="notice notice-'.$result['status'].' is-dismissible">
                    <p>'.ucfirst($result['data']).'.</p>
                </div>';
            } catch (Exception $e) {
                echo '<div class="notice notice-error is-dismissible">
                    <p>'.ucfirst($e->getMessage()).'.</p>
                </div>';
            }

        }?>

        <h3><?php _e('Buy airtime', 'woocommerce');?></h3>
        <form method="post" action="">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <label for="at_shortcode">Phone number</label>
                        </th>
                        <td>
                            <input class="regular-text" type="tel" id="at_shortcode" name="sms_phone" value="+254"/>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="at_shortcode">Amount to buy</label>
                        </th>
                        <td>
                            <input class="regular-text" type="number" id="at_shortcode" name="sms_amount" value="100"/>
                        </td>
                    </tr>
                    <tr valign="top">
                </table>
                <?php submit_button('Buy now');?>
            </form>
        <?php
    }
}

function at_register_settings()
{
    add_option('at_shortcode', '744312');
    register_setting('at_options_group', 'at_shortcode', 'at_callback');

    add_option('at_api_key', 'CANN4N9UGFHH1E7CX41T');
    register_setting('at_options_group', 'at_api_key', 'at_callback');
}
add_action('admin_init', 'at_register_settings');

function at_options_page()
{
    ?>
    <div>
        <h2>Africas Talking Configuration</h2>
        <form method="post" action="options.php">
            <?php settings_fields('at_options_group');?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">
                        <label for="at_shortcode">AT Shortcode</label>
                    </th>
                    <td>
                        <input class="regular-text" type="text" id="at_shortcode" name="at_api_shortcode" value="<?php echo get_option('at_api_shortcode'); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="at_shortcode">API Username</label>
                    </th>
                    <td>
                        <input class="regular-text" type="text" id="at_shortcode" name="at_api_username" value="<?php echo get_option('at_api_username'); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="at_shortcode">API Key</label>
                    </th>
                    <td>
                        <input class="regular-text" type="text" id="at_shortcode" name="at_api_key" value="<?php echo get_option('at_api_key'); ?>" />
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Configuration');?>
        </form>
    </div>
    <?php
}?>