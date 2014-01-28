<?php 

namespace TU;

/**
 * 
 */
class DidaxoLevel {

	public static $_video;

	public $_master;

	/**
	 * [__construct description]
	 */
	public function __construct() 
	{
		$this->init();	
	}


	/**
	 * [init description]
	 * @return [type] [description]
	 */
	public function init(){

		global $post;

		// caricamento script necessari
		wp_enqueue_script( 'froogaloop' );
		wp_enqueue_script( 'didaxo-level' );

		add_action( 'wp_head', array( &$this, 'buildSteps' ) );

		// ottengo il livello tu
		$this->_master = new Level( tu()->level->ID );

		// reperimento risorsa video
		$resources = $this->_master->get_resources();
		$video_array = get_post_custom_values( 'video', $resources[0]->ID);

		self::$_video = $video_array[0];
		
		// costruzione player ( tramite shortcode )
		add_shortcode( 'didaxo_player', array( &$this, 'buildPlayerShortcode' ) );
		
		// error_log( var_export( tu()->level->test , true ) );
	}

	/**
	 * [buildPlayerShortcode description]
	 * @return [type] [description]
	 */
	public function buildPlayerShortcode( $atts )
	{
		ob_start();
		?>
		<div id="didaxo-player-wrapper">
			<iframe id="didaxo-player" src="http://player.vimeo.com/video/<?php echo self::$_video ?>?api=1&amp;player_id=didaxo-player&amp;badge=0&amp;portrait=0&amp;title=0&amp;byline=0" width="540" height="304" frameborder="0"></iframe>
            <p>
                <button class="play">Play</button>
                <button class="pause">Pause</button>
            </p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * [buildSteps description]
	 * @return [type] [description]
	 */
	public function buildSteps()
	{
		global $post;

		// reperire tutti i figli del padre
		$args = array(
			'post_type' => 'tu_level',
			'post_status' => 'publish',
			'post_parent' => $this->_master->ID,
			'orderby' => 'menu_order',
			'order' => 'ASC',
		);
		$children = get_posts( $args );

		// costruire un array con gli step definiti come cf
		$steps = array();
		foreach( $children as $child ) 
		{

			$child_level = new Level( $child->ID );
			$test = new Test( $child_level->get_test() );

			/**
			 * TODO : aggiungere alla coda solo se il test non è stato passato
			 */

			$timer_start = get_post_custom_values( 'timer_start', $child->ID );
			$timer_end = get_post_custom_values( 'timer_end', $child->ID );

			$wp_nonce = wp_create_nonce( 'didaxo_retrieve_level_' . $child->ID );

			$steps[] = array( 
				'level_id' => $child->ID,
				'nonce' => $wp_nonce,
				'timer_start' => $timer_start[0],
				'timer_end' => $timer_end[0],
			);
		}
		//error_log( var_export( $steps, true ));
		$wp_nonce = wp_create_nonce( 'didaxo_retrieve_test_' . $this->_master->ID );

		// trasformare l'array in js
		?>
		<script>
			var didaxoSteps = [];
			<?php 

			foreach( $steps as $step ) 
			{
				echo "didaxoSteps.push({ nonce: '". $step['nonce'] ."', levelId: ". $step['level_id'] .", timerStart: '". $step['timer_start'] ."', timerEnd: '". $step['timer_end'] ."'});\n";
			}

			 ?>
		</script>
		<?php

		add_action( 'wp_ajax_retrieve_test', array( &$this, 'retrieveTest') );

	}

	/**
	 * Ajax test retrieve
	 * @return [type] [description]
	 */
	public function retrieveTest()
	{

		$post_id = $this->_master->ID;
		error_log( var_export( $post_id, true ) );

		if ( !wp_verify_nonce( $_REQUEST['nonce'], "didaxo_retrieve_test_")) {
      		exit("Cosa stai cercando di fare?");
   		}

	}


}