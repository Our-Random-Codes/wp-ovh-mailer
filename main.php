<?php
/**
 * Plugin Name: OVH Mailer
 * Description: Configure WordPress email delivery through OVH SMTP.
 * Version: 1.0.0
 * Requires at least: 5.5
 * Requires PHP: 7.1
 * Author: Our Random Codes
 * Author URI: https://github.com/Our-Random-Codes
 * @see  https://docs.ovhcloud.com/fr/guides/web-cloud/email-and-collaborative-solutions/mx-plan/landing-page-mx-plan#configurer-un-logiciel-de-messagerie
 */
defined( 'ABSPATH' ) || exit;

class OVH_Mailer {
	private const OPTION_NAME = 'ovh_mailer_options';

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_notices', [ $this, 'admin_notice' ] );
		add_action( 'phpmailer_init', [ $this, 'phpmailer_init' ] );
		add_action( 'wp_ajax_ovh_mailer_test', [ $this, 'ajax_test_mail' ] );
		add_filter(
			'plugin_action_links_' . plugin_basename( __FILE__ ),
			[ $this, 'plugin_action_links' ]
		);
	}

	/**
	 * Append a Settings link next to Activate / Deactivate / Delete
	 */
	public function plugin_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'options-general.php?page=ovh-mailer' ) ),
			__( 'Réglages', 'ovh-mailer' )
		);
		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Add Settings > OVH Mailer
	 */
	public function admin_menu() {
		add_options_page(
			'OVH Mailer',
			'OVH Mailer',
			'manage_options',
			'ovh-mailer',
			[ $this, 'settings_page' ]
		);
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting(
			'ovh_mailer',
			self::OPTION_NAME,
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize_options' ],
				'default'           => [],
			]
		);
	}

	/**
	 * Sanitize settings
	 */
	public function sanitize_options( $input ) {
		$options             = [];
		$options['login']    = isset( $input['login'] ) ? sanitize_text_field( $input['login'] ) : '';
		$options['password'] = isset( $input['password'] ) ? sanitize_text_field( $input['password'] ) : '';
		$options['port']     = in_array( (int) ( $input['port'] ?? 465 ), [ 465, 993, 995 ], true )
			? (int) $input['port']
			: 465;
		$options['enabled']  = ! empty( $input['enabled'] ) ? 1 : 0;

		return $options;
	}

	/**
	 * Get settings
	 */
	private function get_options() {
		$options = get_option( self::OPTION_NAME, [] );

		return wp_parse_args( $options, [
			'login'    => '',
			'password' => '',
			'port'     => 465,
			'enabled'  => 0,
		] );
	}

	/**
	 * Check whether SMTP is configured
	 */
	private function is_configured() {
		$options = $this->get_options();

		return ! empty( $options['login'] )
		       && ! empty( $options['password'] )
		       && ! empty( $options['enabled'] );
	}

	/**
	 * Configure PHPMailer
	 */
	public function phpmailer_init( $phpmailer ) {
		if ( ! $this->is_configured() ) {
			return;
		}
		$options = $this->get_options();
		$port    = (int) $options['port'];
		$phpmailer->isSMTP();
		$phpmailer->Host     = 'ssl0.ovh.net';
		$phpmailer->Port     = $port;
		$phpmailer->SMTPAuth = true;
		$phpmailer->Username = $options['login'];
		$phpmailer->Password = $options['password'];
		if ( $port === 465 ) {
			$phpmailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
		} else {
			$phpmailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
		}
		$phpmailer->From = $options['login'];
	}

	/**
	 * Handle AJAX test-mail request
	 */
	public function ajax_test_mail() {
		check_ajax_referer( 'ovh_mailer_test_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission refusée.', 'ovh-mailer' ) ] );
		}
		if ( ! $this->is_configured() ) {
			wp_send_json_error( [ 'message' => __( 'Plugin non configuré.', 'ovh-mailer' ) ] );
		}
		$to = isset( $_POST['to'] ) ? sanitize_email( wp_unslash( $_POST['to'] ) ) : '';
		if ( ! is_email( $to ) ) {
			wp_send_json_error( [ 'message' => __( 'Adresse e-mail invalide.', 'ovh-mailer' ) ] );
		}
		$smtp_log = '';
		add_action( 'phpmailer_init', function ( $phpmailer ) use ( &$smtp_log ) {
			$phpmailer->SMTPDebug   = 4;
			$phpmailer->Debugoutput = function ( $str, $level ) use ( &$smtp_log ) {
				$smtp_log .= trim( $str ) . "\n";
			};
		}, 20 );
		$sent = wp_mail(
			$to,
			__( '[OVH Mailer] E-mail de test', 'ovh-mailer' ),
			__( 'Ceci est un e-mail de test envoyé depuis OVH Mailer.', 'ovh-mailer' )
		);
		global $phpmailer;
		$error_info = ! empty( $phpmailer->ErrorInfo ) ? $phpmailer->ErrorInfo : '';
		wp_send_json(
			[
				'success' => $sent,
				'data'    => [
					'sent'     => $sent,
					'error'    => $error_info,
					'smtp_log' => $smtp_log,
				],
			]
		);
	}

	/**
	 * Admin warning
	 */
	public function admin_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if (
			isset( $_GET['page'] )
			&& $_GET['page'] === 'ovh-mailer'
		) {
			return;
		}
		if ( $this->is_configured() ) {
			return;
		}
		$url = admin_url( 'options-general.php?page=ovh-mailer#ovh-mailer' );
		include plugin_dir_path( __FILE__ ) . 'views/admin-notice.php';
	}

	/**
	 * Settings page
	 */
	public function settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$options            = $this->get_options();
		$current_port       = (int) $options['port'];
		$is_configured      = $this->is_configured();
		$port_choices       = [
			993 => __( 'Entrant — IMAP (993)', 'ovh-mailer' ),
			995 => __( 'Entrant — POP (995)', 'ovh-mailer' ),
			465 => __( 'Sortant — SMTP (465)', 'ovh-mailer' ),
		];
		$current_user       = wp_get_current_user();
		$default_test_email = $current_user->user_email;
		$option_name        = self::OPTION_NAME;
		$plugin_url         = plugin_dir_url( __FILE__ );
		include plugin_dir_path( __FILE__ ) . 'views/settings-page.php';
	}
}

new OVH_Mailer();
