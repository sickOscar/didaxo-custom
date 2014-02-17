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
				_metadataComplete = false,
				_isFlash = false,
				_testBuilt = false;

			var ua = navigator.userAgent;

			var isAndroid = ua.toLowerCase().indexOf("android") > -1;// && ua.indexOf("mobile");
			var isiOS = ( ua.match(/(iPad|iPhone|iPod)/g) ? true : false );
			
			if (isAndroid) {
				// Do something!
				// Redirect to Android-site?
				alert('Device non supportato!');
				base.$video.hide();
				return false;
			}

			if ( isiOS ) {
				var video = base.$video[0];
				addEvent( video, 'contextmenu', function(e) {
					e.preventDefault();
					e.stopPropagation();
				}, false);
				if ( video.hasAttribute('controls') ) {
					video.removeAttribute('controls');
				}
			}



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
				
				this.player = new MediaElementPlayer( base.$video, {
					plugins: ['flash', 'silverlight'],
					features: ['playpause','current','duration','volume'],
					enableKeyboard: false,
					success: function( mediaElement, node, player ) {
						// Flash / silverlight Plugin Bug FIx
						if( mediaElement.pluginType === 'flash' ) {
							// evneto canplay: lanciato quando inizia a caricare il video
							// con plugin flash
							addEvent(mediaElement, 'canplay', function() {

								base.$media = $(mediaElement);
								base.player = mediaElement;

								_loadComplete = true;
								_metadataComplete = true;
								_isFlash = true;

								base.playerReady();
							}, false);
						} else {
							// HTML 5 video
							base.$media = $(mediaElement);
							base.player = mediaElement.player;
							
							addEvent(base.$media[0], 'loadeddata', base.loadComplete, false );
							addEvent(base.$media[0], 'loadedmetadata', base.metadataComplete, false );

						}

						
					},
					error: function() {
						alert( 'Error loading Player!');
					}
				} );
			
			};

			/**
			 * Controllo del caricamento del video
			 * @param  {[type]} e [description]
			 * @return {[type]}   [description]
			 */
			base.loadComplete = function(e) {
				_loadComplete = true;
				base.playerReady();
			};

			/**
			 * Controllo del caricamento dei metadata
			 * @param  {[type]} e [description]
			 * @return {[type]}   [description]
			 */
			base.metadataComplete = function(e) {
				_metadataComplete = true;
				base.playerReady();
			};

			/**
			 * lanciato quando il player è pronto a far partire il video
			 * @param  {[type]} playerId [description]
			 * @return {[type]}          [description]
			 */
			base.playerReady = function(playerId) {
				// controllo nel caso di video HTML5 se sono stati caricati
				// i metadati e l'inizio del video
				if( !(_loadComplete && _metadataComplete) ) {
					return false;
				}

				// se esiste almeno un passo
				if ($.didaxo.steps.length > 0) {

					base.player.setCurrentTime( convertToSeconds($.didaxo.steps[currentStep].timerStart) );
					addEvent(base.$media[0], 'timeupdate', base.stepListener, false );

				}

			};

			/**
			 * listener per l'arrivo del video al termine del currentStep
			 * @param  {[type]} data [description]
			 * @return {[type]}      [description]
			 */
			base.stepListener = function(ev) {
				// controllo per ultima parte di video
				if (currentStep === $.didaxo.steps.length) {
					return false;
				}
				// arrivo nel momento della domanda
				if (parseInt(base.$media[0].currentTime, 10) === convertToSeconds($.didaxo.steps[currentStep].question_time)) {
					base.pause();

					// iOS Fix: rimetto il timer al momento di blocco, per fare in modo
					// che anche schiacciando play non si va avanti
					if ( isiOS ) {
						base.player.setCurrentTime(convertToSeconds($.didaxo.steps[currentStep].question_time));
					}
					
					if ( !_testBuilt ) {
						base.buildTest($.didaxo.steps[currentStep]);
					}
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
					$('form.question-form').slideUp(function() {
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

							// controllo che la risposta abbia un formato corretto
							try {
								response = $.parseJSON(response);
							} catch (e) {
								console.error('Not well formatted JSON');
								console.error(e);
								return false;
							}

							$form = $(response.form);

							// Azioni particolari
							// riservato per eventuali azinoi particolari per caso di risposta
							if (response.result === 'ok') {
								// risposta corretta
							} else {
								// risposta errata
							}

							// Azione in caso di fine del livello padre
							if (response.master === 'ok') {
								console.log('Completato il livello principale');
							}

							$form.hide();
							// appendo il form
							base.$el.after($form);
							$form.slideDown( function() {
								// controllo fullscreen iOS
								
							});


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

					$('form[name="win-form"]').slideUp(function() {
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

					$('form[name="loose-form"]').slideUp(function(ev) {
						$(this).remove();
					});

					// il player torna alla posizione orginale e 
					// viene mostrato il video e fatto partire
					base.resetPlayer( false );
					return false;
				});

			};

			/**
			 * Aumento il player di uno step
			 * @return {[type]} [description]
			 */
			base.nextStep = function() {
				++currentStep;
			};

			/**
			 * nsconde il player
			 * @param  {Function} callback [description]
			 * @return {[type]}            [description]
			 */
			base.hidePlayer = function(callback) {
				if( _isFlash ) {
					// non posso usare una transizione che manda in display: none 
					// bug Flash fallback
					base.$el.css({
						position: 'absolute',
						left: '-9999px'
					});
					if ( callback ) {
						callback.call( this );
					}
				} else {
					base.$el.slideUp(callback);
				}
				
			};

			/**
			 * mostra il player
			 * @param  {Function} callback [description]
			 * @return {[type]}            [description]
			 */
			base.showPlayer = function(callback) {
				if( _isFlash ) {
					// non posso usare una transizione che manda in display: none 
					// bug Flash fallback
					base.$el.css({
						position: 'static'
					});
					if ( callback ) {
						callback.call( this );
					}
				} else {
					base.$el.slideDown(callback);
				}
			};

			/**
			 * resetta il timer del player 
			 * preso dalla variabile currentStep
			 * @return {[type]} [description]
			 */
			base.resetPlayer = function( rightAnswer ) {
				var reset_time;
				

				if( !rightAnswer ) {
					// setto all'inizio del sottolivello
					reset_time = convertToSeconds($.didaxo.steps[currentStep].timerStart );
				} else {
					// setto ad un secondo dopo la domanda
					reset_time = convertToSeconds($.didaxo.steps[currentStep].question_time) + 1;
					base.nextStep();
				}
				
				_testBuilt = false;

				base.showPlayer(function() {
					base.player.setCurrentTime( reset_time );
					//base.play();
				});

			};

			/**
			 * Costruzione del test
			 * @param  {[type]} step
			 * @return {[type]}
			 */
			base.buildTest = function(step) {
				
				_testBuilt = true;
				base.hidePlayer();
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
						
						if ( isiOS ) {
							alert('Chiudi il video e rispondi alla domanda');
						}
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

	function addEvent(obj, evType, fn, useCapture) {
		if (obj.addEventListener) {
			obj.addEventListener(evType, fn, useCapture);
			// console.log( 'added event ' + evType + ' to ' + obj);
			return true;
		} else if (obj.attachEvent) {
			var r = obj.attachEvent("on" + evType, fn);
			return r;
		} else {
			alert("Handler could not be attached");
		}
	}

	function removeEvent(obj, evType, fn) {
		if (obj.removeEventListener) {
			obj.removeEventListener(evType, fn);
			return true;
		} else if (obj.detachEvent) {
			var r = obj.detachEvent("on" + evType, fn);
			return r;
		} else {
			alert("Handler could not be attached");
		}
}

});

jQuery(document).ready(function($) {
		// creazione player
		var wrapper = $('#didaxo-player-wrapper');
		if (wrapper.length) {
			wrapper.didaxo_Player();
		}
	});