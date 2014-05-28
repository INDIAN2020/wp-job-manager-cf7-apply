<?php
/**
 * Plugin Name: WP Job Manager - Apply With Contact Form 7
 * Plugin URI:  https://github.com/Astoundify/wp-job-manager-contact-form-7-apply/
 * Description: Apply to jobs that have added an email address via Contact Form 7
 * Author:      Astoundify, SpencerFinnell
 * Author URI:  http://astoundify.com
 * Version:     1.1.1
 * Text Domain: job_manager_cf7_apply
 */

// Exit if accessed directly
if ( ! defined( 'WPINC' ) )  {
	exit;
}

class Astoundify_Job_Manager_Apply_CF7 {

	/**
	 * @var $instance
	 */
	private static $instance;

	/**
	 * @var $jobs_form_id
	 */
	private $jobs_form_id;

	/**
	 * @var $resumes_form_id
	 */
	private $resumes_form_id;

	/**
	 * Make sure only one instance is only running.
	 */
	public static function get_instance() {
		if ( ! isset ( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Start things up.
	 *
	 * @since WP Job Manager - Apply with Contact Form 7 1.0
	 */
	public function __construct() {
		$this->jobs_form_id    = get_option( 'job_manager_job_apply'   , 0 );
		$this->resumes_form_id = get_option( 'job_manager_resumes_apply', 0 );

		$this->setup_actions();
		$this->setup_globals();
		$this->load_textdomain();
	}

	/**
	 * Set some smart defaults to class variables. Allow some of them to be
	 * filtered to allow for early overriding.
	 *
	 * @since WP Job Manager - Apply with Contact Form 7 1.0
	 *
	 * @return void
	 */
	private function setup_globals() {
		$this->file       = __FILE__;

		$this->basename   = plugin_basename( $this->file );
		$this->plugin_dir = plugin_dir_path( $this->file );
		$this->plugin_url = plugin_dir_url ( $this->file );

		$this->lang_dir   = trailingslashit( $this->plugin_dir . 'languages' );
		$this->domain     = 'job_manager_cf7_apply';
	}

	/**
	 * Loads the plugin language files
	 *
 	 * @since WP Job Manager - Apply with Contact Form 7 1.0
	 */
	public function load_textdomain() {
		$locale        = apply_filters( 'plugin_locale', get_locale(), $this->domain );
		$mofile        = sprintf( '%1$s-%2$s.mo', $this->domain, $locale );

		$mofile_local  = $this->lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/' . $this->domain . '/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			return load_textdomain( $this->domain, $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			return load_textdomain( $this->domain, $mofile_local );
		}

		return false;
	}

	/**
	 * Setup the default hooks and actions
	 *
	 * @since WP Job Manager - Apply with Contact Form 7 1.0
	 *
	 * @return void
	 */
	private function setup_actions() {
		add_filter( 'job_manager_settings' , array( $this, 'job_manager_settings' ) );
		add_filter( 'wpcf7_mail_components', array( $this, 'notification_email' ), 10, 2 );
	}

	/**
	 * Add a setting in the admin panel to select the contact form to use.
	 *
	 * @since WP Job Manager - Apply with Contact Form 7 1.0
	 *
	 * @param array $settings
	 * @return array $settings
	 */
	public function job_manager_settings( $settings ) {
		$settings[ 'job_listings' ][1][] = array(
			'name'    => 'job_manager_job_apply',
			'std'     => null,
			'type'    => 'select',
			'options' => self::get_forms(),
			'label'   => __( 'Jobs Contact Form', 'job_manager_cf7_apply' ),
			'desc'    => __( 'The Contact Form you created for contacting employers.', 'job_manager_cf7_apply' ),
		);

		if ( class_exists( 'WP_Resume_Manager' ) ) {
			$settings[ 'job_listings' ][1][] = array(
				'name'  => 'job_manager_resumes_apply',
				'std'   => null,
				'type'    => 'select',
				'options' => self::get_forms(),
				'label' => __( 'Resumes Contact Form', 'job_manager_cf7_apply' ),
				'desc'  => __( 'The Contact Form you created for contacting employees.', 'job_manager_cf7_apply' ),
			);
		}

		return $settings;
	}

	private static function get_forms() {
		$forms = array( 0 => __( 'Please select a form', 'job_manager_cf7_apply' ) );

		$_forms = get_posts(
			array(
				'numberposts' => -1,
				'post_type'   => 'wpcf7_contact_form',
			)
		);

		if ( ! empty( $_forms ) ) {

			foreach ( $_forms as $_form ) {
				$forms[ $_form->ID ] = $_form->post_title;
			}
		}

		return $forms;
	}

	/**
	 * Set the notification email when sending an email.
	 *
	 * @since WP Job Manager - Apply with Contact Form 7 1.0
	 *
	 * @return string The email to notify.
	 */
	public function notification_email( $components, $cf7 ) {
		if ( ! is_singular( array( 'resume', 'job_listing' ) ) ) {
			return $components;
		}

		if ( $cf7->id !== absint( $this->jobs_form_id ) && $cf7->id !== absint( $this->resumes_form_id ) ) {
			return $components;
		}

		global $post;

		$components[ 'recipient' ] = $cf7->ID == $this->jobs_form_id ? $post->_application : $post->_candidate_email;

		return $components;
	}
}

add_action( 'init', array( 'Astoundify_Job_Manager_Apply_CF7', 'get_instance' ) );
