// Source Chooser Plugin
(function($) {

	$.extend(mejs.MepDefaults, {
		sourcechooserText: 'Quality Chooser'
	});

	$.extend(MediaElementPlayer.prototype, {
		buildsourcechooser: function(player, controls, layers, media) {

			var t = this;

			player.sourcechooserButton =
				// $('<div class="mejs-button mejs-sourcechooser-button">'+
				// 	'<button type="button" aria-controls="' + t.id + '" title="' + t.options.sourcechooserText + '"></button>'+
				// 	'<div class="mejs-sourcechooser-selector">'+
				// 		'<ul>'+
				// 		'</ul>'+
				// 	'</div>'+
				// '</div>')
				// 	.appendTo(controls)
				$(
					'<div class="mejs-button mejs-sourcechooser-button">'+
						'<a title="quality">HD</a>'+
					'</div>'
				).appendTo(controls)

					// hover
					.hover(function() {
						$(this).find('.mejs-sourcechooser-selector').css('visibility','visible');
					}, function() {
						$(this).find('.mejs-sourcechooser-selector').css('visibility','hidden');
					})

					// handle clicks to the language radio buttons
					.delegate('a', 'click', function(ev) {

						ev.preventDefault();

						currentTime = media.currentTime;
						media.pause();

						if( media.currentSrc === t.standardSource ) {
							media.setSrc(t.hdSource);
							$('a[title=quality]').addClass('active');
						}
						if( media.currentSrc === t.hdSource ) {
							media.setSrc(t.standardSource);	
							$('a[title=quality]').removeClass('active');
						}

						t.setCurrentTime( currentTime );

						// media.play();

						// ev.preventDefault();
						// src = this.value;

						// console.log('quality choose');

						// media.pause();

						// if (media.currentSrc != src) {
						// 	currentTime = media.currentTime;
						// 	paused = media.paused;
						// 	media.setSrc(src);
						// 	if (!paused) {
						// 		//media.play();
						// 	}
						// }
						return false;
					});

			// add to list
			for (var i in media.children) {
				src = media.children[i];
				if (src.nodeName === 'SOURCE' && (media.canPlayType(src.type) == 'probably' || media.canPlayType(src.type) == 'maybe')) {
					// player.addSourceButton(src.src, src.title, media.currentSrc == src.src);
					player.addSource(src.src, src.title, media.currentSrc == src.src);
				}
			}

		},

		addSource: function( src, label, isCurrent ) {
			var t = this;
			if( label === 'sd' ) {
				this.standardSource = src;
			}
			if( label === 'hd' ) {
				this.hdSource = src;
			}
		},

		addSourceButton: function(src, label, isCurrent) {
			var t = this;
			if (label === '' || label === undefined) {
				label = src;
			}

			t.sourcechooserButton.find('ul').append(
				$('<li>'+
					'<input type="radio" name="' + t.id + '_sourcechooser" id="' + t.id + '_sourcechooser_' + label + '" value="' + src + '" ' + (isCurrent ? 'checked="checked"' : '') + ' />'+
					'<label for="' + t.id + '_sourcechooser_' + label + '">' + label + '</label>'+
				'</li>')
			);

			t.adjustSourcechooserBox();

		},

		adjustSourcechooserBox: function() {
			var t = this;
			// adjust the size of the outer box
			t.sourcechooserButton.find('.mejs-sourcechooser-selector').height(
				t.sourcechooserButton.find('.mejs-sourcechooser-selector ul').outerHeight(true)
			);
		}
	});

})(mejs.$);