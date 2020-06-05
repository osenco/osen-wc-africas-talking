<?php
/**
 * @package Africas Talking For WordPress
 * @subpackage Plugin Menus
 * @author Osen Concepts < hi@osen.co.ke >
 * @since 0.18.01
 */
require_once plugin_dir_path(__DIR__).'vendor/autoload.php';

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
        'Send SMS',
        '> Send SMS',
        'manage_options',
        'edit.php?post_type=at_ipn&page=at_settings&tab=sms'
    );

    add_submenu_page(
        'edit.php?post_type=at_ipn',
        'B2C Payment',
        '> B2C Payment',
        'manage_options',
        'edit.php?post_type=at_ipn&page=at_settings&tab=b2c'
    );

    add_submenu_page(
        'edit.php?post_type=at_ipn',
        'Buy Airtime',
        '> Buy Airtime',
        
        'manage_options',
        'edit.php?post_type=at_ipn&page=at_settings&tab=airtime'
    );

    add_submenu_page(
        'edit.php?post_type=at_ipn',
        'Callback URLs',
        'Callback URLs',
        'manage_options',
        'at_callbacks',
        'at_admin_cb_page'
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
        at_mpesa_options_page_html();
    }
}

// Redirect to plugin configuration page
function at_admin_settings_page()
{ ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1><?php
        global $at_active_tab;
        $at_active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'apis'; ?>

        <h2 class="nav-tab-wrapper"><?php do_action('at_settings_tab');?></h2>
        <?php do_action('at_settings_content');?>
        <h3>Wallet Balance: KSH <?php echo at_wallet_balance(); ?></h3>
    </div><?php
}

add_action('at_settings_tab', 'at_apis_tab', 1);
function at_apis_tab()
{
    global $at_active_tab; ?>
	<a class="nav-tab <?php echo $at_active_tab == 'apis' || '' ? 'nav-tab-active' : ''; ?>" href="<?php echo admin_url('edit.php?post_type=at_ipn&page=at_settings&tab=apis'); ?>"><?php _e('Welcome', 'woocommerce');?> </a>
    <a class="nav-tab <?php echo $at_active_tab == 'sms' || '' ? 'nav-tab-active' : ''; ?>" href="<?php echo admin_url('edit.php?post_type=at_ipn&page=at_settings&tab=sms'); ?>"><?php _e('Send SMS', 'woocommerce');?> </a>
    <a class="nav-tab <?php echo $at_active_tab == 'b2c' || '' ? 'nav-tab-active' : ''; ?>" href="<?php echo admin_url('edit.php?post_type=at_ipn&page=at_settings&tab=b2c'); ?>"><?php _e('B2C Payment', 'woocommerce');?> </a>
	<a class="nav-tab <?php echo $at_active_tab == 'airtime' || '' ? 'nav-tab-active' : ''; ?>" href="<?php echo admin_url('edit.php?post_type=at_ipn&page=at_settings&tab=airtime'); ?>"><?php _e('Buy Airtime', 'woocommerce');?> </a>
	<?php
}

add_action('at_settings_content', 'at_apis_render_page');
function at_apis_render_page()
{
   global $at_active_tab;
    if ('' || 'apis' == $at_active_tab) { ?>
        <h3><?php _e('Welcome', 'woocommerce');?></h3>
        <p>Explore the AT APIs available for use, for your convenience.</p>
        <!-- Put your content here --><?php 
    } elseif ('sms' == $at_active_tab) {
        if (isset($_POST['sms_phone'])) {
            $recipients = strip_tags(trim($_POST['sms_phone']));
            $message    = strip_tags(trim($_POST['sms_message']));

            $phones     = array();
            if (strpos(',', $recipients) !== false) {
                $phones = explode(',', $recipients);
            } else {
                $phones = $recipients;
            }

            try {
                // Thats it, hit send and we'll take care of the rest
                $result = at_sms($phones, $message);

                echo '<div class="notice notice-'.$result['status'].' is-dismissible">
                    <p>'.$result['data']->SMSMessageData->Message.'.</p>
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
                        <label for="at_shortcode">Phone number(s)</label>
                    </th>
                    <td>
                        <input class="regular-text" type="tel" id="at_shortcode" name="sms_phone" value="+254"/>
                        <br><small>Separate multiple phone numbers with commas</small>
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
    } elseif ('b2c' == $at_active_tab) {
        if (isset($_POST['b2c_phone'])) {
            $payments   = $AT->payments();

            $recipients = strip_tags(trim($_POST['b2c_phone']));
            $currency   = strip_tags(trim($_POST['b2c_currency']));
            $amount     = strip_tags(trim($_POST['b2c_amount']));
            $product    = strip_tags(trim($_POST['b2c_product']));

            $phones     = array();
            if (strpos(',', $recipients) !== false) {
                $numbers = explode(',', $recipients);

                foreach ($numbers as $number) {
                    $phones[] = array(
                        "phoneNumber"   => $number,
                        "currencyCode"  => $currency,
                        "amount"        => round($amount),
                        "name"          => $number,
                        "metadata"      => array(
                            "nothing"   => "no data"
                        )
                    );
                }
            } else {
                $phones[] = array(
                    "phoneNumber"   => $recipients,
                    "currencyCode"  => $currency,
                    "amount"        => round($amount),
                    "name"          => $recipients,
                    "metadata"      => array(
                        "nothing"   => "no data"
                    )
                );
            }

            try {
                // Thats it, hit send and we'll take care of the rest
                $result = $payments->mobileB2C(
                    array(
                        "productName" => $product,
                        "recipients" => $recipients,
                    )
                );

                echo '<div class="notice notice-'.$result['status'].' is-dismissible">
                    <p>'.ucfirst($result['data']).'.</p>
                </div>';
            } catch (Exception $e) {
                echo '<div class="notice notice-error is-dismissible">
                    <p>'.ucfirst($e->getMessage()).'.</p>
                </div>';
            }

        } ?>

        <h3><?php _e('Send to phone', 'woocommerce');?></h3>
        <form method="post" action="">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">
                        <label for="at_shortcode">Phone number(s)</label>
                    </th>
                    <td>
                        <input class="regular-text" type="tel" id="at_shortcode" name="b2c_phone" value="+254"/>
                        <br><small>Separate multiple phone numbers with commas</small>
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
                    <th scope="row">
                        <label for="at_shortcode">Product/Description</label>
                    </th>
                    <td>
                        <input class="regular-text" type="text" id="b2c_product" name="b2c_product">
                        <input class="regular-text" type="hidden" name="b2c_currency" value="KSH">
                    </td>
                </tr>
                <tr valign="top">
            </table>
            <?php submit_button('Send now');?>
        </form><?php
    } elseif ('airtime' == $at_active_tab) {
        if (isset($_POST['airtime_phone'])) {
            $airtime      = $AT->airtime();

            $recipients = strip_tags(trim($_POST['airtime_phone']));
            $currency   = strip_tags(trim($_POST['airtime_currency']));
            $amount     = strip_tags(trim($_POST['airtime_amount']));

            $phones     = array();
            if (strpos(',', $recipients) !== false) {
                $numbers = explode(',', $recipients);

                foreach ($numbers as $number) {
                    $phones[] = array(
                        "phoneNumber"   => $number,
                        "currencyCode"  => $currency,
                        "amount"        => round($amount)
                    );
                }

            } else {
                $phones[] = array(
                    "phoneNumber"   => $recipients,
                    "currencyCode"  => $currency,
                    "amount"        => round($amount)
                );
            }

            try {
                // Thats it, hit send and we'll take care of the rest
                $result =  $airtime->send(array(
                    "recipients" => $phones
                ));

                echo '<div class="notice notice-'.$result['status'].' is-dismissible">
                    <p>'.ucfirst($result['data']).'.</p>
                </div>';
            } catch (Exception $e) {
                echo '<div class="notice notice-error is-dismissible">
                    <p>'.ucfirst($e->getMessage()).'.</p>
                </div>';
            }

        } ?>

        <h3><?php _e('Buy airtime for one or more phone numbers.', 'woocommerce');?></h3>
        <form method="post" action="">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">
                        <label for="at_shortcode">Phone number(s)</label>
                    </th>
                    <td>
                        <input class="regular-text" type="tel" id="at_shortcode" name="airtime_phone" value="+254"/>
                        <br><small>Separate multiple phone numbers with comma</small>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="at_shortcode">Amount to buy</label>
                    </th>
                    <td>
                        <input class="regular-text" type="number" id="at_shortcode" name="airtime_amount" value="100"/>
                        <input class="regular-text" type="hidden" name="airtime_currency" value="KSH">
                    </td>
                </tr>
                <tr valign="top">
            </table>
            <?php submit_button('Buy now');?>
        </form><?php
    }
}



// Redirect to plugin configuration page
function at_admin_cb_page()
{ ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1><?php
        global $at_active_tab;
        $at_active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'apis'; ?>

        <h2 class="nav-tab-wrapper"><?php do_action('at_callbacks_tab');?></h2>
        <?php do_action('at_callbacks_content');?>
    </div><?php
}

add_action('at_callbacks_tab', 'at_apis_cb_tab', 1);
function at_apis_cb_tab()
{
    global $at_active_tab; ?>
	<a class="nav-tab <?php echo $at_active_tab == 'apis' || '' ? 'nav-tab-active' : ''; ?>" href="<?php echo admin_url('edit.php?post_type=at_ipn&page=at_callbacks&tab=apis'); ?>"><?php _e('Welcome', 'woocommerce');?> </a>
    <a class="nav-tab <?php echo $at_active_tab == 'sms' || '' ? 'nav-tab-active' : ''; ?>" href="<?php echo admin_url('edit.php?post_type=at_ipn&page=at_callbacks&tab=sms'); ?>"><?php _e('SMS', 'woocommerce');?> </a>
    <a class="nav-tab <?php echo $at_active_tab == 'b2c' || '' ? 'nav-tab-active' : ''; ?>" href="<?php echo admin_url('edit.php?post_type=at_ipn&page=at_callbacks&tab=b2c'); ?>"><?php _e('B2C', 'woocommerce');?> </a>
	<a class="nav-tab <?php echo $at_active_tab == 'airtime' || '' ? 'nav-tab-active' : ''; ?>" href="<?php echo admin_url('edit.php?post_type=at_ipn&page=at_callbacks&tab=airtime'); ?>"><?php _e('Airtime', 'woocommerce');?> </a>
	<?php
}

add_action('at_callbacks_content', 'at_apis_cb_render_page');
function at_apis_cb_render_page()
{
    $username = at_option('username');
    $apiKey   = at_option('key');
    $AT       = new AfricasTalking\SDK\AfricasTalking($username, $apiKey);
    
    global $at_active_tab;
    if ('' || 'apis' == $at_active_tab) { ?>
        <h3><?php _e('Welcome', 'woocommerce');?></h3>
        <p>Explore the AT APIs available for use, for your convenience.</p>
        <!-- Put your content here --><?php 
    } elseif ('sms' == $at_active_tab) { ?>
        <h3><?php _e('SMS Callback URLs', 'woocommerce');?></h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">
                        <label for="at_shortcode">Status Callback</label>
                    </th>
                    <td>
                        <code><?php echo home_url('africastalking/sms/?action=status'); ?></code>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="at_shortcode">Validation Callback</label>
                    </th>
                    <td>
                        <code><?php echo home_url('africastalking/sms/?action=validate'); ?></code>
                    </td>
                </tr>
            </table>
        <?php
    } elseif ('b2c' == $at_active_tab) { ?>
        <h3><?php _e('B2C Callback URLs', 'woocommerce');?></h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">
                        <label for="at_shortcode">Status Callback</label>
                    </th>
                    <td>
                        <code><?php echo home_url('africastalking/b2c/?action=status'); ?></code>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="at_shortcode">Validation Callback</label>
                    </th>
                    <td>
                        <code><?php echo home_url('africastalking/b2c/?action=validate'); ?></code>
                    </td>
                </tr>
            </table>
        <?php
    } elseif ('airtime' == $at_active_tab) { ?>
        <h3><?php _e('Airtime Callback URLs', 'woocommerce');?></h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">
                        <label for="at_shortcode">Status Callback</label>
                    </th>
                    <td>
                        <code><?php echo home_url('africastalking/airtime/?action=status'); ?></code>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="at_shortcode">Validation Callback</label>
                    </th>
                    <td>
                        <code><?php echo home_url('africastalking/airtime/?action=validate'); ?></code>
                    </td>
                </tr>
            </table>
        <?php
    }
}
