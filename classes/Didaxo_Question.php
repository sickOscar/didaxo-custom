<?php 

namespace TU;

!defined( 'ABSPATH' ) and exit;


class DidaxoQuestion
{

	const DIDAXO_QUESTION = 'didaxo_question';

	public static $_validated = false;

	public function __construct()
	{
		add_filter('tu_question_types', array($this, 'add_type'));
		add_filter('tu_question_meta_boxes', array($this, 'add_meta_box'), 10, 2);
		add_action('tu_meta_box_' . self::DIDAXO_QUESTION, array($this, 'meta_box'));
		add_action('tu_save_question_' . self::DIDAXO_QUESTION, array($this, 'save_question'));
		add_filter('tu_validate_answer_' . self::DIDAXO_QUESTION, array( $this, 'validate_answer'), 10, 3);

		// forzare l'update della domanda anche se un utente ha già 
		// cominciato il test (ovviare strano comportamento options)
		add_filter( 'tu_can_edit_test', array( $this, 'force_question_update') );
	}

	/**
	 * Aggiunge il nuovo tipo di domanda
	 */
	public function add_type( $types )
	{
		
		$types[self::DIDAXO_QUESTION] = __('Domanda Didaxo', 'trainup');
		return $types;
	}

	/**
	 * Aggiunta metabox custom
	 */
	public function add_meta_box( $meta_boxes )
	{
		
		$meta_boxes[self::DIDAXO_QUESTION] = array(
			'title'    => __('Opzioni domanda Didaxo', 'trainup'),
			'context'  => 'advanced',
			'priority' => 'default'
			);

		return $meta_boxes;
	}

	/**
	 * Render meta box custom
	 * @return [type] [description]
	 */
	public function meta_box()
	{
		$show_time = get_post_meta(tu()->question->ID, 'tu_show_time', true);
		echo new View( DIDAXO_DIR . 'view/didaxo_question', array(
			'id'       => 'answer_'. self::DIDAXO_QUESTION .'_template',
			'question' => tu()->question,
			'answers'  => tu()->question->answers,
			'correct_answer' => tu()->question->correct_answer,
			'show_time' => $show_time
			));
	}

	/**
	 * Salvataggio domanda custom
	 * @param  [type] $question [description]
	 * @return [type]           [description]
	 */
	public function save_question( $question ) 
	{
		// error_log( 'save question' );
		if ( !isset($_POST['multiple_answer']) || count($_POST['multiple_answer']) < 2 )
		{
			wp_die(__('Bisogna inserire almeno 2 risposte', 'trainup'));
		}

		if( !isset($_POST['correct_answer']) )
		{
			wp_die(__('Bisogna scegliere una risposta corretta', 'trainup'));
		}

		if ( !isset($_POST['show_time']) || $_POST['show_time'] === '' )
		{
			wp_die(__('Non è stato aggiunto il tempo di uscita della domanda', 'trainup'));
		}

		update_post_meta($question->ID, 'tu_show_time', $_POST['show_time']);
		update_post_meta($question->ID, 'tu_answers', $_POST['multiple_answer']);
		update_post_meta($question->ID, 'tu_correct_answer', $_POST['multiple_answer'][$_POST['correct_answer']]);
	}

	/**
	 * Filtro per la validazione delle risposte di tipo didaxo_custom
	 * @param  [type] $correct      [description]
	 * @param  [type] $users_answer [description]
	 * @param  [type] $question     [description]
	 * @return [type]               [description]
	 */
	public static function validate_answer( $correct, $users_answer, $question )
	{
		
		$question_type  = $question->get_type();
		$users_answer   = tu()->user->get_answer_to_question($question->ID);
		$correct_answer = $question->get_correct_answer();

		if ( $question_type === self::DIDAXO_QUESTION ) {
			$correct = $users_answer == $correct_answer;
		}
		return $correct;
	}

	/**
	* Forza l'update della domanda anche se il test è stato iniziato
	* da un utente
	* @return [type] [description]
	*/
	public function force_question_update( $question )
	{
		return true;
	}

}