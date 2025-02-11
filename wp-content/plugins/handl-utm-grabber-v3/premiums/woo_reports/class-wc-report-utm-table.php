<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WC_Report_UTM_Table extends WP_List_Table {

	protected $max_items;

	protected $start_date;
	protected $end_date;

	/**
	 * Constructor.
	 */
	public function __construct($args = array() ) {

		$args = wp_parse_args(
			$args,
			array(
				'plural'   => '',
				'singular' => '',
				'ajax'     => false,
				'screen'   => null,
			)
		);

		parent::__construct(
			$args
		);
	}

	/**
	 * Output the report.
	 */
	public function output_report() {

		$ranges        = array(
			'year'       => __( 'Year', 'woocommerce' ),
			'last_month' => __( 'Last month', 'woocommerce' ),
			'month'      => __( 'This month', 'woocommerce' ),
			'7day'       => __( 'Last 7 days', 'woocommerce' ),
		);
		$current_range = ! empty( $_GET['range'] ) ? sanitize_text_field( wp_unslash( $_GET['range'] ) ) : '7day';

		if ( ! in_array( $current_range, array( 'custom', 'year', 'last_month', 'month', '7day' ), true ) ) {
			$current_range = '7day';
		}

		$this->calculate_current_range( $current_range );
		$this->prepare_items();

		echo '<h1>' . esc_html__( $this->_args['title'], 'woocommerce' );
		echo '</h1>';


		echo '<div id="poststuff" class="woocommerce-reports-wide">';
		echo '<div class="postbox">';
		?>
		<div class="stats_range">
			<?php $this->get_export_button(); ?>
			<ul>
				<?php
				foreach ( $ranges as $range => $name ) {
					echo '<li class="' . ( $current_range == $range ? 'active' : '' ) . '"><a href="' . esc_url( remove_query_arg( array( 'start_date', 'end_date' ), add_query_arg( 'range', $range ) ) ) . '">' . esc_html( $name ) . '</a></li>';
				}
				?>
				<li class="custom <?php echo ( 'custom' === $current_range ) ? 'active' : ''; ?>">
					<?php esc_html_e( 'Custom:', 'woocommerce' ); ?>
					<form method="GET">
						<div>
							<?php
							// Maintain query string.
							foreach ( $_GET as $key => $value ) {
								if ( is_array( $value ) ) {
									foreach ( $value as $v ) {
										echo '<input type="hidden" name="' . esc_attr( sanitize_text_field( $key ) ) . '[]" value="' . esc_attr( sanitize_text_field( $v ) ) . '" />';
									}
								} else {
									echo '<input type="hidden" name="' . esc_attr( sanitize_text_field( $key ) ) . '" value="' . esc_attr( sanitize_text_field( $value ) ) . '" />';
								}
							}
							?>
							<input type="hidden" name="range" value="custom" />
							<input type="text" size="11" placeholder="yyyy-mm-dd" value="<?php echo ( ! empty( $_GET['start_date'] ) ) ? esc_attr( wp_unslash( $_GET['start_date'] ) ) : ''; ?>" name="start_date" class="range_datepicker from" autocomplete="off" /><?php //@codingStandardsIgnoreLine ?>
							<span>&ndash;</span>
							<input type="text" size="11" placeholder="yyyy-mm-dd" value="<?php echo ( ! empty( $_GET['end_date'] ) ) ? esc_attr( wp_unslash( $_GET['end_date'] ) ) : ''; ?>" name="end_date" class="range_datepicker to" autocomplete="off" /><?php //@codingStandardsIgnoreLine ?>
							<button type="submit" class="button" value="<?php esc_attr_e( 'Go', 'woocommerce' ); ?>"><?php esc_html_e( 'Go', 'woocommerce' ); ?></button>
							<?php wp_nonce_field( 'custom_range', 'wc_reports_nonce', false ); ?>
						</div>
					</form>
				</li>
			</ul>
		</div>

		<?php
		$this->display();
		echo '</div>';
		echo '</div>';

		echo "<script>
		jQuery('input[name=\"utm_source_ad_spend\"]').change(function(){
            let thiss = this
            jQuery(thiss).parent().next().html('Please wait...')
		    jQuery.post(
                ajaxurl,
                {
                    'action': 'handl_woo_report_utm_source',
                    'ad_spend': thiss.value,
                    'item' : jQuery(thiss).data('source')
                },
                function(response) {
                    let total = jQuery(thiss).data('total')
                    jQuery(thiss).parent().next().html( (total/thiss.value).toFixed(2) )
                }
            );
		})
		</script>
		";
	}

	private function calculate_current_range($current_range){
		switch ( $current_range ) {

			case 'custom':
				$this->start_date = max( strtotime( '-20 years' ), strtotime( sanitize_text_field( $_GET['start_date'] ) ) );

				if ( empty( $_GET['end_date'] ) ) {
					$this->end_date = strtotime( 'midnight', current_time( 'timestamp' ) );
				} else {
					$this->end_date = strtotime( 'midnight', strtotime( sanitize_text_field( $_GET['end_date'] ) ) );
				}
				break;
			case 'year':
				$this->start_date    = strtotime( date( 'Y-01-01', current_time( 'timestamp' ) ) );
				$this->end_date      = strtotime( 'midnight', current_time( 'timestamp' ) );
				break;

			case 'last_month':
				$first_day_current_month = strtotime( date( 'Y-m-01', current_time( 'timestamp' ) ) );
				$this->start_date        = strtotime( date( 'Y-m-01', strtotime( '-1 DAY', $first_day_current_month ) ) );
				$this->end_date          = strtotime( date( 'Y-m-t', strtotime( '-1 DAY', $first_day_current_month ) ) );
				break;

			case 'month':
				$this->start_date    = strtotime( date( 'Y-m-01', current_time( 'timestamp' ) ) );
				$this->end_date      = strtotime( 'midnight', current_time( 'timestamp' ) );
				break;

			case '7day':
				$this->start_date    = strtotime( '-6 days', strtotime( 'midnight', current_time( 'timestamp' ) ) );
				$this->end_date      = strtotime( 'midnight', current_time( 'timestamp' ) );
				break;
		}
	}

	/**
	 * Get column value.
	 *
	 * @param mixed  $item Item being displayed.
	 * @param string $column_name Column name.
	 */
	public function column_default( $item, $column_name ) {

		switch ( $column_name ) {
			case 'source':
				echo esc_html( $item->source );
				break;
			case 'sales':
				echo esc_html( $item->count );
				break;
			case 'amount':
				echo get_woocommerce_currency_symbol().esc_html( $this->round_totals( $item->total_sales ));
				break;
			case 'ad_spend':
				$ad_spend = $this->get_ad_spend_value('handl_woo_utm_source_ad_spend_'.$item->source);
				echo get_woocommerce_currency_symbol()."<span style='display:none'>$ad_spend</span><input data-total='".$item->total_sales."' data-source='".$item->source."' name='utm_source_$column_name' value='".($ad_spend ?? "")."'/>";
				break;
			case 'roas':
				$ad_spend = (float)$this->get_ad_spend_value('handl_woo_utm_source_ad_spend_'.$item->source);
				echo esc_html( (float)$ad_spend > 0 ? $this->round_totals($item->total_sales/$ad_spend) : 'NA' );
				break;
			case 'order_ids':
				echo implode(", ",array_map(function($value){
					return "<a href='post.php?post=$value&action=edit'>$value</a>";
				}, explode(",", $item->ids) ) );
				break;
		}
	}

	/**
	 * Get columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'source'  => __( $this->_args['title'], 'woocommerce' ),
			'sales'   => __( 'Number Sales', 'woocommerce' ),
			'amount'  => __( 'Total', 'woocommerce' ),
			'ad_spend'=> __( 'Ad Spend', 'woocommerce' ),
			'roas'=> __( 'ROAS', 'woocommerce' ),
			'order_ids'   => __( 'Order IDs', 'woocommerce' ),
		);

		return $columns;
	}

	private function get_ad_spend_value($key){
		return (float)get_option($key);
	}

	/**
	 * Prepare download list items.
	 */
	public function prepare_items() {

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
		$current_page          = absint( $this->get_pagenum() );
		// Allow filtering per_page value, but ensure it's at least 1.
		$per_page = max( 1, apply_filters( 'woocommerce_admin_downloads_report_downloads_per_page', 20 ) );

		$this->get_items( $current_page, $per_page );

		/**
		 * Pagination.
		 */
		$this->set_pagination_args(
			array(
				'total_items' => $this->max_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $this->max_items / $per_page ),
			)
		);
	}

	/**
	 * No items found text.
	 */
	public function no_items() {
		esc_html_e( 'No handl sources found.', 'woocommerce' );
	}

	/**
	 * Get downloads matching criteria.
	 *
	 * @param int $current_page Current viewed page.
	 * @param int $per_page How many results to show per page.
	 */
	public function get_items( $current_page, $per_page ) {
		global $wpdb;

		$this->max_items = 0;
		$this->items     = array();

		// Get downloads from database.
		$table      = $wpdb->posts;
		$query_from = " FROM {$table} as p ";

		$query_from .= " INNER JOIN {$wpdb->prefix}postmeta AS ts ON ( p.ID = ts.post_id AND ts.meta_key = '_order_total') LEFT JOIN {$wpdb->prefix}postmeta AS pm ON p.ID = pm.post_id ";

		$query_from .= ' WHERE 1=1 ';

		$query_from .= " AND p.post_type IN ('shop_order') AND p.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold', 'wc-refunded') ";

		$query_from .= $wpdb->prepare( ' AND pm.meta_key = "%s"  ', $this->_args['meta_key'] );

		if ( ! is_null( $this->meta_value ) ) {
			$query_from .= $wpdb->prepare( ' AND pm.meta_value = "%s"  ', $this->meta_value );
		}else{
			$query_from .= ' AND pm.meta_value != "" ';
		}

		$query_from .= "
				AND 	p.post_date >= '" . date( 'Y-m-d H:i:s', $this->start_date ) . "'
				AND 	p.post_date < '" . date( 'Y-m-d H:i:s', strtotime( '+1 DAY', $this->end_date ) ) . "'
		";
		$query_from .= ' GROUP BY pm.meta_value  ';

		$query_order = $wpdb->prepare( ' ORDER BY total_sales DESC LIMIT %d, %d;', ( $current_page - 1 ) * $per_page, $per_page );

		$this->items     = $wpdb->get_results( "SELECT GROUP_CONCAT(p.id) ids, COUNT(DISTINCT p.id) as count, post_date, SUM(ts.meta_value) as total_sales, pm.meta_value as source {$query_from} {$query_order}" ); // WPCS: cache ok, db call ok, unprepared SQL ok.
		$this->max_items = $wpdb->get_var( "SELECT COUNT( DISTINCT pm.meta_value ) {$query_from};" ); // WPCS: cache ok, db call ok, unprepared SQL ok.
	}

	/**
	 * Round our totals correctly.
	 *
	 * @param array|string $amount Chart total.
	 *
	 * @return array|string
	 */
	private function round_totals( $amount ) {
		if ( is_array( $amount ) ) {
			return array( $amount[0], wc_format_decimal( $amount[1], wc_get_price_decimals() ) );
		} else {
			return wc_format_decimal( $amount, wc_get_price_decimals() );
		}
	}

	public function get_export_button() {

		$current_range = ! empty( $_GET['range'] ) ? sanitize_text_field( wp_unslash( $_GET['range'] ) ) : '7day'; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<a
			href="#"
			download="report-<?php echo esc_attr( $current_range ); ?>-<?php echo esc_html( date_i18n( 'Y-m-d', current_time( 'timestamp' ) ) ); ?>.csv"
			class="export_csv"
			data-export="table"
			data-xaxes="<?php esc_attr_e( 'Date', 'woocommerce' ); ?>"
		>
			<?php esc_html_e( 'Export CSV', 'woocommerce' ); ?>
		</a>
		<?php
	}
}
