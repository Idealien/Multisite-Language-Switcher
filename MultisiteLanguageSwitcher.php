<?php

/*
Plugin Name: Multisite Language Switcher
Plugin URI: http://lloc.de/msls
Description: A simple but powerful plugin that will help you to manage the relations of your contents in a multilingual multisite-installation.
Version: 0.9.9
Author: Dennis Ploetner 
Author URI: http://lloc.de/
*/

/*
Copyright 2013  Dennis Ploetner  (email : re@lloc.de)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * MultisiteLanguageSwitcher
 * @author Dennis Ploetner <re@lloc.de>
 * @since 0.9.8
 */
if ( !class_exists( 'MslsAutoloader' ) ) {

	if ( !defined( 'MSLS_PLUGIN_VERSION' ) )
		define( 'MSLS_PLUGIN_VERSION', '0.9.9' );
	if ( !defined( 'MSLS_PLUGIN_PATH' ) )
		define( 'MSLS_PLUGIN_PATH', plugin_basename( __FILE__ ) );
	if ( !defined( 'MSLS_PLUGIN__FILE__' ) )
		define( 'MSLS_PLUGIN__FILE__', __FILE__ );

	/**
	 * The Autoloader does all the magic when it comes to include a file
	 * @package Msls
	 */
	class MslsAutoloader {

		/**
		 * Static loader method
		 * @param string $cls
		 */
		public static function load( $cls ) {
			if ( 'Msls' == substr( $cls, 0, 4 ) ) 
				require_once dirname( __FILE__ ) . '/includes/' . $cls . '.php';
		}

	}

	/**
	 * Interface for classes which are to register in the MslsRegistry-instance
	 *
	 * get_called_class is just avalable in php >= 5.3 so I defined an interface here
	 * @package Msls
	 */
	interface IMslsRegistryInstance {

		/**
		 * Instance
		 * @return object
		 */
		public static function instance();

	}

	/**
	 * The autoload-stack could be inactive so the function will return false
	 */
	if ( in_array( '__autoload', (array) spl_autoload_functions() ) )
		spl_autoload_register( '__autoload' );
	spl_autoload_register( array( 'MslsAutoloader', 'load' ) );

	register_activation_hook( __FILE__, array( 'MslsPlugin', 'activate' ) );
	register_deactivation_hook( __FILE__, array( 'MslsPlugin', 'deactivate' ) );
	register_uninstall_hook( __FILE__, array( 'MslsPlugin', 'uninstall' ) );

	add_action( 'init', array( 'MslsPlugin', 'init_i18n_support' ) );
	add_action( 'widgets_init', array( 'MslsPlugin', 'init_widget' ) );

	if ( is_admin() ) {
		add_action( 'admin_menu', array( 'MslsAdmin', 'init' ) );

		add_action( 'wp_ajax_suggest_posts', array( 'MslsMetaBox', 'suggest' ) );
		add_action( 'wp_ajax_suggest_terms', array( 'MslsPostTag', 'suggest' ) );

		add_action( 'load-post.php', array( 'MslsMetaBox', 'init' ) );
		add_action( 'load-post-new.php', array( 'MslsMetaBox', 'init' ) );

		add_action( 'load-edit.php', array( 'MslsCustomColumn', 'init' ) );

		add_action( 'load-edit-tags.php', array( 'MslsCustomColumnTaxonomy', 'init' ) );
		add_action( 'load-edit-tags.php', array( 'MslsPostTag', 'init' ) );

		if ( !empty( $_POST['action'] ) ) {
			if ( 'add-tag' == $_POST['action'] )
				add_action( 'admin_init', array( 'MslsPostTag', 'init' ) );
			elseif ( 'inline-save' == $_POST['action'] )
				add_action( 'admin_init', array( 'MslsCustomColumn', 'init' ) );
			elseif ( 'inline-save-tax' == $_POST['action'] )
				add_action( 'admin_init', array( 'MslsCustomColumnTaxonomy', 'init' ) );
		}
	}

	/**
	 * Filter for the_content()
	 * @package Msls
	 * @uses MslsOptions
	 * @param string $content
	 * @return string
	 */ 
	function msls_content_filter( $content ) {
		if ( !is_front_page() && is_singular() ) {
			$options = MslsOptions::instance();
			if ( $options->is_content_filter() ) {
				$content .= msls_filter_string();
			}
		}
		return $content;
	}
	add_filter( 'the_content', 'msls_content_filter' );

	/**
	 * Create filterstring for msls_content_filter()
	 * @package Msls
	 * @uses MslsOutput
	 * @param string $pref
	 * @param string $post
	 * @return string
	 */ 
	function msls_filter_string( $pref = '<p id="msls">', $post = '</p>' ) {
		$obj   = new MslsOutput();
		$links = $obj->get( 1, true, true );
		if ( !empty( $links ) ) {
			if ( count( $links ) > 1 ) {
				$last  = array_pop( $links );
				$links = sprintf(
					__( '%s and %s', 'msls' ),
					implode( ', ', $links ),
					$last
				);
			}
			else {
				$links = $links[0];
			}
			return $pref .
				sprintf(
					__( 'This post is also available in %s.', 'msls' ),
					$links
				) .
				$post;
		}
		return '';
	}

	/**
	 * Get the output for using the links to the translations in your code
	 * @return string
	 * @package Msls
	 * @param array $arr
	 * @see the_msls()
	 */
	function get_the_msls( array $arr = array() ) {
		$obj = new MslsOutput();
		if ( $arr )
			$obj->set_tags( $arr );
		return( sprintf( '%s', $obj ) );
	}

	/**
	 * Output the links to the translations in your template
	 * 
	 * You can call of this function directly like that
	 * <code>if ( function_exists ( 'the_msls' ) ) the_msls();</code>
	 * @package Msls
	 * @param array $arr
	 * @uses get_the_msls()
	 */
	function the_msls( array $arr = array() ) {
		echo get_the_msls( $arr );
	}

}
