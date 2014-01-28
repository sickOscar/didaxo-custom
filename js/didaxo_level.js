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

				froogaloop.addEvent( 'playProgress', function( data ) {
					if( parseInt(data.seconds, 10) == convertToSeconds( $.didaxo.steps[currentStep].timerEnd ) ) {
						froogaloop.api( 'pause' );

						base.buildTest( $.didaxo.steps[currentStep] );

					}
				});
			};

			base.play = function( e ) {
				e.preventDefault();
				froogaloop.api( 'play' );
			};

			base.pause = function( e ) {
				e.preventDefault();
				froogaloop.api( 'pause' );
			};

			base.buildTest = function( step ) {
				base.$el.slideUp();

				$.ajax({
					type : "post",
					dataType : "json",
					url : ajaxurl,
					data : {
						action: "retrieve_test",
						nonce: step.nonce
					},
					success: function(response) {
						console.log( response );
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

	function convertToSeconds( input ) {
		var parts = input.split(':'),
			minutes = +parts[0],
			seconds = +parts[1];
		return (minutes * 60 + seconds).toFixed(3);
	}

});

