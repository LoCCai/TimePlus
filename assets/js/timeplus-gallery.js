(function ($) {
  'use strict';

  var $root = $('#wrapper');
  var triggerSelector = '.timeplus-gallery-trigger';
  if (!$root.length || !$root.find(triggerSelector).length || typeof $.fn.poptrox !== 'function') return;

  var state = {
    active: false,
    gallery: null,
    index: 0,
    popup: null,
    sourceAnchor: null,
    previousFocus: null,
    transitioning: false,
    transitionTimer: 0,
    preloaded: new Set(),
    scrollY: 0,
    bodyStyle: undefined,
    htmlStyle: undefined,
    touch: null,
    blockedKey: null,
    focusPending: false,
    requestId: 0
  };
  var triggerHrefs = [];
  var galleryByAnchor = new WeakMap();
  var lastPointer = { x: null, y: null };

  $root.find(triggerSelector).each(function () {
    triggerHrefs.push([this, this.getAttribute('href')]);
  });

  function parseGallery(anchor) {
    var container = anchor && anchor.closest('[data-gallery-id]');
    var dataNode = container && container.querySelector('.timeplus-gallery-data');
    if (!container || !dataNode) return null;
    try {
      var data = JSON.parse(dataNode.textContent || '{}');
      if (!data || !Array.isArray(data.images) || data.images.length === 0) return null;
      data.images = data.images.filter(function (url) { return typeof url === 'string' && url !== ''; });
      data.originals = Array.isArray(data.originals) ? data.originals : data.images.slice();
      data.context = data.context === 'post' ? 'post' : 'archive';
      data.container = container;
      return data.images.length ? data : null;
    } catch (error) {
      return null;
    }
  }

  function captionHtml(container) {
    var postTemplate = container.querySelector('.timeplus-post-caption-template');
    if (postTemplate) return postTemplate.innerHTML;

    return Array.prototype.filter.call(container.children, function (child) {
      return child.matches('h2, .content-wrapper, .tag-info, .breadcrumb-nav, .timeplus-original-link');
    }).map(function (child) {
      return child.outerHTML;
    }).join('');
  }

  function prepareCaption($anchor) {
    var anchor = $anchor && $anchor[0];
    var gallery = parseGallery(anchor);
    if (!gallery) return '';
    galleryByAnchor.set(anchor, gallery);
    return captionHtml(gallery.container);
  }

  function selectAnchor(anchor) {
    if (!anchor) return false;
    var gallery = galleryByAnchor.get(anchor) || parseGallery(anchor);
    if (!gallery) return false;
    galleryByAnchor.set(anchor, gallery);
    state.gallery = gallery;
    state.sourceAnchor = anchor;
    state.index = Math.max(0, Math.min(gallery.images.length - 1, parseInt(anchor.dataset.imageIndex, 10) || 0));
    return true;
  }

  function scheduleAdjacentAnchor(direction) {
    var currentIndex = triggerHrefs.findIndex(function (entry) { return entry[0] === state.sourceAnchor; });
    if (currentIndex < 0 || triggerHrefs.length < 2) return;
    var nextIndex = (currentIndex + direction + triggerHrefs.length) % triggerHrefs.length;
    var nextAnchor = triggerHrefs[nextIndex][0];
    window.setTimeout(function () {
      if (!state.active || !selectAnchor(nextAnchor)) return;
      syncPopup();
    }, 0);
  }

  function visiblePopup() {
    var popups = Array.prototype.slice.call(document.querySelectorAll('.poptrox-popup'));
    return popups.find(function (popup) {
      var style = window.getComputedStyle(popup);
      return style.display !== 'none' && style.visibility !== 'hidden';
    }) || popups[popups.length - 1] || null;
  }

  function lockPage() {
    if (state.bodyStyle !== undefined) return;
    state.scrollY = window.scrollY || window.pageYOffset || 0;
    state.bodyStyle = document.body.getAttribute('style');
    state.htmlStyle = document.documentElement.getAttribute('style');
    document.body.style.position = 'fixed';
    document.body.style.top = '-' + state.scrollY + 'px';
    document.body.style.width = '100%';
    document.body.style.overflow = 'hidden';
    document.documentElement.style.overflow = 'hidden';
    document.body.classList.add('modal-active');
  }

  function restoreAttribute(element, name, value) {
    if (value === null) element.removeAttribute(name);
    else element.setAttribute(name, value);
  }

  function unlockPage() {
    if (state.bodyStyle === undefined) return;
    var scrollY = state.scrollY;
    restoreAttribute(document.body, 'style', state.bodyStyle);
    restoreAttribute(document.documentElement, 'style', state.htmlStyle);
    document.body.classList.remove('modal-active');
    state.bodyStyle = undefined;
    state.htmlStyle = undefined;
    window.scrollTo(0, scrollY);
  }

  function popupImage() {
    return state.popup && state.popup.querySelector('.pic img');
  }

  function updateControls() {
    if (!state.popup || !state.gallery) return;
    state.popup.querySelectorAll('.nav-dot').forEach(function (dot) {
      var selected = parseInt(dot.dataset.index, 10) === state.index;
      dot.classList.toggle('active', selected);
      dot.setAttribute('aria-pressed', selected ? 'true' : 'false');
    });
    var originalLink = state.popup.querySelector('.timeplus-original-link');
    if (originalLink) {
      originalLink.href = state.gallery.originals[state.index] || state.gallery.images[state.index];
    }
  }

  function preloadIndex(index) {
    if (!state.gallery || index < 0 || index >= state.gallery.images.length) return;
    var url = state.gallery.images[index];
    if (state.preloaded.has(url)) return;
    state.preloaded.add(url);
    var image = new Image();
    image.src = url;
  }

  function preloadAdjacent() {
    preloadIndex(state.index - 1);
    preloadIndex(state.index + 1);
  }

  function finishTransition(image, requestId) {
    window.clearTimeout(state.transitionTimer);
    state.transitionTimer = window.setTimeout(function () {
      if (requestId !== state.requestId) return;
      if (image) {
        image.classList.remove('timeplus-image-changing');
        image.style.transform = '';
        image.style.transition = '';
      }
      state.transitioning = false;
    }, 180);
  }

  function showImage(nextIndex) {
    if (!state.active || !state.gallery) return false;
    if (nextIndex < 0 || nextIndex >= state.gallery.images.length) return false;
    if (nextIndex === state.index) {
      var current = popupImage();
      if (current) {
        current.style.transform = '';
        current.style.transition = '';
      }
      return true;
    }

    var image = popupImage();
    if (!image) return false;
    var targetUrl = state.gallery.images[nextIndex];
    state.requestId++;
    var requestId = state.requestId;
    window.clearTimeout(state.transitionTimer);
    image.classList.remove('timeplus-image-changing');
    state.transitioning = true;
    var loader = new Image();

    loader.onload = function () {
      if (requestId !== state.requestId || !state.active) return;
      image.classList.add('timeplus-image-changing');
      window.setTimeout(function () {
        if (requestId !== state.requestId || !state.active) return;
        image.src = targetUrl;
        image.alt = '第 ' + (nextIndex + 1) + ' 张图片';
        state.index = nextIndex;
        updateControls();
        preloadAdjacent();
        window.requestAnimationFrame(function () { finishTransition(image, requestId); });
      }, 90);
    };
    loader.onerror = function () {
      if (requestId !== state.requestId || !state.active) return;
      if (state.gallery.fallback) image.src = state.gallery.fallback;
      state.index = nextIndex;
      updateControls();
      finishTransition(image, requestId);
    };
    loader.src = targetUrl;
    return true;
  }

  function syncPopup() {
    if (!state.active || !state.gallery) return;
    state.popup = visiblePopup();
    if (!state.popup) {
      window.setTimeout(syncPopup, 30);
      return;
    }

    state.popup.setAttribute('role', 'dialog');
    state.popup.setAttribute('aria-modal', 'true');
    state.popup.setAttribute('aria-label', '图片查看器');
    state.popup.setAttribute('tabindex', '-1');

    var closer = state.popup.querySelector('.closer');
    if (closer) {
      closer.setAttribute('role', 'button');
      closer.setAttribute('tabindex', '0');
      closer.setAttribute('aria-label', '关闭图片查看器');
    }
    var previous = state.popup.querySelector('.nav-previous');
    var next = state.popup.querySelector('.nav-next');
    if (previous) {
      previous.setAttribute('role', 'button');
      previous.setAttribute('tabindex', '0');
      previous.setAttribute('aria-label', '上一张图片或上一篇文章');
    }
    if (next) {
      next.setAttribute('role', 'button');
      next.setAttribute('tabindex', '0');
      next.setAttribute('aria-label', '下一张图片或下一篇文章');
    }
    var image = popupImage();
    if (image) {
      image.alt = state.sourceAnchor && state.sourceAnchor.querySelector('img')
        ? state.sourceAnchor.querySelector('img').alt
        : '文章图片';
    }
    updateControls();
    preloadAdjacent();
    focusPopupWhenReady();
  }

  function focusPopupWhenReady() {
    if (!state.active || !state.popup || !state.focusPending) return;
    var closer = state.popup.querySelector('.closer');
    if (closer && closer.getClientRects().length > 0) {
      closer.focus({ preventScroll: true });
      state.focusPending = false;
      return;
    }
    window.setTimeout(focusPopupWhenReady, 30);
  }

  function openPopup() {
    state.active = true;
    state.previousFocus = document.activeElement;
    state.preloaded = new Set();
    state.focusPending = true;
    lockPage();
    window.setTimeout(syncPopup, 0);
  }

  function closePopup() {
    window.clearTimeout(state.transitionTimer);
    state.requestId++;
    var image = popupImage();
    if (image) {
      image.style.transform = '';
      image.style.transition = '';
    }
    unlockPage();
    var focusTarget = state.sourceAnchor || state.previousFocus;
    state.active = false;
    state.gallery = null;
    state.popup = null;
    state.sourceAnchor = null;
    state.transitioning = false;
    state.touch = null;
    state.focusPending = false;
    if (focusTarget && typeof focusTarget.focus === 'function') {
      window.setTimeout(function () { focusTarget.focus({ preventScroll: true }); }, 0);
    }
  }

  // Poptrox computes every caption during setup. Select the clicked anchor at
  // interaction time so pages with multiple posts never inherit the last
  // captioned post's gallery state.
  document.addEventListener('click', function (event) {
    var anchor = event.target.closest(triggerSelector);
    if (anchor && $root[0].contains(anchor)) selectAnchor(anchor);
  }, true);

  $root.poptrox({
    baseZIndex: 20000,
    caption: prepareCaption,
    fadeSpeed: 250,
    onPopupOpen: openPopup,
    onPopupClose: closePopup,
    overlayOpacity: 0,
    popupCloserText: '',
    popupHeight: 150,
    popupLoaderText: '',
    popupSpeed: 250,
    popupWidth: 150,
    selector: triggerSelector,
    usePopupCaption: true,
    usePopupCloser: true,
    usePopupDefaultStyling: false,
    usePopupForceClose: true,
    usePopupLoader: true,
    usePopupNav: true,
    windowMargin: 50
  });

  // Poptrox switches adjacent posts without clicking their source anchors.
  // Keep the module state aligned with its internal trigger index.
  $(document).on('poptrox_switch.timeplusGallery', '.poptrox-popup', function (_event, triggerIndex) {
    var entry = triggerHrefs[triggerIndex];
    if (!entry || !selectAnchor(entry[0]) || !state.active) return;
    window.setTimeout(syncPopup, 0);
  });

  // Poptrox removes href during setup. Restore it for keyboard focus,
  // link previews, no-script fallback, and native open-in-new-tab behavior.
  triggerHrefs.forEach(function (entry) {
    if (entry[1]) entry[0].setAttribute('href', entry[1]);
  });

  breakpoints.on('<=xsmall', function () {
    if ($root[0] && $root[0]._poptrox) $root[0]._poptrox.windowMargin = 0;
  });
  breakpoints.on('>xsmall', function () {
    if ($root[0] && $root[0]._poptrox) $root[0]._poptrox.windowMargin = 50;
  });

  document.addEventListener('click', function (event) {
    if (!state.active || !state.popup || !state.popup.contains(event.target)) return;

    var dot = event.target.closest('.nav-dot');
    if (dot) {
      event.preventDefault();
      event.stopImmediatePropagation();
      showImage(parseInt(dot.dataset.index, 10));
      return;
    }

    var next = event.target.closest('.nav-next');
    var previous = event.target.closest('.nav-previous');
    if (!next && !previous) return;
    var targetIndex = state.index + (next ? 1 : -1);
    if (targetIndex >= 0 && targetIndex < state.gallery.images.length) {
      event.preventDefault();
      event.stopImmediatePropagation();
      showImage(targetIndex);
    } else if (state.gallery.context === 'post') {
      event.preventDefault();
      event.stopImmediatePropagation();
    } else {
      scheduleAdjacentAnchor(next ? 1 : -1);
    }
  }, true);

  document.addEventListener('pointermove', function (event) {
    if (!state.active || !state.popup || !state.popup.contains(event.target)) return;
    if (event.pointerType && event.pointerType !== 'mouse') return;
    if (event.clientX === lastPointer.x && event.clientY === lastPointer.y) return;
    lastPointer.x = event.clientX;
    lastPointer.y = event.clientY;
    var dot = event.target.closest('.nav-dot');
    if (!dot || dot.classList.contains('active')) return;
    showImage(parseInt(dot.dataset.index, 10));
  });

  document.addEventListener('wheel', function (event) {
    if (!state.active || !state.popup || !state.popup.contains(event.target) || !state.gallery) return;
    if (state.gallery.images.length < 2 || event.deltaY === 0) return;
    event.preventDefault();
    var targetIndex = state.index + (event.deltaY > 0 ? 1 : -1);
    if (targetIndex >= 0 && targetIndex < state.gallery.images.length) showImage(targetIndex);
  }, { passive: false });

  document.addEventListener('keydown', function (event) {
    if (!state.active || !state.gallery || !state.popup) return;

    var control = event.target instanceof Element
      ? event.target.closest('.closer, .nav-previous, .nav-next, .nav-dot')
      : null;
    if (control && (event.key === 'Enter' || event.key === ' ')) {
      event.preventDefault();
      event.stopImmediatePropagation();
      control.click();
      return;
    }

    if (event.key === 'Tab') {
      var focusable = Array.prototype.slice.call(state.popup.querySelectorAll(
        'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'
      )).filter(function (element) {
        return element.getClientRects().length > 0 && element.getAttribute('aria-hidden') !== 'true';
      });
      if (focusable.length === 0) {
        event.preventDefault();
        state.popup.focus({ preventScroll: true });
        return;
      }
      var first = focusable[0];
      var last = focusable[focusable.length - 1];
      if (!state.popup.contains(document.activeElement)) {
        event.preventDefault();
        first.focus({ preventScroll: true });
      } else if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus({ preventScroll: true });
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus({ preventScroll: true });
      }
      return;
    }

    if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
    var targetIndex = state.index + (event.key === 'ArrowRight' ? 1 : -1);
    state.blockedKey = null;
    if (targetIndex >= 0 && targetIndex < state.gallery.images.length) {
      state.blockedKey = event.key;
      event.preventDefault();
      event.stopImmediatePropagation();
      showImage(targetIndex);
    } else if (state.gallery.context === 'post') {
      state.blockedKey = event.key;
      event.preventDefault();
      event.stopImmediatePropagation();
    } else {
      scheduleAdjacentAnchor(event.key === 'ArrowRight' ? 1 : -1);
    }
  }, true);

  document.addEventListener('keyup', function (event) {
    if (state.blockedKey && event.key === state.blockedKey) {
      event.preventDefault();
      event.stopImmediatePropagation();
      state.blockedKey = null;
    }
  }, true);

  document.addEventListener('pointerdown', function (event) {
    if (event.pointerType === 'mouse') return;
    if (!state.active || !state.popup || !state.popup.contains(event.target) || !state.gallery || state.gallery.images.length < 2) return;
    var image = popupImage();
    if (!image || event.target !== image) return;
    state.touch = {
      pointerId: event.pointerId,
      startX: event.clientX,
      startY: event.clientY,
      currentX: event.clientX,
      horizontal: false,
      image: image
    };
    image.style.transition = 'none';
    if (typeof image.setPointerCapture === 'function') {
      try { image.setPointerCapture(event.pointerId); } catch (error) { /* Synthetic events have no active pointer. */ }
    }
  });

  document.addEventListener('pointermove', function (event) {
    if (!state.touch || event.pointerId !== state.touch.pointerId) return;
    var deltaX = event.clientX - state.touch.startX;
    var deltaY = event.clientY - state.touch.startY;
    if (!state.touch.horizontal && Math.abs(deltaY) > Math.abs(deltaX)) return;
    state.touch.horizontal = true;
    event.preventDefault();
    state.touch.currentX = event.clientX;
    if ((state.index === 0 && deltaX > 0) || (state.index === state.gallery.images.length - 1 && deltaX < 0)) deltaX *= 0.3;
    var image = popupImage();
    if (image) image.style.transform = 'translate3d(' + deltaX + 'px, 0, 0)';
  });

  function finishPointerDrag(event) {
    if (!state.touch || event.pointerId !== state.touch.pointerId) return;
    var deltaX = state.touch.currentX - state.touch.startX;
    var image = popupImage();
    if (image) {
      image.style.transition = 'transform 180ms ease-out';
      image.style.transform = '';
    }
    if (state.touch.horizontal && Math.abs(deltaX) >= Math.min(80, window.innerWidth * 0.18)) {
      var targetIndex = state.index + (deltaX < 0 ? 1 : -1);
      if (targetIndex >= 0 && targetIndex < state.gallery.images.length) showImage(targetIndex);
    }
    state.touch = null;
  }

  document.addEventListener('pointerup', finishPointerDrag);
  document.addEventListener('pointercancel', finishPointerDrag);

  window.addEventListener('resize', function () {
    var image = popupImage();
    if (image) image.style.transform = '';
    state.touch = null;
  });
})(jQuery);
