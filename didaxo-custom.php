<?php
   /*
   Plugin Name: Didaxo Custom
   Description: a plugin to create awesomeness and spread joy
   Version: 0.1
   Author: 
   Author URI: 
   License: 
   */
namespace TU;

!defined( 'ABSPATH' ) and exit;

include_once(ABSPATH . 'wp-admin/includes/plugin.php');

define( "DIDAXO_NAME", "didaxo-custom");
define( "DIDAXO_DIR", plugin_dir_path( __FILE__ ) );
define( "DIDAXO_URL", plugins_url() . '/' . DIDAXO_NAME );

@include( DIDAXO_DIR . 'classes/Utility.php');
@include( DIDAXO_DIR . 'classes/Didaxo_Level.php');

// error_log( DIDAXO_DIR );

/**
 * 
 */
class DidaxoCustom 
{

	/**
	 * [__construct description]
	 */
	public function __construct() 
	{
		add_action( 'init', array( $this, 'register_scripts') );
		add_action( 'wp', array(  $this , 'factory') );
	}

	/**
	 * [factory description]
	 * @return [type] [description]
	 */
	public function factory() 
	{
		
		global $post;
		global $wp_query;

		if ( !is_admin() &&  get_post_type( $post ) === 'tu_level' )
		{
			// se ha dei figli, è un livello ok
			$args = array(
				'post_type' => 'tu_level',
				'post_status' => 'publish',
				'post_parent' => $post->ID,
				'orderby' => 'menu_order',
				'order' => 'ASC',
			);
			$children = get_posts( $args );
			if( count( $children) ) {
				new DidaxoLevel;
			}
		}
		
	}

	/**
	 * [register_scripts description]
	 * @return [type] [description]
	 */
	public function register_scripts()
	{
		wp_register_script('didaxo-level', DIDAXO_URL . '/js/didaxo_level.js', array('jquery'), false, true );
		wp_register_script('froogaloop', 'http://a.vimeocdn.com/js/froogaloop2.min.js');
	}

	
}

if ( is_plugin_active('train-up/index.php') ) {
	add_action( 'plugins_loaded', function() {
		new DidaxoCustom;
	} );

	// AJAX
	// retrieve test
	add_action( 'wp_ajax_retrieveTest', array( 'TU\DidaxoLevel', '_ajax_retrieveTest') );
	add_action( 'wp_ajax_nopriv_retrieveTest', array( 'TU\DidaxoLevel', '_ajax_retrieveTest') );
	// check answer
	add_action( 'wp_ajax_checkAnswer', array( 'TU\DidaxoLevel', '_ajax_checkAnswer') );
	add_action( 'wp_ajax_nopriv_checkAnswer', array( 'TU\DidaxoLevel', '_ajax_checkAnswer') );


	// add_filter( "tu_render_answers", array( 'TU\DidaxoLevel', 'render_answers' ), 1 );
}




