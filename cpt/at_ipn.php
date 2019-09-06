<?php
/**
 * @package Africas Talking For WordPress
 * @subpackage Transactions CPT
 * @author Osen Concepts < hi@osen.co.ke >
 * @since 0.19.04
 */

// Register Custom Post Type
add_action('init', 'africastalking_custom_post_type', 0);
function africastalking_custom_post_type()
{
    $labels = array(
        'name'                  => _x('Africa\'s Talking Transactions', 'Post Type General Name', 'africastalking'),
        'singular_name'         => _x('Transaction', 'Post Type Singular Name', 'africastalking'),
        'menu_name'             => __('Africa\'s Talking', 'africastalking'),
        'name_admin_bar'        => __('Africa\'s Talking IPN', 'africastalking'),
        'archives'              => __('Item Archives', 'africastalking'),
        'attributes'            => __('Item Attributes', 'africastalking'),
        'parent_item_colon'     => __('Parent Item:', 'africastalking'),
        'all_items'             => __('Transactions', 'africastalking'),
        'add_new_item'          => __('Add New Item', 'africastalking'),
        'add_new'               => __('Add New', 'africastalking'),
        'new_item'              => __('New Item', 'africastalking'),
        'edit_item'             => __('Edit Item', 'africastalking'),
        'update_item'           => __('Update Item', 'africastalking'),
        'view_item'             => __('View Item', 'africastalking'),
        'view_items'            => __('View Items', 'africastalking'),
        'search_items'          => __('Search Item', 'africastalking'),
        'not_found'             => __('Not found', 'africastalking'),
        'not_found_in_trash'    => __('Not found in Trash', 'africastalking'),
        'featured_image'        => __('Featured Image', 'africastalking'),
        'set_featured_image'    => __('Set featured image', 'africastalking'),
        'remove_featured_image' => __('Remove featured image', 'africastalking'),
        'use_featured_image'    => __('Use as featured image', 'africastalking'),
        'insert_into_item'      => __('Insert into item', 'africastalking'),
        'uploaded_to_this_item' => __('Uploaded to this item', 'africastalking'),
        'items_list'            => __('Items list', 'africastalking'),
        'items_list_navigation' => __('Items list navigation', 'africastalking'),
        'filter_items_list'     => __('Filter items list', 'africastalking'),
    );

    $args = array(
        'label'               => __('Transaction', 'africastalking'),
        'description'         => __('Africas Talking Transactions IPN', 'africastalking'),
        'labels'              => $labels,
        'supports'            => array(),
        'hierarchical'        => false,
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_position'       => 20,
        'menu_icon'           => 'https://africastalking.com/img/favicons/favicon-16x16.png',
        'show_in_admin_bar'   => false,
        'show_in_nav_menus'   => false,
        'can_export'          => true,
        'has_archive'         => false,
        'exclude_from_search' => true,
        'publicly_queryable'  => false,
        'capability_type'     => 'post',
        'capabilities'        => array('create_posts' => false, 'edit_posts' => true, 'delete_post' => true),
        'map_meta_cap'        => true,
    );
    
    register_post_type('at_ipn', $args);
}

/**
 * A filter to add custom columns and remove built-in
 * columns from the edit.php screen.
 *
 * @access public
 * @param array $columns The existing columns
 * @return array $filtered_columns The filtered columns
 */
add_filter('manage_at_ipn_posts_columns', 'filter_africastalking_payments_table_columns');
function filter_africastalking_payments_table_columns($columns)
{
    //$columns['service_name']         = "Service";
    //$columns['shortcode']         = "Shortcode";
    $columns['reference']   = "Reference";
    $columns['type']        = "Type";
    $columns['amount']      = "Amount";
    $columns['customer']    = "Customer";
    $columns['phone']       = "Phone";
    //$columns['account_number']    = "Account No";
    //$columns['timestamp']         = "Date";
    //$columns['transaction_type']         = "Type";
    unset($columns['title']);
    unset($columns['date']);
    return $columns;
}

/**
 * Render custom column content within edit.php table on event post types.
 *
 * @access public
 * @param String $column The name of the column being acted upon
 * @return void
 */
add_action('manage_at_ipn_posts_custom_column', 'africastalking_payments_table_column_content', 10, 2);
function africastalking_payments_table_column_content($column_id, $post_id)
{
    $order_id = get_post_meta($post_id, '_order_id', true);
    switch ($column_id) {

        case 'service_name':
            echo ($value = get_post_meta($post_id, '_service_name', true)) ? $value : "N/A";
            break;

        case 'shortcode':
            echo ($value = get_post_meta($post_id, '_shortcode', true)) ? $value : "N/A";
            break;

        case 'reference':
            echo ($value = get_post_meta($post_id, '_receipt', true)) ? $value : "N/A";
            break;

        case 'type':
            echo ($value = get_post_meta($post_id, '_type', true)) ? $value : "N/A";
            break;

        case 'timestamp':
            echo ($value = date('M jS, Y \a\t H:i', strtotime(get_post_meta($post_id, '_timestamp', true)))) ? $value : "N/A";
            break;

        case 'transaction_type':
            echo ($value = get_post_meta($post_id, '_transaction_type', true)) ? $value : "N/A";
            break;

        case 'amount':
            echo ($value = get_post_meta($post_id, '_amount', true)) ? $value : "N/A";
            break;

        case 'customer':
            echo ($value = get_post_meta($post_id, '_customer', true)) ? $value : "N/A";
            break;

        case 'phone':
            echo ($value = get_post_meta($post_id, '_phone', true)) ? $value : "N/A";
            break;

        case 'account_number':
            echo ($value = get_post_meta($post_id, '_account_number', true)) ? $value : "N/A";
            break;
    }
}

/**
 * Make custom columns sortable.
 *
 * @access public
 * @param array $columns The original columns
 * @return array $columns The filtered columns
 */
add_filter('manage_edit-at_ipn_sortable_columns', 'africastalking_payments_columns_sortable');
function africastalking_payments_columns_sortable($columns)
{
    $columns['service_name']     = "Service";
    $columns['shortcode']        = "Shortcode";
    $columns['reference']        = "Reference";
    $columns['type']             = "Type";
    $columns['transaction']      = "Transaction ID";
    $columns['timestamp']        = "Date";
    $columns['transaction_type'] = "Type";
    $columns['amount']           = "Amount";
    $columns['customer']         = "Customer";
    $columns['phone']            = "Phone";
    $columns['account_number']   = "Account No";
    return $columns;
}

/**
 * Remove actions from columns.
 *
 * @access public
 * @param array $actions Actions to remove
 */
add_filter('post_row_actions', 'africastalking_remove_row_actions', 10, 1);
function africastalking_remove_row_actions($actions)
{
    if (get_post_type() === 'at_ipn') {
        unset($actions['edit']);
        unset($actions['view']);
        unset($actions['inline hide-if-no-js']);
    }

    return $actions;
}
