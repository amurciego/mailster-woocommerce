<table class="form-table">
	<tr valign="top">
		<th scope="row"><?php esc_html_e( 'Newsletter Page', 'mailster-woocommerce' ); ?></th>
		<td><label><input type="hidden" name="mailster_options[woocommerce-skip-user]" value="0"><input type="checkbox" name="mailster_options[woocommerce-skip-user]" value="1" <?php checked( mailster_option( 'woocommerce-skip-user' ) ); ?>> <?php esc_html_e( 'Allow users to manage their subscription on the account page.', 'mailster-woocommerce' ); ?></label></td>
	</tr>
	<tr valign="top">
		<th scope="row"><?php esc_html_e( 'Lists', 'mailster-woocommerce' ); ?></th>
		<td>
		<?php esc_html_e( 'Allow users to manage following lists ', 'mailster-woocommerce' ); ?>
		<?php mailster( 'lists' )->print_it( null, null, 'mailster_options[woocommerce_lists]', false, mailster_option( 'woocommerce_lists' ) ); ?>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row"><?php esc_html_e( 'Text', 'mailster-woocommerce' ); ?></th>
		<td><p><textarea class="widefat" name="mailster_options[woocommerce_label]" rows="15"><?php echo esc_textarea( mailster_option( 'woocommerce_label' ) ); ?></textarea></p></td>
	</tr>
	<tr valign="top">
		<th scope="row"><?php esc_html_e( 'Action', 'mailster-woocommerce' ); ?></th>
		<td>
		<?php esc_html_e( 'Add customer as subscriber when order has been ', 'mailster-woocommerce' ); ?>
		<select name="mailster_options[woocommerce_action]">
			<option value="created" <?php selected( mailster_option( 'woocommerce_action' ), 'created' ); ?>><?php esc_html_e( 'created', 'mailster-woocommerce' ); ?></option>
			<option value="completed" <?php selected( mailster_option( 'woocommerce_action' ), 'completed' ); ?>><?php esc_html_e( 'completed', 'mailster-woocommerce' ); ?></option>
		</select>
		</td>
	</tr>
</table>
