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
			var currentStep = 0,
				vimeoPlayer,
				froogaloop;

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
				vimeoPlayer = base.el.querySelectorAll('iframe');
				froogaloop = $f(vimeoPlayer[0]);
				froogaloop.addEvent('ready', base.playerReady);
			};

			/**
			 * lanciato quando il player è pronto a far partire il video
			 * @param  {[type]} playerId [description]
			 * @return {[type]}          [description]
			 */
			base.playerReady = function(playerId) {
				var playButton = base.$el.find('.play');
				var pauseButton = base.$el.find('.pause');

				playButton.on('click', base.play);
				pauseButton.on('click', base.pause);

				// se esiste almeno un passo
				if ($.didaxo.steps.length > 0) {
					froogaloop.api('seekTo', convertToSeconds($.didaxo.steps[currentStep].timerStart));
					froogaloop.api('pause');
					// add event
					froogaloop.addEvent('playProgress', base.stepListener);
				}


			};

			/**
			 * listener per l'arrivo del video al termine del currentStep
			 * @param  {[type]} data [description]
			 * @return {[type]}      [description]
			 */
			base.stepListener = function(data) {
				if (parseInt(data.seconds, 10) == convertToSeconds($.didaxo.steps[currentStep].question_time)) {
					// remove Event
					froogaloop.removeEvent('playProgress');
					// froogaloop actions
					froogaloop.api('pause');
					base.buildTest($.didaxo.steps[currentStep]);
				}
			};


			/**
			 * [play description]
			 * @param  {[type]} e [description]
			 * @return {[type]}   [description]
			 */
			base.play = function(e) {
				froogaloop.api('play');
				return false;
			};

			/**
			 * pause
			 * @param  {[type]} e [description]
			 * @return {[type]}   [description]
			 */
			base.pause = function(e) {
				froogaloop.api('pause');
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
								alert('Completato il livello principale');
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

					$('form[name="win-form"]').fadeOut(function() {
						$(this).remove();
					});

					base.nextStep();
					return false;
				});

				/**
				 * submit form di sconfitta
				 * @param  {[type]} ev [description]
				 * @return {[type]}    [description]
				 */
				$('body').on('submit', 'form[name="loose-form"]', function(ev) {

					$('form[name="loose-form"]').fadeOut(function(ev) {
						$(this).remove();
					});

					// il player torna alla posizione orginale e 
					// viene mostrato il video e fatto partire
					base.resetPlayer();
					base.showPlayer(function() {
						// Anche aggiunta listener
						froogaloop.addEvent('playProgress', base.stepListener);
						base.play();
					});
					return false;
				});

			};

			/**
			 * Aumento il player di uno step e si occupa di far
			 * partire il video correttamente
			 * @return {[type]} [description]
			 */
			base.nextStep = function() {
				// se non sono arrivato all'ultimo step prima della fine
				// del video
				if (++currentStep < $.didaxo.steps.length) {
					base.resetPlayer();
					// Anche aggiunta listener
					froogaloop.addEvent('playProgress', base.stepListener);
				}

				base.showPlayer(function() {
					base.play();
				});

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
			 * resetta il timer del player all'inizio dello step corrent
			 * preso dalla variabile currentStep
			 * @return {[type]} [description]
			 */
			base.resetPlayer = function() {
				froogaloop.api('seekTo', convertToSeconds($.didaxo.steps[currentStep].timerStart));

				// add event
				froogaloop.addEvent('playProgress', base.stepListener);
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
		return (minutes * 60 + seconds).toFixed(3);
	}

});