<?php 

namespace TU;

!defined( 'ABSPATH' ) and exit;

/**
 * 
 */
class DidaxoLevel 
{

	public static $_video;
	public static $_video_sd_url;
	public static $_video_hd_url;
	public static $_video_mobile_url;
	public static $_video_hls_url;
	public static $_time_limit;

	public $_master;

	public static $_masterId;


	/**
	 * Costanti di riferimento per i nomi dei campi custom
	 */
	const VIDEO_ID = 'wpcf-video';

	const VIDEO_HD_URL = 'wpcf-video_hd_url';

	const VIDEO_SD_URL = 'wpcf-video_sd_url';

	const VIDEO_MOBILE_URL = 'wpcf-video_mobile_url';

	const VIDEO_HLS_URL = 'wpcf-video_hls_url';

	const TIMER_START = 'wpcf-timer_start';

	const TIMER_END = 'wpcf-timer_end';

	const SHOW_TIME = 'wpcf-show_time';

	const TIME_LIMIT = 'wpcf-time_limit'; // in giorni

	const PASSED_LEVEL_USER_META = 'tu_passed_level_';
	

	/**
	 * [__construct description]
	 */
	public function __construct( $post_admin = null ) 
	{
		if( $post_admin === 'post_admin' )
		{

		} 
		else {
			$this->init();		
		}
	}


	/**
	 * [init description]
	 * @return [type] [description]
	 */
	public function init(){

		global $post;

		add_filter( 'tu_level_options', array( $this, 'level_options' ) );

		wp_localize_script( 'didaxo-level-vimeoapi', 'didaxo_ajax', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' )
			));

		wp_localize_script( 'didaxo-level-mediaelement', 'didaxo_ajax', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' )
			));

		add_action( 'wp_head', array( &$this, 'buildSteps' ) );

		// ottengo il livello tu
		$master_level = new Level( tu()->level->ID );
		$this->_master = &$master_level;

		self::$_masterId = $this->_master->ID;

		// error_log( var_export($this->_master, true) );

		// reperimento risorsa video
		// self::$_video = get_post_meta( $this->_master->ID, self::VIDEO_ID, true);

		self::$_video_sd_url = get_post_meta( $this->_master->ID, self::VIDEO_SD_URL, true);
		self::$_video_hd_url = get_post_meta( $this->_master->ID, self::VIDEO_HD_URL, true);
		self::$_video_mobile_url = get_post_meta( $this->_master->ID, self::VIDEO_MOBILE_URL, true);
		self::$_video_hls_url = get_post_meta( $this->_master->ID, self::VIDEO_HLS_URL, true);

		self::$_time_limit = get_post_meta( $this->_master->ID, self::TIME_LIMIT, true);
		
		// costruzione player vimeo ( tramite shortcode )
		add_shortcode( 'didaxo_vimeo_player', array( &$this, 'buildVimeoPlayerShortcode' ) );
		// costruzione player mediaelement.js ( tramite shortcode )
		add_shortcode( 'didaxo_mediaelement_player', array( &$this, 'buildMediaElementPlayerShortcode' ) );
		// elenco sottolivelli
		add_shortcode( 'didaxo_sublevels_list', array( &$this, 'buildSublevelsList' ) );

	}

	public function buildSublevelsList( $atts )
	{
		ob_start(); ?>
			<ol class="tu-list tu-list-sub-levels">
				<?php 

				$list = wp_list_pages(array(
						'sort_column' => 'menu_order',
						'sort_order'  => 'ASC',
						'echo'        => true,
						'child_of'    => self::$_masterId,
						'title_li'    => '',
						'post_type'   => 'tu_level',
						'walker'      => new DidaxoLevelWalker
				));

				 ?>
			</ol>
		<?php
		return ob_get_clean();
	}

	/**
	 * [buildPlayerShortcode description]
	 * @return [type] [description]
	 */
	public function buildVimeoPlayerShortcode( $atts )
	{
		// caricamento script necessari per API Vimeo
		wp_enqueue_script( 'froogaloop' );
		wp_enqueue_script( 'didaxo-level-vimeoapi' );
		ob_start();
		?>
		<div id="didaxo-player-wrapper">
			<iframe id="didaxo-player" src="http://player.vimeo.com/video/<?php echo self::$_video ?>?api=1&amp;player_id=didaxo-player&amp;badge=0&amp;portrait=0&amp;title=0&amp;byline=0" width="540" height="304" frameborder="0"></iframe>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Costruisce il player con le librerie mediaelement. 
	 * Necessita del plugin Wordpress MediaElementJs
	 * @url: http://wordpress.org/plugins/media-element-html5-video-and-audio-player/
	 * @param  [type] $atts [description]
	 * @return [type]       [description]
	 */
	public function buildMediaElementPlayerShortcode( $atts )
	{
		// reperire tutti i figli del padre

		wp_enqueue_style( 'mediaelement-style');
		// wp_enqueue_style( 'iosfix-style');
		wp_enqueue_script( 'mediaelement' );
		wp_enqueue_script( 'didaxo-level-mediaelement' );

		$can_use = true;

		// Controllo sul limite di giorni per visualizzare il video
		$user_passed_time = get_user_meta( tu()->user->ID, self::PASSED_LEVEL_USER_META . tu()->level->ID, true );
		if( $user_passed_time ) 
		{
			// $limit = get_post_meta( tu()->level->ID, self::TIME_LIMIT, true) * 3600 * 24;
			$limit = 60 * 3600 * 24;

			if( time()  - $limit > intval($user_passed_time)  ) {
				$can_use = false;
			}
		}

		if(!$can_use)
		{
			ob_start(); ?>
 			<div class="message error">
 				<span>Hai superato il limite di <?php echo get_post_meta( tu()->level->ID, self::TIME_LIMIT, true) ?> giorni per visualizzare questo video!</span>
 			</div>
			<?php
			return ob_get_clean();
		}

		ob_start() ?>
		<div id="didaxo-player-wrapper">
			<video width="600" height="337" controls="controls" preload="none"  >
				<!-- MP4 for Safari, IE9, iPhone, iPad, Android, and Windows Phone 7 -->
				<source type="video/mp4" src="<?php echo self::$_video_sd_url; ?>" />
				<!-- <source type="video/mp4" src="<?php echo MEDIAELEMENT_URL ?>/media/echo-hereweare.mp4" /> -->
				<object width="600" height="337" type="application/x-shockwave-flash" data="<?php echo MEDIAELEMENT_URL ?>/flashmediaelement.swf">
					<param name="movie" value="<?php echo MEDIAELEMENT_URL ?>/flashmediaelement.swf" />
					<param name="flashvars" value="controls=true&amp;file=<?php echo urlencode(self::$_video_sd_url); ?>" />
					<!-- <param name="flashvars" value="controls=true&amp;file=<?php echo MEDIAELEMENT_URL ?>/media/echo-hereweare.mp4 ?>" /> -->
					<img src="<?php echo MEDIAELEMENT_URL ?>/background.png" width="600" height="337" alt="No video playback" title="No video playback capabilities, sorry!" />
				</object>		
			</video>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Creazione degli step
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
				$timer_start = get_post_meta( $child->ID, self::TIMER_START, true );
				$timer_end = get_post_meta( $child->ID, self::TIMER_END, true);

				$wp_nonce = wp_create_nonce( 'didaxo_retrieve_level' );

				// question_time indica il momento in cui la domanda deve 
				// venire fuori, all'interno dell'intervallo timer_Start timer_end
				// Tempo assoluto rispetto l'intera lunghezza del video
				$question_time;
				// question_id è l'id della domanda da reperire
				$question_id;

				$questions = $test->get_questions();
				if( !count($questions) ) 
				{
					die('Errore nel reperimento delle domande del test.');
				}
				// domanda random
				$key = array_rand( $questions );
				$quest = $questions[$key];
				// reperire il time di una domanda
				$question_time = get_post_meta( $quest->ID, 'tu_show_time', true );
				$question_id = $quest->ID;

				$steps[] = array( 
					'level_id' => $child->ID,
					'nonce' => $wp_nonce,
					'timer_start' => $timer_start,
					'timer_end' => $timer_end,
					'question_time' => $question_time,
					'question_id' => $question_id
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
			echo "didaxoSteps.push({ nonce: '". $step['nonce'] ."', levelId: ". $step['level_id'] .", timerStart: '". $step['timer_start'] ."', timerEnd: '". $step['timer_end'] ."', question_time: '". $step['question_time'] ."', question_id: '". $step['question_id'] ."'});\n";
		}

		?>
		</script>
		<?php

	}

	/**
	 * - Recupero della domanda attuale via ajax
	 * - Start del test (start del timer, max 90 sec)
	 * @return [type] [description]
	 */
	public static function _ajax_retrieveTest()
	{
		if ( !wp_verify_nonce( $_REQUEST['nonce'], "didaxo_retrieve_level") ) {
			die(json_encode(
				array( 'result' => 'Cosa vuoi?')
			));
		}

		// retrieve question
		$question = Questions::factory( $_REQUEST['question_id'] );
		$test = $question->get_test();

		// ATTENZIONE
		// controllo se l'utente aveva precedentemente abbandonato il test
		// ovvero, se c'è un meta tu_started_test_{$test->ID}, in quel caso lo resetto
		// all'attuale time, così è come se ricominciasse il test
		if( get_user_meta( tu()->user->ID, 'tu_started_test_'. $test->ID, true) )
		{
			update_user_meta( tu()->user->ID, 'tu_started_test_'. $test->ID, time());
		}

		$acl = tu()->user->can_access_test( $test );
		
		if( !$acl[0] ) 
		{
			die(json_encode(array(
				'result' => 'ko',
				'form' => 'Non puoi accedere a questo test: ' . $acl[1]
				)));
		}

		
		$resit = tu()->user->can_resit_test( $test );
		if( !$resit[0] )
		{
			die(json_encode(array(
				'result' => 'ko',
				'form' => 'Non puoi ripetere questo test: ' . !$resit[1]
				)));
		}

		// reset caratteristiche dell'user rispetto al test	
		tu()->user->resit_test( $test );

		// inizia il test
		tu()->user->start_test( $test );

		
		
		// render
		$html = self::render_answers_form( $question );
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
				<span><?php echo apply_filters('the_content', $question->post_content); ?></span>
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
	 * Render del form di errore di risposta
	 * @return [type] [description]
	 */
	public static function render_out_of_time_form( $test )
	{
		$time_limit = self::convert_time_to_seconds( $test->get_time_limit() );
		ob_start(); ?>
		<form action="#" name="loose-form" class="result-form">
			<span class="loose-message message">Non hai dato la risposta nel limite dei <?php echo $time_limit ?> secondi! Riguarda il video e prova ancora!</span><br>
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

		// riferimento alla domanda
		$q = Questions::factory( $_REQUEST['tu_question_id'] );

		$test = $q->get_test();

		// salva la risposta data
		self::save_answer();

		// fine del test
		tu()->user->finish_test( $test );

		// controllo la correttezza del test intero
		// (dipendente dalla pecenutale che si è ottenuta)
		$positive = self::correct_answer( $q );

		$result = array();
		if( $positive === true )
		{
			// TEST SUPERATO
			// controlla se tutti i test dei sottolivelli sono superati
			$master = self::master_level_complete( $test->get_level() ) ? 'ok' : 'ko';

			$result['result'] = 'ok';
			$result['form'] = self::render_right_answer_form( $test );
			$result['master'] = $master;
			// terminato test e generazione del risultato
		}
		else 
		{
			// TEST FALLITO
			$result['result'] = 'ko';
			if( is_array($positive) && $positive['error'] === 'time_limit' )
			{
				$result['form'] = self::render_out_of_time_form( $test );
			}
			else 
			{
				$result['form'] = self::render_wrong_answer_form();	
			}
			
		}
		
		die(json_encode($result));

	}


	/**
	 * [save_answer description]
	 * @return [type] risposta di salvataggio del server
	 */
	public static function save_answer()
	{
		// ottengo la risposta in formato serizlized
		// (necessaria per salvare con ajax in trainup)
		$serialized = '';
		foreach( $_REQUEST as $key=>$value ) 
		{
			$serialized .= $key . '=' . $value . '&';
		}

		// ottengo la risposta
		return Questions::ajax_save_answer( $serialized );
	}


	/**
	 * Check della correttezza di una risposta
	 * @param  [type] $serialized risposta alla domanda in formato serializzato
	 * @param  [type] $question domanda
	 * @return boolean se la risposta è corretta
	 */
	public static function correct_answer( $question )
	{
		$test = $question->get_test();

		$time_limit = self::convert_time_to_seconds( $test->get_time_limit() );
		// controllo tempistiche 
		// deve averci messo meno del tempo limite del test
		// per farlo devo comunque ottenere l'archive appena inserito,
		// dato che cancella il meta tu_started_test quindi non si possono 
		// fare le sottrazioni
		$archive = tu()->user->get_archive( $test->ID );
		if(  $archive['duration'] > $time_limit )
		{
			return array('error' => 'time_limit');
		}
		$passed = $archive['passed'] === '1' ? true : false;

		// $archive = tu()->user->get_result( $test->ID );
		// if( )

		return $passed;

		// controllo sull'intero test (filtro sulle risposte in DidaxoQuestion)
		// return $archive['passed'] === '1' ? true : false;
		
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

		// var_dump( $master );

		// non dovrei mai fare questo controllo
		if( !$master )
		{
			die('Non va bene che tu sia qui');
			return false;
		}

		$children = get_posts(array(
			'post_type' => 'tu_level',
			'post_parent' => $master->ID
			));


		$completed = true;
		foreach( $children as $child )
		{
			$sub = Levels::factory( $child->ID );
			$sub_test = $sub->get_test();
			// controllo se esiste un risultato positivo per 
			// il test associato al sottolivello, se non c'è 
			// almeno per uno, il test non è completato
			$archive = tu()->user->get_archive( $sub_test->ID );

			$passed = $archive['passed'] === '1' ? true : false;
			if( !$passed )
			{
				$completed = false;
			}
		}

		if( $completed )
		{
			// setto un campo meta specifico per l'utente, per dire che ha 
			// già passato questo test
			update_user_meta( tu()->user->ID, self::PASSED_LEVEL_USER_META . $master->ID, time() );
		}

		return $completed;
	}

	/**
	 * Funzione utilyti per convertire le stringhe min:sec in secondi
	 * @param  [type] $str_time [description]
	 * @return [type]           [description]
	 */
	public static function convert_time_to_seconds( $str_time )
	{
		sscanf( $str_time, "%d:%d", $minutes, $seconds );
		return $minutes * 60 + $seconds;
	}

	/**
	 * [level_options description]
	 * @param  [type] $options [description]
	 * @return [type]          [description]
	 */
	public function level_options( $options )
	{
		error_log( var_export($options, true) );
	}


}
