<?php
GFForms::include_addon_framework();

class RT_GF_Stripe extends GFAddOn {
	protected $_version = RT_GF_Stripe_VERSION;
	protected $_min_gravityforms_version = '2.0';
	protected $_slug = 'recurringtimesforgfstripe';
	protected $_path = 'recurringtimesforgfstripe/recurringtimesforgfstripe.php';
	protected $_full_path = __FILE__;
	protected $_title = 'Recurring Times for Gravity Forms Stripe';
	protected $_short_title = 'RT for GFStripe';

	private static $_instance = null;

	/**
	 * Get an instance of this class.
	 *
	 * @return RT_GF_Stripe
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new RT_GF_Stripe();
		}

		return self::$_instance;
	}

	/**
	 * Include the function hooked to gform_post_add_subscription_payment at the same time the Stripe webhooks would be processed.
	 */
	public function pre_init() {
		add_action( 'gform_post_add_subscription_payment', array( $this, 'post_add_subscription_payment' ) );
	}

	/**
	 * Include the function hooked to gform_gravityformsstripe_feed_settings_fields when in the admin.
	 */
	public function init_admin() {
		add_filter( 'gform_gravityformsstripe_feed_settings_fields', array( $this, 'feed_settings_fields' ), 10, 2 );
	}

	/**
	 * Add the recurringTimes setting to the Stripe feed.
	 *
	 * @param array $feed_settings_fields An array of feed settings fields which will be displayed on the Feed Settings edit view.
	 * @param GFStripe $addon The current instance of the Gravity Forms Stripe Add-on.
	 *
	 * @return mixed
	 */
	public function feed_settings_fields( $feed_settings_fields, $addon ) {

		$feed_settings_fields = $this->add_field_after( 'billingCycle', array(
			array(
				'name'    => 'recurringTimes',
				'label'   => esc_html__( 'Recurring Times', 'gravityforms' ),
				'type'    => 'select',
				'choices' => array(
					             array(
						             'label' => esc_html__( 'infinite', 'gravityforms' ),
						             'value' => '0'
					             )
				             ) + $addon->get_numeric_choices( 1, 100 ),
				'tooltip' => '<h6>' . esc_html__( 'Recurring Times', 'gravityforms' ) . '</h6>' . esc_html__( 'Select how many times the recurring payment should be made.  The default is to bill the customer until the subscription is canceled.', 'gravityforms' )
			)
		), $feed_settings_fields );

		return $feed_settings_fields;
	}

	/**
	 * After a subscription payment has been processed cancel the subscription if the configured number of recurring payments has been made.
	 *
	 * @param array $entry The entry currently being processed.
	 */
	public function post_add_subscription_payment( $entry ) {

		// Abort if this is not an active subscription.
		if ( rgar( $entry, 'payment_status' ) != 'Active' ) {
			return;
		}

		// Get the feed which processed this entry.
		$feed = gf_stripe()->get_payment_feed( $entry );

		// Abort if a feed wasn't found.
		if ( ! $feed ) {
			return;
		}

		// Get the value of the recurringTimes setting from the feed.
		$recurringTimes = rgars( $feed, 'meta/recurringTimes' );

		// Abort if recurringTimes wasn't set for this feed or it was set to 0 (infinite).
		if ( rgblank( $recurringTimes ) ) {
			return;
		}

		// Count how many subscription payments have been made for this entry.
		global $wpdb;
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT count(id) FROM {$wpdb->prefix}gf_addon_payment_transaction WHERE lead_id=%d", $entry['id'] ) );

		// Cancel the subscription if the count matches the value of the recurringTimes setting.
		if ( $count == $recurringTimes ) {
			$result = gf_stripe()->cancel( $entry, $feed );
			gf_stripe()->log_debug( sprintf( '%s(): Cancelling subscription (feed #%d - %s) for entry #%d. Result: %s', __METHOD__, $feed['id'], rgars( $feed, 'meta/feedName' ), $entry['id'], print_r( $result, 1 ) ) );
		}

	}

}