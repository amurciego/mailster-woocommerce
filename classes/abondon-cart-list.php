<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Mailster_Woo_Abandon_Cart_Table extends WP_List_Table {

	public $total_items;
	public $total_pages;
	public $per_page;

	public function __construct() {

		parent::__construct(
			array(
				'singular' => esc_html__( 'Subscriber', 'mailster' ), // singular name of the listed records
				'plural'   => esc_html__( 'Subscribers', 'mailster' ), // plural name of the listed records
				'ajax'     => false, // does this table support ajax?
			)
		);

		add_action( 'admin_footer', array( &$this, 'script' ) );
		add_filter( 'manage_newsletter_page_mailster_subscribers_columns', array( &$this, 'get_columns' ) );

	}



	public function script() {
	}


	public function no_items() {

		echo 'no';

	}

	public function get_columns() {

		return array(
			'cb'        => 'Custsomer',
			'user'        => 'Customer',
			'item_count' => 'Items',
			'order_total' => 'Order Total',
			'date'        => 'Abandoned Date',
			'status'      => 'Cart Status',
		);

	}


	/**
	 *
	 *
	 * @param unknown $item
	 * @param unknown $column_name
	 * @return unknown
	 */
	public function column_default( $item, $column_name ) {

		$cart       = maybe_unserialize( $item->cart )['cart'];
		$user       = get_userdata( $item->user_id );
		$subscriber = mailster( 'subscribers' )->get_by_wpid( $item->user_id );
		switch ( $column_name ) {

			case 'user':
				echo '<strong>' . $user->display_name . '</strong> ';
				echo ' <a href="' . admin_url( 'edit.php?post_type=newsletter&page=mailster_subscribers&ID=' . $subscriber->ID ) . '" class="dashicons dashicons-buddicons-pm"></a>';
				echo ' <a href="' . admin_url( 'user-edit.php?user_id=' . $user->ID ) . '" class="dashicons dashicons-admin-users"></a>';

				echo '<pre>' . print_r( $cart, true ) . '</pre>';
				break;

			case 'item_count':
				$total = 0;
				foreach ( $cart as $key => $cart_item ) {
					$total += $cart_item['quantity'];
				}
				echo  $total;
				break;

			case 'order_total':
				$total = 0;
				foreach ( $cart as $key => $cart_item ) {
					$total += isset($cart_item['line_total']) ? $cart_item['line_total'] : 0;
				}
				echo wc_price( $total );
				break;

			case 'date':
				echo date( mailster( 'helper' )->timeformat(), $item->timestamp ) . ', ' . sprintf( esc_html__( '%s ago', 'mailster-woocommerce' ), human_time_diff( $item->timestamp ) );
				break;
			default:
				return '<pre>' . print_r( $item, true ) . '</pre>'; // Show the whole array for troubleshooting purposes
		}
	}


	/**
	 *
	 *
	 * @return unknown
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'name' => array( 'name', false ),
		);
		return $sortable_columns;
	}


	/**
	 *
	 *
	 * @return unknown
	 */
	public function get_bulk_actions() {
		$actions = array(
			'delete' => esc_html__( 'Delete', 'mailster' ),
		);

		return $actions;
	}


	/**
	 *
	 *
	 * @param unknown $which (optional)
	 */
	public function bulk_actions( $which = '' ) {

		parent::bulk_actions( $which );

	}


	/**
	 *
	 *
	 * @param unknown $which (optional)
	 */
	public function extra_tablenav( $which = '' ) {}


	/**
	 *
	 *
	 * @param unknown $item
	 * @return unknown
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="subscribers[]" value="%s" class="subscriber_cb" />',
			$item->ID
		);
	}


	/**
	 *
	 *
	 * @param unknown $domain  (optional)
	 * @param unknown $post_id (optional)
	 */
	public function prepare_items( $domain = null, $post_id = null ) {

		global $wpdb;
		$screen   = get_current_screen();
		$columns  = $this->get_columns();
		$hidden   = get_hidden_columns( $screen );
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->per_page = 50;

		$sql = "SELECT SQL_CALC_FOUND_ROWS `cart`.umeta_id AS `ID`, `cart`.user_id AS `user_id`, `cart`.meta_value AS `cart`, `timestamp`.meta_value AS `timestamp` FROM {$wpdb->usermeta} AS cart LEFT JOIN {$wpdb->usermeta} AS `timestamp` ON `timestamp`.user_id = cart.user_id WHERE cart.meta_key = %s AND `timestamp`.meta_key = %s AND cart.meta_value != %s";

		$blog_id = get_current_blog_id();
		$items = $wpdb->get_results( $wpdb->prepare( $sql, '_woocommerce_persistent_cart_' . $blog_id, '_woocommerce_persistent_cart_' . $blog_id . '_timestamp', 'a:1:{s:4:"cart";a:0:{}}' ) );

		//$sql = "SELECT SQL_CALC_FOUND_ROWS sessions.session_id AS ID, subscribers.ID AS subscriber_id, sessions.session_value AS `data`, sessions.session_expiry AS `timestamp` FROM `{$wpdb->prefix}woocommerce_sessions` AS sessions LEFT JOIN `{$wpdb->prefix}mailster_subscribers` AS subscribers ON subscribers.wp_id = sessions.session_key WHERE subscribers.ID IS NOT NULL AND sessions.session_value NOT LIKE '%s:4:\"cart\";s:6:\"a:0:{}\"%';";



		//$items = $wpdb->get_results( $sql );

		error_log( print_r($items, true) );

		$this->items       = $items;
		$this->total_items = $wpdb->get_var( 'SELECT FOUND_ROWS();' );

		$this->total_pages = ceil( $this->total_items / $this->per_page );

		$this->set_pagination_args(
			array(
				'total_items' => $this->total_items,
				'total_pages' => $this->total_pages,
				'per_page'    => $this->per_page,
			)
		);

	}


}
