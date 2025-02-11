<?php

function handl_integromat_webhook_url($url_set){
	if (!$url_set){
		return get_option( 'handl_integromat_url' );
	}
	return $url_set;
}
add_filter('handl_webhook_url_set', 'handl_integromat_webhook_url', 10, 1);

function integramat_process_data($data){
	SendDataToIntegromat($data);
}
add_action( 'handl_post_data_to', 'integramat_process_data', 10, 1 );


function handl_integromat_url(){
	register_setting( 'handl-utm-grabber-settings-group', 'handl_integromat_url' );
}
add_action( 'admin_init', 'handl_integromat_url' );

function handl_add_integromat_url(){
	global $handl_fields_disabled;
	?>
	<tr>
		<th scope='row'>Integromat Webhook URL</th>
		<td>
			<fieldset>
				<legend class='screen-reader-text'>
					<span>Integromat Webhook URL</span>
				</legend>
				<label for='handl_integromat_url'>
					<input style="width: 500px" name='handl_integromat_url' id='handl_integromat_url' type='text' value='<?php print get_option( 'handl_integromat_url' ) ? get_option( 'handl_integromat_url' ) : '' ?>' <?php print $handl_fields_disabled;?>/>
				</label>
			</fieldset>
		</td>
	</tr>
	<?php
}
add_filter("insert_rows_to_handl_options", "handl_add_integromat_url", 10);

if (!function_exists('SendDataToIntegromat')) {
	function SendDataToIntegromat( $data ) {
		if ( $integromat_url = get_option( 'handl_integromat_url' ) ) {
			$response = Requests::post( $integromat_url, array(), $data );
			//add_option( 'hug_integoramat_log', $response, '', 'yes' ) or update_option( 'hug_integoramat_log', $response );
		}
	}
}