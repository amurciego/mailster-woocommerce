<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( isset( $_GET['tab'] ) ) {
	$tab = sanitize_title( $_GET['tab'] );
} else {
	$tab = 'general';
}
?>
	<div class="wrap">
<form method="post" id="mainform" action="" enctype="multipart/form-data">
		<h2 class="nav-tab-wrapper">
			<a href="<?php echo admin_url( 'admin.php?page=woocommerce-mailster&tab=general' ); ?>" class="nav-tab <?php echo $tab == 'general' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'General', 'mailster-woocommerce' ); ?></a>
			<a href="<?php echo admin_url( 'admin.php?page=woocommerce-mailster&tab=account' ); ?>" class="nav-tab <?php echo $tab == 'account' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Account', 'mailster-woocommerce' ); ?></a>
			<a href="<?php echo admin_url( 'admin.php?page=woocommerce-mailster&tab=abandon-cart' ); ?>" class="nav-tab <?php echo $tab == 'abandon-cart' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Abandon Cart', 'mailster-woocommerce' ); ?></a>
		</h2>

<?php if ( 'general' == $tab ) : ?>

	<?php include 'general.php'; ?>

<?php elseif ( 'account' == $tab ) : ?>

	<?php include 'account.php'; ?>

<?php elseif ( 'abandon-cart' == $tab ) : ?>

	<?php include 'abandon-cart.php'; ?>

<?php endif; ?>

	<?php submit_button( esc_html__( 'Save changes', 'mailster-woocommerce' ), 'primary', 'submit', true, null ); ?>
</div>

</form>

	</div>


