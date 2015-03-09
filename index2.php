<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<title>Document1</title>
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
	<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/animate.css/3.2.1/animate.min.css">
	<link rel="stylesheet" href="style.css">
</head>
<body>

<a href="#mcont1" class="modaler">open1</a>
<div id="mcont1" data-animationOpen="bounceInRight">modal-content1</div>

<hr>

<a href="#mcont2" class="modaler">open2</a>
<div id="mcont2" data-ovclickclose="false">modal-content2<br><a href="#mcont3" class="modaler">change</a></div>
<div id="mcont3">modal-content3</div>
<hr>

<a href="ajax.php" class="modaler">ajax</a>
<hr>

<a href="ajax.php" id="tester">tester</a>
<hr>




<script>
document.write((!document.addEventListener ? (!document.querySelector ? 'IE7' : 'IE8') : 'Not IE8'));
</script>

<script src="//code.jquery.com/jquery-1.10.2.min.js"></script>
<script>
if (!window.console) {
	window.console = {log:function(){},info:function(){}};
}

/*
modalbeit

albeit・・・…ではあるが(although)；…であろうとも(even though)
*/
(function(w, $){
	'use strict';

	// IE7以下は対象外
	if (!document.querySelector) {
		$.fn.modaler = function(){};
		return false;
	}

	// デフォルト設定
	var pluginName = 'modaler',

	defaults = $.extend({
		ovDisableClose: false,
		ovOpacity: 0.2,
		autoHide: 0,
		preload: true,
		acShow: 'fadeInDown',
		acHide: 'fadeOutDown',
		acChangeHide: 'flipOutY',
		acChangeShow: 'flipInY',
		acSpinShow: 'zoomIn',
		acSpinHide: 'zoomOut',
		acDisableClose: 'wobble'
	}, w.modalbeitGlobals),

	isOldIE = (!document.addEventListener),

	animationListener = (function(){
		if (isOldIE) {
			return false;
		}

		var style = document.documentElement.style;

		if (style['animationName'] !== undefined) {
			return {start: 'animationstart', end: 'animationend'};
		} else if (style['webkitAnimationName'] !== undefined) {
			return {start: 'webkitAnimationStart', end: 'webkitAnimationEnd'};
		} else if (style['MozAnimationName'] !== undefined) {
			return {start: 'animationstart', end: 'animationend'};
		} else if (style['OAnimationName'] !== undefined) {
			return {start: 'oanimationstart', end: 'oanimationend'};
		}
	})(),

	$body = $('body'), $overlay, $stage, $que, $cover, $spinner, $current,

	currentSelector,
	isAnimating = false,
	isCssAnimating = false,
	spinnerTimer = null,
	autoHideTimer = null,
	animationCallback = {},
	xhr = {},
	cache = {};

	// initialize
	$body.addClass('-' + pluginName + (isOldIE ? ' -old-ie' : ''));
	$que = $('<div class="-' + pluginName + '-que"/>')
		.appendTo($body);
	$overlay = $('<div class="-' + pluginName + '-ov"/>')
		.css('opacity', 0)
		.appendTo($body);
	$stage = $('<td/>')
		.appendTo($('<tr/>')
		.appendTo($('<table class="-' + pluginName + '-stg"/>')
		.appendTo($body)))
		.on('click', function(event){
			if ('TD' !== event.target.tagName || isAnimating || isCssAnimating) {
				return false;
			}

			if ($current) {
				if ($current.option.ovDisableClose) {
					$current.addClass($current.option.acDisableClose);
				} else {
					$current.hide();
				}
			}
		});
	$cover = $('<td/>')
		.appendTo($('<tr/>').appendTo($('<table class="-' + pluginName + '-cv"/>').appendTo($body)))
		.on({
			show: function(){
				$body.addClass('-' + pluginName + '-covered');
			},
			hide: function() {
				var self = $(this);
				if (spinnerTimer) {
					if (animationListener) {
						animationCallback[defaults.acSpinHide] = function(){
							animationCallback[defaults.acSpinHide] = null;
							clearTimeout(spinnerTimer);
							$body.removeClass('-' + pluginName + '-covered -' + pluginName + '-loading');
							$spinner.removeClass(defaults.acSpinHide);
						};
						$spinner.removeClass(defaults.acSpinShow).addClass(defaults.acSpinHide);
					} else {
						self.fadeTo('fast', 0, function(){
							clearTimeout(spinnerTimer);
							$body.removeClass('-' + pluginName + '-covered -' + pluginName + '-loading');
							self.fadeTo(0, 1);
						});
					}
				} else {
					$body.removeClass('-' + pluginName + '-covered -' + pluginName + '-loading');
				}
			},
			loading: function() {
				$spinner.addClass(defaults.spinnershow);
				var count = 0, step = [0,1,2,6,10,9,8,4], text;
				spinnerTimer = setInterval(function() {
					text = ['&#9632;','&#9632;','&#9632;','<br>','&#9632;','&#9633;','&#9632;','<br>','&#9632;','&#9632;','&#9632;'];
					text[step[count]] = '&#9633;';
					$spinner.html(text.join(''));
					count = (count == 7) ? 0 : count +1;
				}, 100);
				$body.addClass('-' + pluginName + '-loading');
			}
		});
	$spinner = $('<div class="animated -' + pluginName + '-sp"></div>')
		.appendTo($cover);

	if (animationListener) {
		$spinner.get(0).anim = true;
		$spinner.on(animationListener.end, function(e){
			if (!e.target.anim) {
				return;
			}
			var name = e.originalEvent.animationName;
			if ('function' === typeof animationCallback[name]) {
				animationCallback[name]();
			}
		});
	}

	/*$cover.show = function() {
		$body.addClass('-' + pluginName + '-covered');
	};

	$cover.hide = function() {
		if (spinnerTimer) {
			if (animationListener) {
				animationCallback[defaults.acSpinHide] = function(){
					animationCallback[defaults.acSpinHide] = null;
					clearTimeout(spinnerTimer);
					$body.removeClass('-' + pluginName + '-covered -' + pluginName + '-loading');
					$spinner.removeClass(defaults.acSpinHide);
				};
				$spinner.removeClass(defaults.acSpinShow).addClass(defaults.acSpinHide);
			} else {
				$cover.fadeTo('fast', 0, function(){
					clearTimeout(spinnerTimer);
					$body.removeClass('-' + pluginName + '-covered -' + pluginName + '-loading');
					$cover.fadeTo(0, 1);
				});
			}
		} else {
			$body.removeClass('-' + pluginName + '-covered -' + pluginName + '-loading');
		}
	};

	$cover.loading = function() {
		$spinner.addClass(defaults.spinnershow);
		var count = 0, step = [0,1,2,6,10,9,8,4], text;
		spinnerTimer = setInterval(function() {
			text = ['&#9632;','&#9632;','&#9632;','<br>','&#9632;','&#9633;','&#9632;','<br>','&#9632;','&#9632;','&#9632;'];
			text[step[count]] = '&#9633;';
			$spinner.html(text.join(''));
			count = (count == 7) ? 0 : count +1;
		}, 100);
		$body.addClass('-' + pluginName + '-loading');
	};*/

	function ajax(url, option, callback){
		if (cache[url] && 'function' === typeof callback) {
			callback(cache[url]);
		} else {
			var success = function(res){
				xhr[url] = null;
				if (res.indexOf('</body>') != -1) {
					res = res.replace(/^[\s\S]*?<body([^>]+)?>([\s\S]*?)?<\/body>[\s\S]*?$/i, '$2');
				}
				var instance = new Modal(res, option);
				cache[url] = instance;
				if ('function' === typeof callback) {
					callback(instance);
				}
			};

			if (xhr[url]) {
				xhr[url].success(success);
			} else {
				xhr[url] = $.get(url, success);
			}
		}
	}


	function Modal(element, option)
	{
		element = this.become(element);
		this.option = $.extend({}, defaults, option, element.data());
		this.element = element;
	}

	Modal.prototype = {
		constructor: Modal,

		become: function(element){
			if('string' === typeof element || !element instanceof $) {
				element = $('<div/>').append(element);
			}

			if (!element.data(pluginName + 'On')) {

				if (currentSelector) {
					element.find(currentSelector)[pluginName]();
				}

				element = $('<div class="animated -' + pluginName + '-container"/>')
						.append(element)
						.appendTo($que)
						.data(pluginName + 'On', true);

				if (animationListener) {
					var source = element.get(0);
					source['is_' + pluginName] = true;

					element.on(animationListener.start, function(e){
						if (!e.target['is_' + pluginName]) return;
						isCssAnimating = true;
					});

					element.on(animationListener.end, function(event){
						if (!event.target['is_' + pluginName]) return;
						var name = event.originalEvent.animationName;
						$(event.target).removeClass(name);
						isCssAnimating = false;
						if ('function' === typeof animationCallback[name]) {
							animationCallback[name]();
						}
					});
				}
			}

			return element;
		},

		a: function() {
			if (isAnimating || isCssAnimating) {
				return;
			}
			isAnimating = true;
			return true;
		},

		auto: function() {
			if ($current.option.autoHide && 'number' === typeof $current.option.autoHide) {
				clearTimeout(autoHideTimer);
				autoHideTimer = setTimeout(function() {
					$current.hide();
				}, $current.option.autoHide);
			}
			return $current;
		},

		show: function(){
			if (!this.a()) return;

			var self = this;

			// すでに表示している場合
			if ($current && $current !== self) {
				if (animationListener) {
					animationCallback[self.option.acChangeHide] = function(){
						animationCallback[self.option.acChangeHide] = null;
						$body.removeClass('-' + pluginName + '-fast');
						$current.element.removeClass($current.option.acChangeHide).appendTo($que);
						$current = self;
						self.auto().element.appendTo($stage).addClass(self.option.acChangeShow);
						$cover.hide();
					};

					isAnimating = false;
					$body.addClass('-modaler-fast');
					$current.element.addClass($current.option.acChangeHide);
				} else {
					$current.element.fadeTo('fast', 0, function() {
						$current.element.appendTo($que).fadeTo(0, 1);
						self.element.fadeTo(0, 0).appendTo($stage).fadeTo('normal', 1, function() {
							isAnimating = false;
							isCssAnimating = false;
							$current= self.auto();
							$cover.hide();
						});
					});
				}
			}

			// 初期状態から
			else {
				$body.addClass('-' + pluginName + '-open');
				$overlay.stop().fadeTo('normal', parseFloat(self.option.ovOpacity), function(){
					isAnimating = false;
				});
				self.element.appendTo($stage).addClass(self.option.acShow);
				$current = self;

				self.auto();
			}

			return self;
		},

		hide: function() {
			if (!this.a()) return;
			if (!$current || $current !== this) return;

			if (autoHideTimer) {
				clearTimeout(autoHideTimer);
			}

			$current.element.addClass($current.option.acHide);
			$body.addClass('-' + pluginName + '-closing');
			$overlay.stop().fadeTo('normal', 0, function(){
				$overlay.hide();
				$body.removeClass('-' + pluginName + '-closing -' + pluginName + '-open');
				$current.element.removeClass($current.option.acHide).appendTo($que);
				$current = null;
				isAnimating = false;
				isCssAnimating = false;
			});

			return this;
		}
	};


	$.fn[pluginName] = function(option) {
		var self, target, instance, id, href;
		currentSelector = this.selector;

		return this.each(function() {
			self = $(this);
			option = $.extend({}, defaults, option, self.data());

			if (!this[pluginName + 'Target']) {
				// 対象がインライン要素の場合
				id = option.target || self.attr('href');
				if ('#' === id[0] || '.' === id[0]) {
					target = $(id).first();

					if (!target.length) {
						return true;
					}

					this[pluginName + 'Target'] = target;
					instance = new Modal(target, option);
					self.on('click.' + pluginName, function() {
						instance.show();
					});
				} else if(self.is('a')) {
					href = self.prop('href');
					if (option.preload) {
						ajax(href);
					}

					self.on('click', function(event) {
						event.preventDefault();
						if (!cache[href]) {
							$cover.trigger('loading');
						}
						ajax(href, option, function(instance){
							self.get(0)[pluginName + 'Target'] = instance.element;
							$cover.trigger('hide');
							instance.show();
						});
					});
				}
			}
		});
	};

	


	$(function(){
		
		$[pluginName] = function(unit, opt) {
			return new Modal(unit, opt);
		};

		$.modaler('<div>test<br><a href="ajax.php" class="modaler">second</a></div>').show();
	});
})(window, jQuery);

$('.modaler').modaler();

</script>

</body>
</html>