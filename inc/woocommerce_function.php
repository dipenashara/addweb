<?php
add_action( 'woocommerce_update_cart_action_cart_updated', 'update_existing_cart_item_meta', 9);
function update_existing_cart_item_meta($cart_updated) {

	$cart_totals = isset( $_POST['cart'] ) ? $_POST['cart'] : '';
	if ( sizeof( WC()->cart->get_cart() ) > 0 ) {
		foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
			WC()->cart->cart_contents[ $cart_item_key ]['gift_message'] = $cart_totals[ $cart_item_key ]['gift_message'];
		}
		WC()->cart->set_session();
	}
}

//This is in Order summary. It show Gift Message variable under product name. Same place where Variations are shown.
add_filter( 'woocommerce_get_item_data', 'item_data', 10, 2 );
function item_data( $data, $cart_item ) {
	
    if ( isset( $cart_item['gift_message'] ) ) {
        $data['gift_message'] = array('name' => 'Gift Message', 'value' => $cart_item['gift_message']);
    }
    return $data;
}

// Displaying custom fields in the WooCommerce order and email confirmations
add_action( 'woocommerce_checkout_create_order_line_item', 'custom_order_item_meta', 20, 4 );
function custom_order_item_meta( $item, $cart_item_key, $values, $order ) {
    if ( isset( $values['gift_message'] ) ) {
        $item->update_meta_data( __('Gift Message', 'woocommerce'), $values['gift_message'] );          
    }
}

//Admin grid code start
add_filter( 'manage_edit-product_columns', 'show_product_order',15 );
function show_product_order($columns){

	unset($columns['sku']);

	$arr = array( 'total_orders' => __( 'Total Order(s)') ) ;	
	array_splice( $columns, 7, 0, $arr );

	return $columns;
}

add_action( 'manage_product_posts_custom_column', 'total_orders_product_column_offercode', 10, 2 );
function total_orders_product_column_offercode( $column, $postid ) {
    if ( $column == 'total_orders' ) {
		echo product_base_get_count($postid);
    }
}

function product_base_get_count($postid){
	
	global $woocommerce;
    include_once( $woocommerce->plugin_path() . '/includes/admin/reports/class-wc-admin-report.php');
    include_once( $woocommerce->plugin_path() . '/includes/admin/reports/class-wc-report-sales-by-product.php');

    $reports = new WC_Report_Sales_By_Product();
    $reports->start_date = strtotime('2010-01-01');
    $reports->end_date = strtotime( date( 'Y-m-d', current_time( 'timestamp' ) ) );

    $reports->product_ids = $postid;

    $total_items = absint( $reports->get_order_report_data( array(
        'data' => array(
            '_qty' => array(
                'type'            => 'order_item_meta',
                'order_item_type' => 'line_item',
                'function'        => 'SUM',
                'name'            => 'order_item_count'
            )
        ),
        'where_meta' => array(
            'relation' => 'OR',
            array(
                'type'       => 'order_item_meta',
                'meta_key'   => array( '_product_id', '_variation_id' ),
                'meta_value' => $reports->product_ids,
                'operator'   => 'IN'
            )
        ),
        'query_type'   => 'get_var',
        'filter_range' => true
    ) ) );
    return $total_items;
}
//Admin grid code end

add_action( 'woocommerce_email_after_order_table', 'add_content_specific_email' );
function add_content_specific_email( $order, $sent_to_admin, $plain_text, $email) {
	
  if ( $order->has_status( 'completed' ) || $order->has_status( 'processing' )) { ?>
<h2 style="color:#96588a;display:block;font-family:'Helvetica Neue',Helvetica,Roboto,Arial,sans-serif;font-size:18px;font-weight:bold;line-height:130%;margin:0 0 18px;text-align:left">Discount Code For Next Purchase</h2>
  <p><strong>Note</strong> : This discount code you can use for next purchase but only once time usable.</p>
  <p>Discount Code: <strong>VSORC</strong></p> 
<?php } } 