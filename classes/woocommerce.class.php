<?php

class MailsterWooCommerce {

	private $plugin_dir;
	private $plugin_url;

	public function __construct() {

		$this->plugin_path = plugin_dir_path( MAILSTER_WOOCOMMERCE_FILE );
		$this->plugin_url  = plugin_dir_url( MAILSTER_WOOCOMMERCE_FILE );

		register_activation_hook( MAILSTER_WOOCOMMERCE_FILE, array( &$this, 'activate' ) );
		register_deactivation_hook( MAILSTER_WOOCOMMERCE_FILE, array( &$this, 'deactivate' ) );

		load_plugin_textdomain( 'mailster-woocommerce' );

		add_action( 'plugins_loaded', array( &$this, 'init' ) );

	}


	public function init() {

		if ( ! function_exists( 'mailster' ) ) {

			add_action( 'admin_notices', array( &$this, 'notice' ) );
			return;

		}

		add_action( 'mailster_add_tags', array( &$this, 'add_custom_tags' ), 10, 2 );

		add_filter( 'mailster_setting_sections', array( &$this, 'settings_tab' ) );
		add_action( 'mailster_section_tab_woocommerce', array( &$this, 'settings' ) );

		add_action( 'add_meta_boxes_product', array( &$this, 'add_mailster_product_metabox' ) );

		add_action( 'save_post', array( &$this, 'save_product' ), 10, 2 );

		add_action( 'woocommerce_checkout_' . mailster_option( 'woocommerce_checkbox_pos', 'after_customer_details' ), array( &$this, 'checkbox' ) );
		add_action( 'woocommerce_' . mailster_option( 'woocommerce_checkbox_pos', 'after_customer_details' ), array( &$this, 'checkbox' ) );

		add_action( 'woocommerce_checkout_update_order_meta', array( &$this, 'on_checkout' ) );
		add_action( 'woocommerce_order_status_completed', array( &$this, 'on_completed' ) );

		add_action( 'woocommerce_email', array( &$this, 'remove_header_and_footer' ) );
		add_filter( 'woocommerce_mail_callback_params', array( &$this, 'mail_callback_params' ), 10, 2 );

		add_filter( 'init', array( &$this, 'add_rewrite_endpoint' ) );
		add_filter( 'woocommerce_account_menu_items', array( &$this, 'account_menu_items' ) );
		add_filter( 'woocommerce_account_newsletter_endpoint', array( &$this, 'endpoint' ) );

		add_filter( 'admin_menu', array( &$this, 'admin_menu' ) );

		add_filter( 'updated_user_meta', array( &$this, 'persistent_cart_update' ), 10, 4 );
		add_filter( 'deleted_user_meta', array( &$this, 'persistent_cart_delete' ), 10, 4 );

		add_filter( 'mailster_action_hooks', array( &$this, 'add_autoresponder_hook' ) );

		add_filter( 'mailster_cron_worker', array( &$this, 'check_abandoned_carts' ) );

		mailster_register_dynamic_post_type(
			'woo_abondon_cart_item',
			array(
				'labels' => array(
					'name'          => 'Abandon Cart Items',
					'singular_name' => 'Abandon Cart Item',
				),
			),
			array( &$this, 'get_abondon_cart_item' )
		);

	}

	public function check_abandoned_carts() {

		$abandoned_carts = $this->get_abandoned_carts();

	}

	public function get_abandoned_carts($user_id = null) {

		global $wpdb;

		$sql = "SELECT SQL_CALC_FOUND_ROWS `cart`.umeta_id AS `ID`, `cart`.user_id AS `user_id`, `cart`.meta_value AS `cart`, `timestamp`.meta_value AS `timestamp` FROM {$wpdb->usermeta} AS cart LEFT JOIN {$wpdb->usermeta} AS `timestamp` ON `timestamp`.user_id = cart.user_id WHERE cart.meta_key = %s AND `timestamp`.meta_key = %s AND cart.meta_value != %s";

		$blog_id = get_current_blog_id();
		$items   = $wpdb->get_results( $wpdb->prepare( $sql, '_woocommerce_persistent_cart_' . $blog_id, '_woocommerce_persistent_cart_' . $blog_id . '_timestamp', 'a:1:{s:4:"cart";a:0:{}}' ) );

		foreach ( $items as $i => $item ) {
			$items[ $i ]->cart = maybe_unserialize( $items[ $i ]->cart )['cart'];
		}

	}

	public function add_custom_tags() {

	}

	public function add_autoresponder_hook( $hooks ) {

		$hooks['mailster_wc_abandoned_cart'] = esc_html__( 'Abandoned Cart', 'mailster-woocommerce' );

		return $hooks;
	}

	public function persistent_cart_update( $meta_id, $object_id, $meta_key, $meta_value ) {
		if ( '_woocommerce_persistent_cart_' . get_current_blog_id() == $meta_key ) {
			update_user_meta( $object_id, $meta_key . '_timestamp', time() );
		}
	}
	public function persistent_cart_delete( $meta_id, $object_id, $meta_key, $meta_value ) {
		if ( '_woocommerce_persistent_cart_' . get_current_blog_id() == $meta_key ) {
			delete_user_meta( $object_id, $meta_key . '_timestamp' );
		}
	}

	public function get_abondon_cart_item( $offset, $campaign_id, $subscriber_id, $term_ids, $args ) {

		$post            = get_post( 58 - $offset );
		$post->post_link = 'http://Adasd';
		return $post;
	}

	public function admin_menu() {
		add_submenu_page( 'woocommerce', esc_html__( 'Newsletter', 'mailster-woocommerce' ), esc_html__( 'Newsletter', 'mailster-woocommerce' ), 'manage_options', 'woocommerce-mailster', array( &$this, 'admin_page' ) );
	}

	public function admin_page() {

		include $this->plugin_path . 'views/admin-page.php';

	}

	public function add_rewrite_endpoint() {
		add_rewrite_endpoint( 'newsletter', EP_PAGES );
	}

	public function endpoint() {

		echo do_shortcode( '[newsletter_signup _form id=1]' );
	}

	public function account_menu_items( $menu_links ) {

		$position   = 5;
		$menu_links = array_slice( $menu_links, 0, $position, true ) +
			array( 'newsletter' => esc_html__( 'Newsletter', 'mailster-woocommerce' ) ) +
			array_slice( $menu_links, $position, null, true );

		return $menu_links;
	}

	public function mail_callback_params( $params, $obj ) {

		if ( ! ( $system_mail = mailster_option( 'system_mail' ) ) ) {
			return $params;
		}

		$class = get_class( $obj );

		// WC_Email_New_Order => new_order
		$template = strtolower( substr( $class, 9 ) );

		$templates = mailster_option( 'woocommerce_templates', array() );

		if ( ! empty( $template ) && array_key_exists( $template, $templates ) && ! empty( $templates[ $template ] ) ) {
			$templatefile = $templates[ $template ];
		} else {
			$templatefile = ! empty( $templates['other'] ) ? $templates['other'] : null;
		}

		if ( $templatefile ) {
			add_filter(
				'mailster_wp_mail_template_file',
				function( $file ) use ( $templatefile ) {
					return $templatefile;
				},
				PHP_INT_MAX
			);
		}

		return $params;
	}

	public function mailster_lists() {
		$lists = mailster( 'lists' )->get();

		$mailster_lists[''] = esc_html__( 'Select a list', 'mailster-woocommerce' );

		foreach ( $lists as $list ) {
			$mailster_lists[ $list->ID ] = $list->name;
		}

		return $mailster_lists;
	}

	/**
	 *
	 *
	 * @param unknown $settings
	 * @return unknown
	 */
	public function settings_tab( $settings ) {

		$position = 5;
		$settings = array_slice( $settings, 0, $position, true ) +
			array( 'woocommerce' => 'WooCommerce' ) +
			array_slice( $settings, $position, null, true );

		return $settings;
	}


	public function add_mailster_product_metabox() {

		add_meta_box( 'mailster_product_metabox', 'Mailster', array( &$this, 'mailster_product_metabox' ), 'product', 'side', 'core' );
	}

	public function mailster_product_metabox( $post ) {

		wp_nonce_field( 'mailster_woocommerce_lists_nonce', 'mailster_woocommerce_lists_nonce' );
		$selected_lists = get_post_meta( $post->ID, '_mailster_lists', true );

		if ( ! is_array( $selected_lists ) ) {
			$selected_lists = mailster_option( 'woocommerce_lists' );
		}

		?>
		<?php mailster( 'lists' )->print_it( null, null, 'mailster_lists', false, $selected_lists ); ?>
		<p class="description">
			<?php esc_html_e( 'Customers who purchases this product will get added to these lists', 'mailster-woocommerce' ); ?>
		</p>

		<?php
	}

	public function save_product( $post_id, $post ) {

		if ( empty( $post ) || $post->post_type != 'product' ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST['mailster_woocommerce_lists_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['mailster_woocommerce_lists_nonce'], 'mailster_woocommerce_lists_nonce' ) ) {
			return;
		}

		$lists = isset( $_POST['mailster_lists'] ) ? (array) $_POST['mailster_lists'] : array();
		update_post_meta( $post_id, '_mailster_lists', $lists );

	}

	public function metabox() {

		include $this->plugin_path . 'views/metabox.php';

	}


	public function settings() {

		include $this->plugin_path . 'views/settings.php';

	}

	public function on_checkout( $order_id ) {

		if ( ( 'checkbox' == mailster_option( 'woocommerce_type' ) && isset( $_POST['mailster_signup'] ) )
			|| 'auto' == mailster_option( 'woocommerce_type' ) ) {

			if ( 'completed' == mailster_option( 'woocommerce_action' ) ) {
				add_post_meta( $order_id, 'mailster_signup', true, true );
			} elseif ( 'created' == mailster_option( 'woocommerce_action' ) ) {
				$this->subscribe( $order_id );
			}
		}

	}


	public function on_completed( $order_id ) {

		if ( get_post_meta( $order_id, 'mailster_signup', true ) ) {
			$this->subscribe( $order_id );
		}
	}

	public function subscribe( $order_id ) {

		$order = new WC_Order( $order_id );

		if ( ! $order ) {
			return;
		}
		if ( is_null( $order->get_billing_email() ) ) {
			return;
		}

		$default_lists = mailster_option( 'woocommerce_lists', array() );

		$email     = $order->get_billing_email();
		$firstname = $order->get_billing_first_name();
		$lastname  = $order->get_billing_last_name();

		foreach ( $order->get_items() as $item ) {

			$product_lists = get_post_meta( $item['product_id'], '_mailster_lists', true );

			if ( is_array( $product_lists ) ) {
				$default_lists = array_merge( $default_lists, $product_lists );
			}
		}

		if ( $subscriber = mailster( 'subscribers' )->get_by_mail( $email ) ) {
			$subscriber_id = $subscriber->ID;
		} else {

			$user_data = array(
				'firstname' => $firstname,
				'lastname'  => $lastname,
				'email'     => $email,
				'referer'   => sprintf( '<a href="' . admin_url( 'post.php?post=%d&action=edit' ) . '">%s #%d</a>', $order->get_id(), esc_html__( 'Order', 'mailster-woocommerce' ), $order->get_id() ),
				'status'    => mailster_options( 'woocommerce-double-opt-in' ) ? 0 : 1,
			);

			$synclist = mailster_option( 'sync' ) ? mailster_option( 'synclist', array() ) : array();
			foreach ( $synclist as $usermeta => $field ) {
				if ( is_callable( array( $order, 'get_' . $field ) ) ) {

					if ( $value = call_user_method( 'get_' . $field, $order ) ) {
						$user_data[ $usermeta ] = $value;
					}
				}
			}

			$subscriber_id = mailster( 'subscribers' )->add( $user_data );
		}

		if ( ! empty( $default_lists ) && ! is_wp_error( $subscriber_id ) ) {
			$lists   = array_unique( $default_lists );
			$success = mailster( 'subscribers' )->assign_lists( $subscriber_id, $lists, false );
		}
	}

	public function checkbox() {

		if ( 'auto' == mailster_option( 'woocommerce_type' ) ) {
			return;
		}

		if ( 'checkbox' == mailster_option( 'woocommerce_type' ) ) {
			if ( mailster_option( 'woocommerce-skip-user' ) && is_user_logged_in() && $subscriber = mailster( 'subscribers' )->get_by_wpid( get_current_user_id() ) ) {
				echo '<div class="mailster-signup"><input id="wc_mailster_signup" name="mailster_signup" type="hidden" value="1"></div>';
			} else {
				echo '<div class="mailster-signup"><label for="wc_mailster_signup" class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox"><input id="wc_mailster_signup" name="mailster_signup" type="checkbox" ' . checked( mailster_option( 'woocommerce_checkbox' ), true, false ) . ' class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox"> <span>' . mailster_option( 'woocommerce_label' ) . '</span></label></div>';
			}
		}

	}


	public function remove_header_and_footer( $wooEmailObj ) {
		if ( mailster_option( 'system_mail' ) ) {
			remove_action( 'woocommerce_email_header', array( $wooEmailObj, 'email_header' ) );
			remove_action( 'woocommerce_email_footer', array( $wooEmailObj, 'email_footer' ) );
		}
	}

	public function activate() {
		if ( function_exists( 'mailster' ) ) {

			$defaults = array(
				'woocommerce_action'        => 'created',
				'woocommerce_type'          => 'checkbox',
				'woocommerce_checkbox'      => false,
				'woocommerce_checkbox_pos'  => 'after_checkout_billing_form',
				'woocommerce_label'         => esc_html__( 'Subscribe to our newsletter', 'mailster-woocommerce' ),
				'woocommerce_templates'     => array(
					'new_order'                 => 0,
					'cancelled_order'           => 0,
					'failed_order'              => 0,
					'customer_on_hold_order'    => 0,
					'customer_processing_order' => 0,
					'customer_completed_order'  => 0,
					'customer_refunded_order'   => 0,
					'customer_invoice'          => 0,
					'customer_note'             => 0,
					'customer_reset_password'   => 0,
					'customer_new_account'      => 0,
					'other'                     => 0,
				),
				'woocommerce-skip-user'     => false,
				'woocommerce-double-opt-in' => false,
			);

			$mailster_options = mailster_options();

			foreach ( $defaults as $key => $value ) {
				if ( ! isset( $mailster_options[ $key ] ) ) {
					mailster_update_option( $key, $value );
				}
			}
		}

	}


	public function deactivate() {
		if ( function_exists( 'mailster' ) ) {
		}
	}


	public function notice() {
		$msg = sprintf( esc_html__( 'You have to enable the %s to use Mailster for WooCommerce!', 'mailster-woocommerce' ), '<a href="https://evp.to/mailster?utm_campaign=wporg&utm_source=Mailster+for+WooCommerce&utm_medium=plugin">Mailster Newsletter Plugin</a>' );
		?>
		<div class="error"><p><strong><?php echo $msg; ?></strong></p></div>
		<?php

	}

}
