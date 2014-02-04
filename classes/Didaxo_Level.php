<?php 

namespace TU;

!defined( 'ABSPATH' ) and exit;

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
		wp_localize_script( 'didaxo-level', 'didaxo_ajax', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' )
		));

		add_action( 'wp_head', array( &$this, 'buildSteps' ) );

		

		// ottengo il livello tu
		$this->_master = new Level( tu()->level->ID );

		// reperimento risorsa video
		$resources = $this->_master->get_resources();
		$video_array = get_post_custom_values( 'video', $resources[0]->ID);

		self::$_video = $video_array[0];
		
		// costruzione player ( tramite shortcode )
		add_shortcode( 'didaxo_player', array( &$this, 'buildPlayerShortcode' ) );

		
		//error_log( 'add_action');
		
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

			$child_level = Levels::factory( $child->ID );
			$test = $child_level->get_test();

			/**
			 * TODO : aggiungere alla coda solo se il test non è stato passato
			 */

			$timer_start = get_post_custom_values( 'timer_start', $child->ID );
			$timer_end = get_post_custom_values( 'timer_end', $child->ID );

			$wp_nonce = wp_create_nonce( 'didaxo_retrieve_level' );

			$steps[] = array( 
				'level_id' => $child->ID,
				'nonce' => $wp_nonce,
				'timer_start' => $timer_start[0],
				'timer_end' => $timer_end[0],
			);
		}
		$wp_nonce = wp_create_nonce( 'didaxo_retrieve_level' );

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

	}

	/**
	 * Ajax test retrieve
	 * @return [type] [description]
	 */
	public static function _ajax_retrieveTest()
	{
		// if ( !wp_verify_nonce( $_REQUEST['nonce'], "didaxo_retrieve_level") ) {
  //     		exit("Cosa stai cercando di fare?");
  //  		}

		$level = Levels::factory( $_REQUEST['level_id'] );
		$questions = $level->get_test()->get_questions();
		
		// domanda random
		$key = array_rand( $questions );
		$quest = $questions[$key];
		// render
		$html = self::render_answers_form( $quest );
		echo $html;
	}

	public static function render_answers_form( $question )
	{
		// creazione nonce
		$wp_nonce = wp_create_nonce( 'didaxo_check_Answer' );
		// reperire domande
		$answers = $question->get_answers();

		ob_start(); ?>
		<form action="#" class="question-form" data-question-id="<?php echo $question->ID ?>" data-nonce="<?php echo $wp_nonce ?>">
			<div class="question-title">
				<span><?php echo $question->post_title; ?></span>
			</div>
		<?php 
		$index = 0;
		foreach( $answers as $answer): ?>
			<div class="answer">
				<label for="tu_answer[<?php echo $index; ?>]">
					<input type="radio" name="tu_answer" value="<?php echo $answer ?>">
					<?php echo $answer ?>
				</label>
			</div>
		<?php 
		$index++;
		endforeach; ?>
		<div class="submit">
			<input type="submit" value="Conferma Risposta">
		</div>
		</form>
		<?php 
		return ob_get_clean();
	}


	public static function _ajax_checkAnswer()
	{

		// if ( !wp_verify_nonce( $_REQUEST['nonce'], "didaxo_retrieve_level") ) {
  //     		exit("Cosa stai cercando di fare?");
  //  		}
		
		// riferimento alla domanda
		// 
		$q = Questions::factory( $_REQUEST['tu_question_id'] );

		$serialized = '';
		foreach( $_REQUEST as $key=>$value ) 
		{
			$serialized .= $key . '=' . $value . '&';
		}

		// // start test
		if( tu()->user->can_start_test($q->get_test()) ) {
			
			tu()->user->start_test( $q->get_test() );

			$response = Questions::ajax_save_answer( $serialized );

			if( $response['type'] === 'success' )
			{
				// controllo se il risultato è corretto
				$positive_result = Questions::validate_answer( tu()->user , $q );
				$result = array();
				if( $positive_result ) 
				{
					// TEST SUPERATO
					$result['result'] = 'ok';
				}
				else {
					$result['result'] = 'ko';
				}
				echo json_encode($result);
			}

		}

	}

}