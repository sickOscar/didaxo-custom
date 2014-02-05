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

			$archives = tu()->user->get_archives( array( $test->ID ) );

			// indicatore di passaggio del test che si sta analizzando
			$passed = false;
			foreach( $archives as $archive ) 
			{
				if( $archive['test_id'] == $test->ID && $archive['passed']) 
				{
					$passed = true;
				}
				
			}

			// se ho già un risultato valido, non aggiungo alla lista lo step
			if( !$passed ) 
			{
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
	 * Recupero della domanda attuale via ajax
	 * @return [type] [description]
	 */
	public static function _ajax_retrieveTest()
	{
		if ( !wp_verify_nonce( $_REQUEST['nonce'], "didaxo_retrieve_level") ) {
    		die(json_encode(
	    		array( 'result' => 'Cosa vuoi?')
	    	));
 		}


		$level = Levels::factory( $_REQUEST['level_id'] );
		$test = $level->get_test();

		$questions = $level->get_test()->get_questions();
		if( !count($questions) ) 
		{
			die('Errore: Nessuna domanda creata per il test');
		} 
		
		// domanda random
		$key = array_rand( $questions );
		$quest = $questions[$key];
		// render
		$html = self::render_answers_form( $quest );
		die( $html );
	}

	/**
	 * Render del form con le risposte
	 * @param  [type] $question [description]
	 * @return [type]           [description]
	 */
	public static function render_answers_form( $question )
	{
		// creazione nonce
		$wp_nonce = wp_create_nonce( 'didaxo_check_answer' );
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

	/**
	 * Render del form di conferma risposta corretta
	 * @return [type] [description]
	 */
	public static function render_right_answer_form( $test )
	{
		$result = tu()->user->get_result( $test->ID );
		ob_start(); ?>
		<form action="#" name="win-form" class="result-form">
			<span class="win-message message">Test passato con successo</span><br>
			<input type="submit" value="Continua con il video!" >
		</form>
		<?php 
		wp_reset_postdata();
		return ob_get_clean();
	}

	/**
	 * Render del form di errore di risposta
	 * @return [type] [description]
	 */
	public static function render_wrong_answer_form( )
	{
		ob_start(); ?>
		<form action="#" name="loose-form" class="result-form">
			<span class="loose-message message">Non hai dato la risposta corretta! Riguarda il video e prova ancora!</span><br>
			<input type="submit" value="Riguarda il video!" >
		</form>
		<?php 
		return ob_get_clean();
	}

	/**
	 * Controllo Ajax della risposta corretta
	 * @return [type] [description]
	 */
	public static function _ajax_checkAnswer()
	{

		if ( !wp_verify_nonce( $_REQUEST['nonce'], "didaxo_check_answer") ) {
	    	die(json_encode(
	    		array( 'result' => 'Cosa vuoi?')
	    	));
	 	}

	 	// ottengo la risposta in formato serizlized
	 	// (necessaria per salvare con ajax in trainup)
	 	$serialized = '';
		foreach( $_REQUEST as $key=>$value ) 
		{
			$serialized .= $key . '=' . $value . '&';
		}
		
		// riferimento alla domanda
		$q = Questions::factory( $_REQUEST['tu_question_id'] );

		$test = $q->get_test();

		// resetto il test ed effettuo la nuova prova
		$positive = self::reset_test( $q, $serialized );

		// il test finisce compunque perchè la risposta la si è data
		tu()->user->finish_test( $test );

		$result = array();
		if( $positive )
		{
			// TEST SUPERATO
			// controlla se tutti i test dei sottolivelli sono superati
			if( self::master_level_complete( tu()->level ) )
			{
				// setta a completo anche il livello padre
				die( 'COMPLETATO' );
			}

			$result['result'] = 'ok';
			$result['form'] = self::render_right_answer_form( $test );
			// terminato test e generazione del risultato
		}
		else 
		{
			// TEST FALLITO
			$result['result'] = 'ko';
			$result['form'] = self::render_wrong_answer_form();
		}

		

		die(json_encode($result));

	}


	/**
	 * Fa ritentare l'utente di fare una domanda di uno specifico test
	 * @param  [type] $serialized risposta alla domanda in formato serializzato
	 * @param  [type] $question domanda
	 * @return boolean se la risposta è corretta
	 */
	public static function reset_test( $question, $serialized )
	{
		$test = $question->get_test();
		$acl = tu()->user->can_access_test( $test );
		// error_log(var_export($acl, true));
		if( !$acl[0] ) 
		{
			die(json_encode(array(
				'result' => 'ko',
				'form' => 'Non puoi accedere a questo test'
			)));
		}

		
		$resit = tu()->user->can_resit_test( $test );
		if( !$resit[0] )
		{
			die(json_encode(array(
				'result' => 'ko',
				'form' => 'Non puoi ripetere questo test'
			)));
		}

		// reset caratteristiche dell'user rispetto al test	
		tu()->user->resit_test( $test );
		// inizia il test
		tu()->user->start_test( $test );
		// ottengo la risposta
		$response = Questions::ajax_save_answer( $serialized );

		if( $response['type'] === 'success' )
		{
			// controllo se il risultato è corretto
			return Questions::validate_answer( tu()->user , $question );
		}
	}

	/**
	 * Controlla se il livello padre di un certo livello è stato 
	 * completato. Per essere stato completato, deve avere tutti i suoi 
	 * sotto livelli completati
	 * @param  [type] $level [description]
	 * @return [type]        [description]
	 */
	public static function master_level_complete( $level ) 
	{
		// ottengo il padre
		$master = Levels::Factory( $level->post_parent );

		// non dovrei mai fare questo controllo
		if( !$master )
		{
			die('Non va bene che tu sia qui');
			return false;
		}

		$children = get_posts(array(
			'post_type' => 'tu_level',
			'post_parent' => $master->id
		));

		$completed = true;
		foreach( $children as $child )
		{
			$sub = Levels::factory( $child->ID );
			$sub_test = $sub->get_test();
			// controllo se esiste un risultato positivo per 
			// il test associato al sottolivello, se non c'è 
			// almeno per uno, il test non è completato
			$archives = tu()->user->get_archives( array( $sub_test->ID ) );

			// indicatore di passaggio del test che si sta analizzando
			$completed = true;
			foreach( $archives as $archive ) 
			{
				if( $archive['test_id'] == $sub_test && !$archive['passed']) 
				{
					$completed = false;
				}
				
			}
		}

		return $completed;


	}


}