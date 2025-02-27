<?php
/**
 * PA Admin Notices.
 */

namespace PremiumAddons\Admin\Includes;

use PremiumAddons\Includes\Helper_Functions;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Class Admin_Notices
 */
class Admin_Notices {

	/**
	 * Class object
	 *
	 * @var instance
	 */
	private static $instance = null;

	/**
	 * Elementor slug
	 *
	 * @var elementor
	 */
	private static $elementor = 'elementor';

	/**
	 * PAPRO Slug
	 *
	 * @var papro
	 */
	private static $papro = 'premium-addons-pro';

	/**
	 * Notices Keys
	 *
	 * @var notices
	 */
	private static $notices = null;

	/**
	 * Constructor for the class
	 */
	public function __construct() {

		add_action( 'admin_init', array( $this, 'init' ) );

		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		add_action( 'wp_ajax_pa_reset_admin_notice', array( $this, 'reset_admin_notice' ) );

		add_action( 'wp_ajax_pa_dismiss_admin_notice', array( $this, 'dismiss_admin_notice' ) );

		self::$notices = array(
			'pa-review',
			'halloween24_hide',
		);

	}

	/**
	 * Init
	 *
	 * Init required functions
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function init() {

		$this->handle_review_notice();

	}

	/**
	 * init notices check functions
	 */
	public function admin_notices() {

		$this->required_plugins_check();

		$show_review = get_option( 'pa_review_notice' );

		// Make sure "Already did" was not clicked before.
		if ( '1' !== $show_review ) {

            $cache_key = 'premium_notice_' . PREMIUM_ADDONS_VERSION;

            $response = get_transient( $cache_key );

			if ( false == $response ) {
				$this->show_review_notice();
			}
		}

		if ( Helper_Functions::check_hide_notifications() ) {
			return;
		}

		$this->get_halloween_notice();

	}

	/**
	 * Handle Review Notice
	 *
	 * Checks if review message is dismissed.
	 *
	 * @access public
	 * @return void
	 */
	public function handle_review_notice() {

		if ( ! isset( $_GET['pa_review'] ) ) {
			return;
		}

		if ( 'opt_out' === $_GET['pa_review'] ) {
			check_admin_referer( 'opt_out' );

			update_option( 'pa_review_notice', '1' );
		}

		wp_safe_redirect( remove_query_arg( 'pa_review' ) );

		exit;
	}

	/**
	 * Required plugin check
	 *
	 * Shows an admin notice when Elementor is missing.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function required_plugins_check() {

		$elementor_path = sprintf( '%1$s/%1$s.php', self::$elementor );

		if ( ! defined( 'ELEMENTOR_VERSION' ) ) {

			if ( ! Helper_Functions::is_plugin_installed( $elementor_path ) ) {

				if ( Admin_Helper::check_user_can( 'install_plugins' ) ) {

					$install_url = wp_nonce_url( self_admin_url( sprintf( 'update.php?action=install-plugin&plugin=%s', self::$elementor ) ), 'install-plugin_elementor' );

					$message = sprintf( '<p>%s</p>', __( 'Premium Addons for Elementor is not working because you need to Install Elementor plugin.', 'premium-addons-for-elementor' ) );

					$message .= sprintf( '<p><a href="%s" class="button-primary">%s</a></p>', $install_url, __( 'Install Now', 'premium-addons-for-elementor' ) );

				}
			} else {

				if ( Admin_Helper::check_user_can( 'activate_plugins' ) ) {

					$activation_url = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $elementor_path . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $elementor_path );

					$message = '<p>' . __( 'Premium Addons for Elementor is not working because you need to activate Elementor plugin.', 'premium-addons-for-elementor' ) . '</p>';

					$message .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $activation_url, __( 'Activate Now', 'premium-addons-for-elementor' ) ) . '</p>';

				}
			}
			$this->render_admin_notices( $message );
		}
	}

	/**
	 * Get Review Text
	 *
	 * Gets admin review notice HTML.
	 *
	 * @since 2.8.4
	 * @access public
	 *
	 * @param string $review_url plugin page.
	 * @param string $optout_url redirect url.
	 */
	public function get_review_text( $review_url, $optout_url ) {

		$notice = sprintf(
			'<p>' . __( 'Could we take just 2 minutes of your time? We\'d be incredibly grateful if you could give ', 'premium-addons-for-elementor' ) .
			'<b>' . __( 'Premium Addons for Elementor', 'premium-addons-for-elementor' ) . '</b> a 5 Stars Rating on WordPress.org. Your support helps us continue creating even more amazing free features in the future!</p>
            <div>
                <a class="button button-primary" href="%s" target="_blank"><span>' . __( 'Sure, leave a Review', 'premium-addons-for-elementor' ) . '</span></a>
                <a class="button" href="%2$s"><span>' . __( 'I Already Did', 'premium-addons-for-elementor' ) . '</span></a>
                <a class="button button-secondary pa-notice-reset"><span>' . __( 'Maybe Later', 'premium-addons-for-elementor' ) . '</span></a>
            </div>',
			$review_url,
			$optout_url
		);

		return $notice;
	}

	/**
	 * Checks if review admin notice is dismissed
	 *
	 * @since 2.6.8
	 * @return void
	 */
	public function show_review_notice() {

		$review_url = 'https://wordpress.org/support/plugin/premium-addons-for-elementor/reviews/?filter=5';

		$optout_url = wp_nonce_url( add_query_arg( 'pa_review', 'opt_out' ), 'opt_out' );
		?>

		<div class="error pa-notice-wrap pa-review-notice" data-notice="pa-review">
			<div class="pa-img-wrap">
				<img src="<?php echo esc_url( PREMIUM_ADDONS_URL . 'admin/images/pa-logo-symbol.png' ); ?>">
			</div>
			<div class="pa-text-wrap">
				<?php echo wp_kses_post( $this->get_review_text( $review_url, $optout_url ) ); ?>
			</div>
			<div class="pa-notice-close">
				<a href="<?php echo esc_url( $optout_url ); ?>"><span class="dashicons dashicons-dismiss"></span></a>
			</div>
		</div>

		<?php

	}

    public function get_halloween_notice() {

        $time     = time();

        if ( $time > 1730582400 || get_transient( 'halloween24_hide' ) ) {
			return;
		}

        $papro_path = 'premium-addons-pro/premium-addons-pro-for-elementor.php';

		$is_papro_installed = Helper_Functions::is_plugin_installed( $papro_path );

		$license_key = get_option( 'papro_license_key' );

        if ( $is_papro_installed ) {
			$status = $this->check_status( $license_key );

            if( $status ) {
                return;
            }
		}

		$link = Helper_Functions::get_campaign_link( 'https://premiumaddons.com/halloween-sale/', 'wp-dash', 'halloween24-notification', 'halloween24' );

		?>

		<div class="error pa-notice-wrap pa-new-feature-notice pa-review-notice">
			<div class="pa-img-wrap">
				<img src="<?php echo PREMIUM_ADDONS_URL . 'admin/images/pa-logo-symbol.png'; ?>">
			</div>
			<div class="pa-text-wrap">
				<p>
					<?php echo __( 'Halloween Sale! Save up to 20% on Premium Addons Pro.', 'premium-addons-for-elementor' ); ?>
					<a class="button pa-cta-btn button-primary" href="<?php echo esc_url( $link ); ?>" target="_blank">
						<span><?php echo __( 'Catch The Deal', 'premium-addons-for-elementor' ); ?></span>
					</a>
				</p>
			</div>
			<div class="pa-notice-close" data-notice="halloween24_hide">
				<span class="dashicons dashicons-dismiss"></span>
			</div>
		</div>

		<?php
	}

	/**
	 * Renders an admin notice error message
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param string $message notice text.
	 * @param string $class notice class.
	 * @param string $handle notice handle.
	 *
	 * @return void
	 */
	private function render_admin_notices( $message, $class = '', $handle = '' ) {
		?>
			<div class="error pa-new-feature-notice <?php echo esc_attr( $class ); ?>" data-notice="<?php echo esc_attr( $handle ); ?>">
				<?php echo wp_kses_post( $message ); ?>
			</div>
		<?php
	}



	/**
	 * Register admin scripts
	 *
	 * @since 3.2.8
	 * @access public
	 */
	public function admin_enqueue_scripts() {

		wp_enqueue_script(
			'pa-dashboard',
			PREMIUM_ADDONS_URL . 'admin/assets/js/pa-dashboard.js',
			array( 'jquery' ),
			PREMIUM_ADDONS_VERSION,
			true
		);

		wp_localize_script(
			'pa-dashboard',
			'PaNoticeSettings',
			array(
				'ajaxurl' => esc_url( admin_url( 'admin-ajax.php' ) ),
				'nonce'   => wp_create_nonce( 'pa-notice-nonce' ),
			)
		);

	}

	/**
	 * Set transient for admin notice
	 *
	 * @since 3.2.8
	 * @access public
	 *
	 * @return void
	 */
	public function reset_admin_notice() {

		check_ajax_referer( 'pa-notice-nonce', 'nonce' );

		if ( ! Admin_Helper::check_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$key = isset( $_POST['notice'] ) ? sanitize_text_field( wp_unslash( $_POST['notice'] ) ) : '';

		if ( ! empty( $key ) && in_array( $key, self::$notices, true ) ) {

			$cache_key = 'premium_notice_' . PREMIUM_ADDONS_VERSION;

			set_transient( $cache_key, true, WEEK_IN_SECONDS );

			wp_send_json_success();

		} else {

			wp_send_json_error();

		}

	}

	/**
	 * Dismiss admin notice
	 *
	 * @since 3.11.7
	 * @access public
	 *
	 * @return void
	 */
	public function dismiss_admin_notice() {

		check_ajax_referer( 'pa-notice-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$key = isset( $_POST['notice'] ) ? sanitize_text_field( wp_unslash( $_POST['notice'] ) ) : '';

		if ( ! empty( $key ) && in_array( $key, self::$notices, true ) ) {

			set_transient( $key, true, 20 * DAY_IN_SECONDS );

			wp_send_json_success();

		} else {

			wp_send_json_error();

		}

	}

    /**
     * Check Status
     *
     * @since 4.10.15
	 * @access public
     */
    public function check_status( $key ) {

        $status = false;

        $api_params = array(
            'edd_action' => 'check_license',
            'license'    => $key,
            'item_id'    => 361,
        );

        $response = wp_remote_get(
            'http://my.leap13.com',
            array(
                'timeout'   => 15,
                'sslverify' => false,
                'body'      => $api_params,
            )
        );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return false;
        }

        $body = wp_remote_retrieve_body( $response );

        $body = json_decode( $body, true );

        if ( isset( $body['license'] ) && 'valid' === $body['license'] ) {
            $status = true;
        }

        return $status;
    }

	/**
	 * Creates and returns an instance of the class
	 *
	 * @since 2.8.4
	 * @access public
	 *
	 * @return object
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) ) {

			self::$instance = new self();

		}

		return self::$instance;
	}

}
