/* Core TimePlus page interactions. Gallery behavior lives in timeplus-gallery.js. */
(function ($) {
  'use strict';

  var $window = $(window);
  var $body = $('body');

  breakpoints({
    xlarge: ['1281px', '1680px'],
    large: ['981px', '1280px'],
    medium: ['737px', '980px'],
    small: ['481px', '736px'],
    xsmall: [null, '480px']
  });

  if (browser.mobile) {
    $body.addClass('touch');
  }

  $window.on('load', function () {
    window.setTimeout(function () {
      $body.removeClass('is-preload');
    }, 100);
  });

  var resizeTimer;
  $window.on('resize', function () {
    window.clearTimeout(resizeTimer);
    $body.addClass('is-resizing');
    resizeTimer = window.setTimeout(function () {
      $body.removeClass('is-resizing');
    }, 150);
  });

  var $panels = $('.panel');
  $panels.each(function () {
    var $panel = $(this);
    var panelId = $panel.attr('id');
    var $toggles = $('[data-panel-target="' + panelId + '"]');
    var $closer = $('<button type="button" class="closer" aria-label="关闭面板"></button>').appendTo($panel);

    function hidePanel() {
      $panel.removeClass('active').attr('aria-hidden', 'true');
      $toggles.removeClass('active').attr('aria-expanded', 'false');
      $body.removeClass('content-active');
    }

    function showPanel() {
      $panels.not($panel).trigger('timeplus:hide');
      $panel.addClass('active').attr('aria-hidden', 'false');
      $toggles.addClass('active').attr('aria-expanded', 'true');
      $body.addClass('content-active');
      $closer.trigger('focus');
    }

    $panel
      .on('click', function (event) { event.stopPropagation(); })
      .on('timeplus:hide', hidePanel)
      .on('timeplus:toggle', function () {
        if ($panel.hasClass('active')) hidePanel();
        else showPanel();
      });

    $closer.on('click', hidePanel);
    $toggles.on('click', function (event) {
      event.preventDefault();
      event.stopPropagation();
      $panel.trigger('timeplus:toggle');
    });
  });

  $body.on('click', function () {
    if ($body.hasClass('content-active')) {
      $panels.trigger('timeplus:hide');
    }
  });

  $window.on('keydown', function (event) {
    if (event.key === 'Escape' && $body.hasClass('content-active')) {
      event.preventDefault();
      $panels.trigger('timeplus:hide');
    }
  });

  var $copyright = $('#footer .copyright');
  if ($copyright.length) {
    var $copyrightParent = $copyright.parent();
    var $mobileParent = $copyrightParent.parent().children().last();
    breakpoints.on('<=medium', function () { $copyright.appendTo($mobileParent); });
    breakpoints.on('>medium', function () { $copyright.appendTo($copyrightParent); });
  }

  document.addEventListener('error', function (event) {
    var image = event.target;
    if (!(image instanceof HTMLImageElement)) return;
    var fallback = image.dataset.fallbackSrc;
    if (!fallback || image.dataset.fallbackApplied === 'true') return;
    image.dataset.fallbackApplied = 'true';
    image.src = fallback;
  }, true);

  function initFullscreen() {
    var button = document.getElementById('fullscreen');
    if (!button) return;
    var label = button.querySelector('[data-fullscreen-label]');
    var request = document.documentElement.requestFullscreen;
    var exit = document.exitFullscreen;

    function update() {
      var active = Boolean(document.fullscreenElement);
      button.setAttribute('aria-pressed', active ? 'true' : 'false');
      if (label) label.textContent = active ? '退出全屏' : '全屏';
    }

    if (typeof request !== 'function' || typeof exit !== 'function') {
      button.disabled = true;
      button.title = '当前浏览器不支持全屏';
      return;
    }

    button.addEventListener('click', function () {
      try {
        var action = document.fullscreenElement
          ? document.exitFullscreen()
          : document.documentElement.requestFullscreen();
        if (action && typeof action.catch === 'function') {
          action.catch(update);
        }
      } catch (error) {
        update();
      }
    });
    document.addEventListener('fullscreenchange', update);
    update();
  }

  initFullscreen();
})(jQuery);
