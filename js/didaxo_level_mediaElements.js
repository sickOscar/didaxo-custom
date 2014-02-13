;
jQuery(function($) {

	(function($) {

		// creazione namespace
		if (!$.didaxo) {
			$.didaxo = {};
		}

		$.didaxo.steps = didaxoSteps;

		$.didaxo.Player = function(el, options) {

			var base = this;
			base.$el = $(el);
			base.el = el;
			base.$video = base.$el.find('video');
			base.$media = undefined;
			base.player = undefined;
			var currentStep = 0,
				_loadComplete = false,
				_metadataComplete = false;


			base.$el.data("didaxo.Player", base);

			/**
			 * Inizializzazione
			 * @return {[type]} [description]
			 */
			base.init = function() {
				base.options = $.extend({}, $.didaxo.Player.defaultOptions, options);

				base.buildPlayer();

				if ($.didaxo.steps.length > 0) {
					base.waitAnswers();
				}
			};

			/**
			 * [buildPlayer description]
			 *
			 * @return {[type]} [description]
			 */
			base.buildPlayer = function() {
				// vimeoPlayer = base.el.querySelectorAll('iframe');
				// froogaloop = $f(vimeoPlayer[0]);
				// froogaloop.addEvent('ready', base.playerReady);
				
				this.player = new MediaElementPlayer( base.$video, {
					features: ['playpause','current','duration','volume'],
					enableKeyboard: false,
					success: function( media, node, player ) {
						base.$media = $(media);
						base.player = base.$media[0].player;
						// this.player.setCurrentTime( convertToSeconds($.didaxo.steps[currentStep].timerStart) );
						base.$media[0].addEventListener( 'loadeddata', base.loadComplete );
						base.$media[0].addEventListener( 'loadedmetadata', base.metadataComplete );
					}
				} );
			
			};

			/**
			 * Controllo del caricamento del video
			 * @param  {[type]} e [description]
			 * @return {[type]}   [description]
			 */
			base.loadComplete = function(e) {
				console.log( 'loaded Data' );
				_loadComplete = true;
				base.playerReady();
			};

			/**
			 * Controllo del caricamento dei metadata
			 * @param  {[type]} e [description]
			 * @return {[type]}   [description]
			 */
			base.metadataComplete = function(e) {
				console.log( 'loaded Metadata' );
				_metadataComplete = true;
				base.playerReady();
			};

			/**
			 * lanciato quando il player è pronto a far partire il video
			 * @param  {[type]} playerId [description]
			 * @return {[type]}          [description]
			 */
			base.playerReady = function(playerId) {
				console.log( 'player ready');
				if( !(_loadComplete && _metadataComplete) ) {
					console.log( 'falsy');
					return false;
				}
				var playButton = base.$el.find('.play');
				var pauseButton = base.$el.find('.pause');

				playButton.on('click', base.play);
				pauseButton.on('click', base.pause);

				// se esiste almeno un passo
				if ($.didaxo.steps.length > 0) {
					console.log( 'seek to ' + convertToSeconds($.didaxo.steps[currentStep].timerStart));
					base.player.setCurrentTime( convertToSeconds($.didaxo.steps[currentStep].timerStart) );
					// base.player.pause();
					base.$media[0].addEventListener( 'timeupdate', base.stepListener );
				}

			};

			/**
			 * listener per l'arrivo del video al termine del currentStep
			 * @param  {[type]} data [description]
			 * @return {[type]}      [description]
			 */
			base.stepListener = function(ev) {
				// arrivo nel momento della domanda
				if( parseInt(base.$media[0].currentTime, 10) === convertToSeconds($.didaxo.steps[currentStep].question_time) ) {
					base.pause();
					base.$media[0].removeEventListener('timeupdate', base.stepListener);
					base.buildTest( $.didaxo.steps[currentStep] );
				}
			};


			/**
			 * [play description]
			 * @param  {[type]} e [description]
			 * @return {[type]}   [description]
			 */
			base.play = function(e) {
				base.player.play();
				return false;
			};

			/**
			 * pause
			 * @param  {[type]} e [description]
			 * @return {[type]}   [description]
			 */
			base.pause = function(e) {
				base.player.pause();
				return false;
			};

			/**
			 * Eventi di attesa delle azioni di form
			 * @param  {[type]} e [description]
			 * @return {[type]}   [description]
			 */
			base.waitAnswers = function(e) {

				/**
				 * evento submit form questionario
				 * @param  {[type]} ev [description]
				 * @return {[type]}    [description]
				 */
				$('body').on('submit', 'form.question-form', function(submit) {
					submit.preventDefault();

					$form = $(submit.target);

					if ($form.find('input:checked').length === 0) {
						return false;
					}

					// tolgo domanda
					$('form.question-form').fadeOut(function() {
						$(this).remove();
					});

					// controllo se la risposta è corretta
					$.ajax({
						type: "POST",
						// dataType : "json",
						url: didaxo_ajax.ajaxurl,
						data: {
							action: "checkAnswer",
							tu_question_id: $form.data('question-id'),
							tu_answer: $form.find('input:checked').val(),
							nonce: $form.data('nonce')
						},
						success: function(response) {

							var form;

							try {
								response = $.parseJSON(response);
							} catch (e) {
								console.error('Not well formatted JSON');
								console.error(e);
								return false;
							}

							$form = $(response.form);

							if (response.result === 'ok') {
								// risposta corretta
							} else {
								// risposta errata
							}

							if (response.master === 'ok') {
								console.log('Completato il livello principale');
							}

							$form.hide();
							base.$el.after($form);
							$form.fadeIn();


						},
						error: function(error) {
							alert(error);
						}
					});

					return false;
				});

				/**
				 * submit form di vittoria
				 * @param  {[type]} ev [description]
				 * @return {[type]}    [description]
				 */
				$('body').on('submit', 'form[name="win-form"]', function(ev) {

					ev.preventDefault();

					$('form[name="win-form"]').fadeOut(function() {
						$(this).remove();
					});

					base.resetPlayer( true );
					return false;
				});

				/**
				 * submit form di sconfitta
				 * @param  {[type]} ev [description]
				 * @return {[type]}    [description]
				 */
				$('body').on('submit', 'form[name="loose-form"]', function(ev) {

					ev.preventDefault();

					$('form[name="loose-form"]').fadeOut(function(ev) {
						$(this).remove();
					});

					// il player torna alla posizione orginale e 
					// viene mostrato il video e fatto partire
					base.resetPlayer( false );
					return false;
				});

			};

			/**
			 * Aumento il player di uno step e si occupa di far
			 * partire il video correttamente
			 * @return {[type]} [description]
			 */
			base.nextStep = function() {
				++currentStep;
				//base.resetPlayer();
			};

			/**
			 * mostra il player
			 * @param  {Function} callback [description]
			 * @return {[type]}            [description]
			 */
			base.hidePlayer = function(callback) {
				base.$el.slideUp();
			};

			/**
			 * nasconde il player
			 * @param  {Function} callback [description]
			 * @return {[type]}            [description]
			 */
			base.showPlayer = function(callback) {
				base.$el.slideDown(callback);
			};

			/**
			 * resetta il timer del player 
			 * preso dalla variabile currentStep
			 * @return {[type]} [description]
			 */
			base.resetPlayer = function( rightAnswer ) {
				var reset_time;
				if( !rightAnswer ) {
					reset_time = convertToSeconds($.didaxo.steps[currentStep].timerStart);
				} else {
					reset_time = convertToSeconds($.didaxo.steps[currentStep].question_time) + 1;
					base.nextStep();
				}
				base.player.setCurrentTime( reset_time );

				// se non sono arrivato all'ultimo step prima della fine
				// del video
				if (currentStep < $.didaxo.steps.length) {
					// Anche aggiunta listener
					// froogaloop.addEvent('playProgress', base.stepListener);
					console.log( currentStep + '  --  ' + $.didaxo.steps.length);
					base.$media[0].addEventListener( 'timeupdate', base.stepListener );
				}

				base.showPlayer(function() {
					base.play();
				});

			};

			/**
			 * Costruzione del test
			 * @param  {[type]} step
			 * @return {[type]}
			 */
			base.buildTest = function(step) {
				base.$el.slideUp();
				// reperimento dati
				$.ajax({
					type: "POST",
					// dataType : "json",
					url: didaxo_ajax.ajaxurl,
					data: {
						action: "retrieveTest",
						question_id: step.question_id,
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
				play: base.play,
				pause: base.pause
			};

		};

		// opzioni di default
		$.didaxo.Player.defaultOptions = {

		};

		// creazione plugin
		$.fn.didaxo_Player = function(options) {
			return this.each(function() {
				(new $.didaxo.Player(this, options));
			});
		};


	})($);


	$(document).ready(function() {
		// creazione player
		var wrapper = $('#didaxo-player-wrapper');
		if (wrapper.length) {
			wrapper.didaxo_Player();
		}
	});

	/**
	 * Converte in secondi una stringa di minuti
	 * formato m:ss
	 * @param  {[type]} input
	 * @return {[type]}
	 */
	function convertToSeconds(input) {
		var parts = input.split(':'),
			minutes = +parts[0],
			seconds = +parts[1];
		return parseInt(minutes * 60 + seconds, 10);
	}

});