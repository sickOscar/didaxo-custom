;jQuery( function($) {

	(function( $ ) {

		// creazione namespace
		if (!$.didaxo) {
			$.didaxo = {};
		}

		$.didaxo.steps = didaxoSteps;

		$.didaxo.Player = function( el, options ) {

			var base = this;
			base.$el = $(el);
			base.el = el;
			var currentStep = 0,
				vimeoPlayer,
				froogaloop;

			base.$el.data( "didaxo.Player" , base );

			base.init = function() {
				base.options = $.extend( {}, $.didaxo.Player.defaultOptions, options );

				base.buildPlayer();

				base.waitAnswers();
			};

			base.buildPlayer = function() {

				vimeoPlayer = base.el.querySelectorAll( 'iframe' );
				froogaloop = $f(vimeoPlayer[0]);

				froogaloop.addEvent( 'ready', base.playerReady );

			};

			base.playerReady = function( playerId ) {
				var playButton = base.$el.find('.play');
				var pauseButton = base.$el.find('.pause');

				playButton.on( 'click', base.play );
				pauseButton.on( 'click', base.pause );

				froogaloop.api( 'seekTo', convertToSeconds( $.didaxo.steps[currentStep].timerStart ) );
				froogaloop.api( 'pause' );

				// add event
				froogaloop.addEvent( 'playProgress', base.stepListener );
			};

			base.stepListener = function( data ) {
				if( parseInt(data.seconds, 10) == convertToSeconds( $.didaxo.steps[currentStep].timerEnd ) ) {
					// remove Event
					froogaloop.removeEvent( 'playProgress' );
					// froogaloop actions
					froogaloop.api( 'pause' );
					base.buildTest( $.didaxo.steps[currentStep] );
				}
			};

			base.play = function( e ) {
				e.preventDefault();
				froogaloop.api( 'play' );
			};

			base.pause = function( e ) {
				e.preventDefault();
				froogaloop.api( 'pause' );
			};

			base.waitAnswers = function( e ) {

				$('body').on('submit', 'form.question-form',  function( submit ) {
					submit.preventDefault();

					$form = $(submit.target);

					if( $form.find('input:checked').length === 0 ) {
						return false;
					}

					console.log( 'form submitted' );
					// controllo se la risposta è corretta
					$.ajax({
						type : "POST",
						// dataType : "json",
						url : didaxo_ajax.ajaxurl,
						data : {
							action: "checkAnswer",
							tu_question_id: $form.data('question-id'),
							tu_answer: $form.find('input:checked').val(),
							nonce: $form.data('nonce')
						},
						success: function(response) {
							console.log( response );
						},
						error: function(error) {
							alert(error);
						}
					});

					return false;
				});

			};

			/**
			 * Costruzione del test
			 * @param  {[type]} step
			 * @return {[type]}
			 */
			base.buildTest = function( step ) {
				base.$el.slideUp();
				// reperimento dati
				$.ajax({
					type : "POST",
					// dataType : "json",
					url : didaxo_ajax.ajaxurl,
					data : {
						action: "retrieveTest",
						level_id: step.levelId,
						nonce: step.nonce
					},
					success: function(response) {
						base.$el.after(response);
					},
					error: function(error) {
						alert(error);
					}
				});

			};

			base.init();

			return {
				play : base.play,
				pause: base.pause
			};

		};

		$.didaxo.Player.defaultOptions = {

		};

		$.fn.didaxo_Player = function ( options ) {
			return this.each( function() {
				(new $.didaxo.Player( this, options) );
			});
		};


	})($);


	$(document).ready( function() {
		var wrapper = $('#didaxo-player-wrapper');
		if( wrapper.length ) {
			wrapper.didaxo_Player();
		}
	});

	/**
	 * Converte in secondi una stringa di minuti
	 * @param  {[type]} input
	 * @return {[type]}
	 */
	function convertToSeconds( input ) {
		var parts = input.split(':'),
			minutes = +parts[0],
			seconds = +parts[1];
		return (minutes * 60 + seconds).toFixed(3);
	}

});

