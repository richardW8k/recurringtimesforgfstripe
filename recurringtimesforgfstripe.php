<?php

/*
Plugin Name: Recurring Times for Gravity Forms Stripe
Plugin URI:
Description: Adds a Recurring Times setting to feeds created with the Gravity Forms Stripe Add-On and uses the Stripe webhooks to check if the subscription should be cancelled.
Version: 0.1
Author: Richard Wawrzyniak
Author URI: http://www.wawrzyniak.me
------------------------------------------------------------------------
Copyright 2016 Richard Wawrzyniak

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see http://www.gnu.org/licenses.
*/

define( 'GF_STRIPE_VERSION', '2.0.4' );

add_action( 'gform_loaded', array( 'RT_GF_Stripe_Bootstrap', 'load' ), 5 );

class RT_GF_Stripe_Bootstrap {

	public static function load() {

		if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
			return;
		}

		require_once( 'class-recurringtimes-for-gf-stripe.php' );

		GFAddOn::register( 'RT_GF_Stripe' );
	}

}