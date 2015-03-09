;(function(w, $){
	'use strict';

	// IE7以下は対象外
	if (!$ || !document.querySelector) {
		return false;
	}

	// デフォルト設定
	var pluginName = 'modaled',
	prefix = '-' + pluginName,

	defaults = $.extend({
		header: null,
		footer: null,
		containerClass: 'modal-container',
		headerClass: 'modal-header',
		footerClass: 'modal-footer',
		contentClass: 'modal-content',
		hideButtonClass: '.hide',
		overlayClose: false,
		overlayOpacity: 0.2,
		autoHide: 0,
		preload: false,
		acShow: 'fadeInDown',
		acHide: 'fadeOutDown',
		acChangeHide: 'flipOutY',
		acChangeShow: 'flipInY',
		acSpinShow: 'zoomIn',
		acSpinHide: 'zoomOut',
		acDisableClose: 'wobble',
		beforeShow: null,
		afterShow: null,
		beforeClose: null,
		afterClose: null
	}, w.modalbeitGlobals),

	isOld = (!w.addEventListener),
	isAnimateCss = (function(){
		var c = $('<div class="animated" style="display:none"></div>')
			.appendTo(document.body);
		var s = c.css('animationDuration');
		c.remove();
		return (s !== '0s');
	})(),

	animationListener = (function(){
		if (isOld || !isAnimateCss) {
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

	currentSelector,
	queues = [],
	isAnimating = false,
	isCssAnimating = false,
	spinnerTimer = null,
	autoHideTimer = null,
	//animationCallback = {},
	xhr = {},
	cache = {},

	$body = $('body').addClass(prefix + (isOld ? ' ' + prefix + '-olds' : '')),
	$current,

	$queue = $('<div class="' + prefix + '-que"/>').appendTo($body),

	$overlay = $('<div class="' + prefix + '-ov"/>').appendTo($body),

	$stage = $('<div class="animated ' + prefix + '-stage"/>')
		.appendTo(
			$('<div/>').appendTo($('<div class="' + prefix + '-stgwrap"/>').appendTo($body)).on('click', function(event) {
				if (event.target === this) {
					event.preventDefault();
					if ($current && !isAnimating && !isCssAnimating) {
						var option = $current.data('option');
						if (option.overlayClose) {
							$body.addClass(prefix + '-stop');
							if (animationListener) {
								$stage.get(0)[pluginName] = function() {
									$body.removeClass(prefix + '-stop');
								};
								$stage.addClass(option.acDisableClose);
							} else {
								isAnimating = true;
								$stage.stop().fadeTo('fast', 0.1, function() {
									$stage.fadeTo('fast', 1, function() {
										$stage.fadeTo('fast', 0.1, function() {
											$stage.fadeTo('fast', 1, function() {
												$body.removeClass(prefix + '-stop');
												isAnimating = false;
											});
										});
									});
								});
							}
							
							autoHide();
						} else {
							$stage.trigger('hide');
						}
					}
				}
			})
		)
		.on({
			show: function(event, element){
				if (isAnimating || isCssAnimating) return;
				var self = this, target = $(element), data = target.data();
				
				$body.addClass(prefix + '-open' + ' ' + prefix + '-stop');
				runEvent('beforeShow', data);

				if (!$current) {
					$overlay.fadeTo('slow', data.option.overlayOpacity);
					var func = function(){
						$body.removeClass(prefix + '-stop');
						isAnimating = false;
						runEvent('afterShow', data);
						autoHide();
						isAnimating = false;
						isCssAnimating = false;
					};
					if (animationListener) {
						this[pluginName] = func;
						$stage.append(target).addClass(data.option.acShow);
					} else {
						$stage.stop().append(target).fadeTo('normal', 1, func);
					}

					$current = target;
				} else if ($current !== target) {
					var currentData = $current.data();
					if (animationListener) {
						$body.addClass(prefix + '-fast');
						self[pluginName] = function(){
							$current.appendTo($queue);
							setTimeout(function(){
								self[pluginName] = function(){
									$body.removeClass(prefix + '-fast ' + prefix + '-stop');
									$current = target;
									runEvent('afterShow', data);
									autoHide();
									isAnimating = false;
									isCssAnimating = false;
								};
								$stage.append(target).addClass(data.option.acChangeShow);
							}, 100);
						};
						$stage.addClass(currentData.option.acChangeHide);
					} else {
						$stage.stop().fadeTo('fast', 0, function() {
							$current.appendTo($queue);
							$stage.append(target).fadeTo('normal', 1, function(){
								$body.removeClass(prefix + '-fast ' + prefix + '-stop');
								$current = target;
								runEvent('afterShow', data);
								autoHide();
								isAnimating = false;
								isCssAnimating = false;
							});
						});
					}
				}
			},

			hide: function() {
				if (isAnimating || isCssAnimating || !$current) return;
				if (autoHideTimer) {
					clearTimeout(autoHideTimer);
					autoHideTimer = null;
				}

				var data = $current.data();
				$body.addClass(prefix + '-stop');
				runEvent('beforeHide', data);
				$overlay.fadeTo('slow', 0);
				var func = function() {
					$body.removeClass(prefix + '-stop ' + prefix + '-open');
					$current.appendTo($queue);
					$current = null;
					runEvent('afterHide', data);
					isAnimating = false;
					isCssAnimating = false;
				};
				if (animationListener) {
					this[pluginName] = func;
					$stage.addClass(data.option.acHide);
				} else {
					$stage.stop().fadeTo('normal', 0, func);
				}
			}
		}),

	$spinner = $('<div class="animated ' + prefix + '-sp"/>').appendTo($body).on({
		show: function(event, callback) {
			var self = this;
			if (self.showed) {
				return false;
			}
			self.showed = true;
			$body.addClass(prefix + '-loading');

			var count = 1, text, step = [0,1,2,6,10,9,8,4], on = '&#9632;', off = '&#9633;',
			spin = function() {
				text = [on,on,on,'<br>',on,off,on,'<br>',on,on,on];
				text[step[count]] = off;
				self.innerHTML = text.join('');
				count = (count == 7) ? 0 : count +1;
			};
			spin();
			self.timer = setInterval(spin, 100);

			if (animationListener) {
				$(self).addClass(defaults.acSpinShow);
			} else {
				$(self).stop().fadeTo('fast', 1);
			}
		},
		hide: function(event, callback) {
			var self = this;
			if (!self.showed) {
				return false;
			}

			if (self.timer) {
				clearInterval(self.timer);
				self.timer = null;
			}

			var func = function() {
				this.showed = false;
				$body.removeClass(prefix + '-loading');
				$(this).removeClass(defaults.acSpinHide);
			};
			if (animationListener) {
				self[pluginName] = func;
				$(self).removeClass(defaults.acSpinShow).addClass(defaults.acSpinHide);
			} else {
				$(self).stop().fadeTo('fast', 0, func);
			}
		}
	});

	if (animationListener) {
		var animationEvent = function(event){
			if (!event.target[pluginName]) return;
			var name = event.originalEvent.animationName;
			$(event.target).removeClass(name);
			if ('function' === typeof event.target[pluginName]) {
				event.target[pluginName]();
			}
			event.target[pluginName] = null;
		};

		$spinner.on(animationListener.end, animationEvent);
		$stage.on(animationListener.end, animationEvent);
	} else {
		$overlay.css('opacity', 0);
		$stage.css('opacity', 0);
		$spinner.css('opacity', 0);
	}


	function attach(element, option)
	{
		if (!element.data || !element.data(pluginName + 'On')) {
			option = $.extend({}, defaults, option);
			var wrapper = $('<div class="' + prefix + '-container ' + option.containerClass + '"/>');

			var content = $('<div class="' + prefix + '-content ' + option.contentClass + '"/>');
			if ('string' === typeof element) {
				content.html(element);
				content.data(pluginName + 'On', true);
			} else {
				if (element.data) {
					option = $.extend({}, option, element.data());
				}
				element.data(pluginName + 'On', true);
				content.append(element);
			}
			
			if (option.header) {
				var header = $('<div class="' + option.headerClass + '"/>');
				if ('string' === typeof option.header) {
					header.html(option.header);
				} else {
					header.append($(option.header));
				}
				wrapper.append(header);
			}

			wrapper.append(content);

			if (option.footer) {
				var footer = $('<div class="' + option.footerClass + '"/>');
				if ('string' === typeof option.footer) {
					footer.html(option.footer);
				} else {
					footer.append($(option.footer));
				}
				wrapper.append(footer);
			}

			element = wrapper.appendTo($queue).data({option: option, content: content});

			wrapper.find('form').on('submit.' + pluginName, function() {
				var self = $(this),
				href = self.prop('action'),
				method = self.prop('method') || 'get',
				data = self.serialize();

				if (href && data) {
					if ('get' == method.toLowerCase()) {
						href += (href.indexOf('?') == -1 ? '?' : '&') + data;
						data = {};
					}

					ajax(href, option, function(modal){
						$spinner.trigger('hide');
						$stage.trigger('show', modal);
					}, data);

					return false;
				}
			});

			if (currentSelector) {
				wrapper.find(currentSelector)[pluginName]();
			}
		}

		return element;
	}

	function ajax(url, option, callback, postData){
		if (cache[url] && 'function' === typeof callback) {
			$spinner.trigger('hide');
			callback(cache[url]);
		} else {
			if (!option || (option && !option.preload) || xhr[url] || postData) {
				$spinner.trigger('show');
			}
			
			var success = function(res){
				xhr[url] = null;
				if (res.indexOf('</body>') != -1) {
					res = res.replace(/^[\s\S]*?<body([^>]+)?>([\s\S]*?)?<\/body>[\s\S]*?$/i, '$2');
				}
				if ('<' === res[0] && '>' === res.substr(-1)) {
					var target = attach(res, option);
				} else {
					var target = attach($('<div/>').html(res), option);
				}
				cache[url] = target;
				if ('function' === typeof callback) {
					callback(target);
				}
				$spinner.trigger('hide');
			};

			if (url.match(/\.(jpe?g|gif|png)(\?[\s\S]*)?$/i)) {
				xhr[url] = true;
				var image = document.createElement('img');
				image.src = url;
				image.onload = function() {
					var style = '';
					if (isOld) {
						style += ' style="';
						var maxWidth = $body.width() - 50, maxHeight = $body.height() - 50;
						if (maxWidth < this.width) {
							style += 'max-width:' + maxWidth + 'px';
						}
						if (maxHeight < this.height) {
							style += ' max-height:' + maxHeight + 'px';
						}
						style += '"';
					}
					success('<img src="' + url + '" class="' + prefix + '-image"' + style + '>');
				};
			} else {
				if (xhr[url]) {
					xhr[url].success(success);
				} else {
					if (postData) {
						xhr[url] = $.post(url, postData, success);
					} else {
						xhr[url] = $.get(url, success);
					}
				}
			}

			
		}
	}

	function runEvent(name, data) {
		if (data.option[name] && 'function' === typeof data.option[name]) {
			data.option[name].apply(this, [data.content, data.option]);
		}
	}

	function isAnimation() {
		if (isAnimating || isCssAnimating) {
			return true;
		}
		isAnimating = true;
	}

	function autoHide() {
		if (!$current) return;
		var option = $current.data('option');
		if (option.autoHide && 'number' === typeof option.autoHide) {
			clearTimeout(autoHideTimer);
			autoHideTimer = setTimeout(function() {
				$stage.trigger('hide');
			}, option.autoHide);
		}
	}

	$.fn[pluginName] = function(option) {
		var self, modal, id, href;
		currentSelector = this.selector;

		return this.each(function() {
			self = $(this);
			option = $.extend({}, defaults, option, self.data());

			if (!self.data('modal')) {
				// 対象がインライン要素の場合
				id = option.target || self.attr('href');
				if ('#' === id[0] || '.' === id[0]) {
					modal = $(id);
					if (!modal.length) {
						return true;
					}

					self.data('modal', attach(modal, option))
						.on('click.' + pluginName, function(event) {
							event.preventDefault();
							$stage.trigger('show', $(this).data('modal'));
						});
				}

				// Ajaxの場合
				else if(self.is('a')) {
					if (option.preload) {
						ajax(this.href, option);
					}

					self.on('click.' + pluginName, function(event) {
						event.preventDefault();
						ajax(this.href, option, function(modal){
							$stage.trigger('show', modal);
						});
					});
				}
			}
		});
	};

	$[pluginName] = function(target, option) {
		if (target) {
			return $stage.trigger('show', attach(target, option));
		}
	};

	$[pluginName].setting = function (option, value) {
		if ('object' === typeof option) {
			defaults = $.extend(defaults, option);
		} else if ('string' === typeof option && undefined !== value) {
			defaults[option] = value;
		}
	};
})(window, jQuery);
