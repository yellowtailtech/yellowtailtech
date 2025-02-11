<?php

function handl_affwp_register_user($affiliate_id = 0, $status = '', $args = array() ){

	/** @var AffWP\Affiliate $affiliate */
	$affiliate = affwp_get_affiliate($affiliate_id);
	$user_id = $affiliate->get_user()->ID;

	$fields = generateUTMFields();
	foreach ($fields as $field){
		$cookie_field = isset($_COOKIE[$field]) ? $_COOKIE[$field] : '';
		if ($cookie_field != ''){
			$cookie_value = wp_filter_nohtml_kses( $cookie_field );
			update_user_meta( $user_id, $field, $cookie_value );
		}
	}
}
add_action('affwp_register_user', 'handl_affwp_register_user', 10, 3);

function handl_show_user_profile( $user ) {

	if( ! current_user_can( 'manage_affiliates' ) ) {
		return;
	}

	?>
	<table class="form-table">
		<?php
		$fields = generateUTMFields();
		$i = 0;
		foreach ($fields as $field) {
			if(  $handlValue = get_user_meta( $user->ID, $field, true ) ) {
				if ($i == 0){
					print "<h3>HandL UTM Grabber Fields</h3>";
				}
				print "<tr>
						<th><label>$field</label></th>
						<td>$handlValue</td>
					</tr>";
				$i++;
			}
		}
		?>
	</table>
	<?php
}
add_action( 'edit_user_profile', 'handl_show_user_profile' );