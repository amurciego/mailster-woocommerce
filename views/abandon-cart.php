<?php if ( ! apply_filters( 'woocommerce_persistent_cart_enabled', true ) ) : ?>

	<div class="error">
		<p><strong><?php esc_html_e( 'WooCommerce Persistent Cart is disabled on your site so Mailster cannot process Abandon Cart Emails! ', 'mailster-woocommerce' ); ?></strong></p>
	</div>

<?php else : ?>

	<?php

	require_once $this->plugin_path . 'classes/abondon-cart-list.php';
	$table = new Mailster_Woo_Abandon_Cart_Table();

	$table->prepare_items();

	$table->views();

	?>
<form method="post" action="" id="subscribers-overview-form">
	<?php $table->display(); ?>
</form>

<?php endif; ?>
