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

define( "MEDIAELEMENT_URL", DIDAXO_URL . '/mediaelement' );

@include( DIDAXO_DIR . 'classes/Utility.php');
@include( DIDAXO_DIR . 'classes/Didaxo_Custom_Fields.php');
@include( DIDAXO_DIR . 'classes/Didaxo_Walker.php');
@include( DIDAXO_DIR . 'classes/Didaxo_Level.php');
@include( DIDAXO_DIR . 'classes/Didaxo_Question.php');
@include( DIDAXO_DIR . 'classes/Woocommerce_adaptor.php');

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
		// TODO: inizializzare i custom field senza plugin estrni
		Didaxo_Custom_Fields::init_customs();
		// custom type domande
		new DidaxoQuestion;

		add_action( 'init', array( $this, 'register_scripts') );
		add_action( 'wp', array(  $this , 'factory') );

		add_shortcode('didaxo_available_lessons', array( &$this, 'get_available_lessons' ) );
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
	 * [get_available_lessons description]
	 * @param  [type] $atts [description]
	 * @return [type]       [description]
	 */
	public function get_available_lessons( $atts )
	{
		global $wpdb;
		$qry = $wpdb->prepare( "SELECT meta_value from " . $wpdb->usermeta . " WHERE meta_key = 'tu_group' AND user_id = %d", tu()->user->ID );

		$query_results = $wpdb->get_results( $qry );
		$groups = array();
		foreach( $query_results as $meta_value ) 
		{
			$groups[] = $meta_value->meta_value;
		}

		// $tu_group = implode(',', $groups);

		$levels = get_posts(array(
			'post_type' => 'tu_level',
			'meta_query' => array(
				array(
					'key' => 'tu_group',
					'value' => $groups,
					'compare' => 'IN',
				)
			)
		));

		ob_start(); ?>
			<?php //print_r($levels); ?>
			<ul>
			<?php foreach($levels as $level) :  //print_r($level);
				$title = get_post_meta( $level->ID, 'wpcf-didaxo_titolo_lezione', true);
				if(!$level->post_parent) :
			?>
				<li>
					<a href="<?php echo get_permalink( $level->ID ); ?>"><?php echo $title; ?></a>
				</li>
			<?php endif; ?>
			<?php endforeach; ?>
			</ul>
		<?php
		return ob_get_clean();
	}

	/**
	 * [register_scripts description]
	 * @return [type] [description]
	 */
	public function register_scripts()
	{
		// VIMEO API
		wp_register_script('didaxo-level-vimeoapi', DIDAXO_URL . '/js/didaxo_level_vimeoAPI.js', array('jquery'), false, true );
		wp_register_script('froogaloop', 'http://a.vimeocdn.com/js/froogaloop2.min.js');
		
		// MEDIA ELEMENTS
		wp_register_script('mediaelement', DIDAXO_URL . '/mediaelement/mediaelement-and-player.min.js', array(), false, true);
		wp_register_script('mep-sourcechooser', DIDAXO_URL . '/js/mep-sourcechooser.js', array('mediaelement'), false, true);
		wp_register_script('didaxo-level-mediaelement', DIDAXO_URL . '/js/didaxo_level_mediaElements.js', array('jquery', 'mep-sourcechooser'), false, true );
		wp_register_style('mediaelement-style', DIDAXO_URL . '/mediaelement/mediaelementplayer.css');
		wp_register_style('iosfix-style', DIDAXO_URL . '/css/iOSFIx.min.css');
	}

	
}

if ( is_plugin_active('train-up/index.php') ) {
	add_action( 'plugins_loaded', function() {
		new DidaxoCustom;
	} );

	

	// AJAX
	// choose definition
	add_action( 'wp_ajax_chooseDefinition', array( 'TU\DidaxoLevel', '_ajax_chooseDefinition') );
	add_action( 'wp_ajax_nopriv_chooseDefinition', array( 'TU\DidaxoLevel', '_ajax_chooseDefinition') );
	// retrieve test
	add_action( 'wp_ajax_retrieveTest', array( 'TU\DidaxoLevel', '_ajax_retrieveTest') );
	add_action( 'wp_ajax_nopriv_retrieveTest', array( 'TU\DidaxoLevel', '_ajax_retrieveTest') );
	// check answer
	add_action( 'wp_ajax_checkAnswer', array( 'TU\DidaxoLevel', '_ajax_checkAnswer') );
	add_action( 'wp_ajax_nopriv_checkAnswer', array( 'TU\DidaxoLevel', '_ajax_checkAnswer') );

}





