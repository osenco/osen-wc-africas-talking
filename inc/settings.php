<?php
/**
 * @package AT
 * @subpackage Admin Settings Page
 * @author Mauko Maunde <mauko@osen.co.ke>
 * @author Brightone Mwasaru <bmwasaru@gmail.com>
 * @author Johnes Mecha <jmecha09@gmail.com>
 * @version 1.8
 * @since 1.8
 * @license See LICENSE
 */
add_action( 'admin_init', 'at_settings_init' );
function at_settings_init() {
    register_setting( 'africastalking_options', 'at_mpesa_options' );
    
    add_settings_section( 'at_section_mpesa', __( '', 'africastalking_options' ), '', 'africastalking_options' );
    
    add_settings_field(
        'shortcode',
        __( 'AT Shortcode', 'africastalking_options' ),
        'at_fields_at_mpesa_shortcode_cb',
        'africastalking_options',
        'at_section_mpesa',
        [
        'label_for' => 'shortcode',
        'class' => 'at_row',
        'at_custom_data' => 'custom',
        ]
    );

    add_settings_field(
        'username',
        __( 'AT Username', 'africastalking_options' ),
        'at_fields_at_mpesa_cs_cb',
        'africastalking_options',
        'at_section_mpesa',
        [
        'label_for' => 'username',
        'class' => 'at_row',
        'at_custom_data' => 'custom',
        ]
    );
    
    add_settings_field(
        'key',
        __( 'AT API Key', 'africastalking_options' ),
        'at_fields_at_mpesa_ck_cb',
        'africastalking_options',
        'at_section_mpesa',
        [
        'label_for' => 'key',
        'class' => 'at_row',
        'at_custom_data' => 'custom',
        ]
    );
}

function at_section_at_mpesa_cb( $args ) {
    $options = get_option( 'at_mpesa_options', ['env'=>'sandbox'] ); ?>
    
    <?php
}

function at_fields_at_mpesa_shortcode_cb( $args ) {
    $options = get_option( 'at_mpesa_options' );
    ?>
    <input type="text" id="<?php echo esc_attr( $args['label_for'] ); ?>"
        data-custom="<?php echo esc_attr( $args['at_custom_data'] ); ?>"
        name="at_mpesa_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
        value="<?php echo esc_attr( isset( $options[ $args['label_for'] ] ) ? $options[ $args['label_for'] ] : '' ); ?>"
        class="regular-text">
    <?php
}

function at_fields_at_mpesa_ck_cb( $args ) {
    $options = get_option( 'at_mpesa_options' );
    ?>
    <input type="text" id="<?php echo esc_attr( $args['label_for'] ); ?>"
        data-custom="<?php echo esc_attr( $args['at_custom_data'] ); ?>"
        name="at_mpesa_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
        value="<?php echo esc_attr( isset( $options[ $args['label_for'] ] ) ? $options[ $args['label_for'] ] : '0be438d7976ba7613238370ea8f84e3eaa93b23e59cb0d132a1aa72260bfc795' ); ?>"
        class="regular-text">
    <?php
}

function at_fields_at_mpesa_cs_cb( $args ) {
    $options = get_option( 'at_mpesa_options' );
    ?>
    <input type="text" id="<?php echo esc_attr( $args['label_for'] ); ?>"
        data-custom="<?php echo esc_attr( $args['at_custom_data'] ); ?>"
        name="at_mpesa_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
        value="<?php echo esc_attr( isset( $options[ $args['label_for'] ] ) ? $options[ $args['label_for'] ] : '' ); ?>"
        class="regular-text">
    <?php
}
 
/**
 * top level menu:
 * callback functions
 */
function at_mpesa_options_page_html() {
    // check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    
    // add error/update messages
    
    // check if the user have submitted the settings
    // wordpress will add the "settings-updated" $_GET parameter to the url
    if ( isset( $_GET['settings-updated'] ) ) {
    // add settings saved message with the class of "updated"
        add_settings_error( 'at_messages', 'at_message', __( 'AT Settings Updated', 'africastalking_options' ), 'updated' );
    }
    
    // show error/update messages
    settings_errors( 'at_messages' );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
            <?php
                // output security fields for the registered setting "africastalking_options"
                settings_fields( 'africastalking_options' );
                // output setting sections and their fields
                // (sections are registered for "africastalking_options", each field is registered to a specific section)
                do_settings_sections( 'africastalking_options' );
                // output save settings button
                submit_button( 'Save AT Settings' );
            ?>
        </form>
    </div>
    <?php
}