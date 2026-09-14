/* ============================================================
   PhotoWall 光影墙 — 轻量 Lightbox（零依赖）
   键盘 / 手势 / 缩放 / 信息栏 / 焦点管理
   ============================================================ */
(function () {
  'use strict';

  var PW = (window.PW = window.PW || {});

  function icon(name) {
    var paths = {
      close: '<path d="M6 6l12 12M18 6L6 18"/>',
      prev: '<path d="M15 5l-7 7 7 7"/>',
      next: '<path d="M9 5l7 7-7 7"/>',
      zoomIn: '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3M11 8v6M8 11h6"/>',
      zoomOut: '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3M8 11h6"/>',
      download: '<path d="M12 3v12m0 0l-4-4m4 4l4-4M4 21h16"/>',
      original: '<path d="M14 4h6v6M20 4l-9 9M18 13v6H5V6h6"/>',
      share: '<circle cx="18" cy="5" r="2.5"/><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="19" r="2.5"/><path d="M8.2 10.8l7.6-4.3M8.2 13.2l7.6 4.3"/>',
      goto: '<path d="M6 3h9l4 4v14H6z"/><path d="M9 12h7M9 16h7M9 8h3"/>'
    };
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
      + (paths[name] || '') + '</svg>';
  }

  var MIN_SCALE = 1;
  var MAX_SCALE = 5;

  function Lightbox(opts) {
    this.opts = opts || {};
    this.items = [];
    this.index = 0;
    this.scale = 1;
    this.tx = 0;
    this.ty = 0;
    this.opened = false;
    this.pushed = false;
    this.usedReplace = false;
    this.lastFocus = null;
    this.pointers = new Map();
    this.pinchDist = 0;
    this.pinchScale = 1;
    this.pinchMid = null;
    this.dragStart = null;
    this.swipe = null;

    this.buildDom();
    this.bindEvents();
  }

  Lightbox.prototype.buildDom = function () {
    var root = document.createElement('div');
    root.className = 'pw-lightbox';
    root.id = 'pw-lightbox';
    root.hidden = true;

    var backdrop = document.createElement('div');
    backdrop.className = 'pw-lb-backdrop';
    root.appendChild(backdrop);

    var stage = document.createElement('div');
    stage.className = 'pw-lb-stage';
    var figure = document.createElement('figure');
    figure.className = 'pw-lb-figure';
    var img = document.createElement('img');
    img.className = 'pw-lb-image';
    img.alt = '';
    figure.appendChild(img);
    stage.appendChild(figure);
    var spinner = document.createElement('div');
    spinner.className = 'pw-lb-spinner';
    spinner.hidden = true;
    stage.appendChild(spinner);
    root.appendChild(stage);

    var toolbar = document.createElement('div');
    toolbar.className = 'pw-lb-toolbar';
    root.appendChild(toolbar);

    var prev = document.createElement('button');
    prev.type = 'button';
    prev.className = 'pw-lb-btn pw-lb-prev';
    prev.setAttribute('aria-label', '上一张（←）');
    prev.innerHTML = icon('prev');
    root.appendChild(prev);

    var next = document.createElement('button');
    next.type = 'button';
    next.className = 'pw-lb-btn pw-lb-next';
    next.setAttribute('aria-label', '下一张（→）');
    next.innerHTML = icon('next');
    root.appendChild(next);

    var close = document.createElement('button');
    close.type = 'button';
    close.className = 'pw-lb-btn pw-lb-close';
    close.setAttribute('aria-label', '关闭（Esc）');
    close.innerHTML = icon('close');
    root.appendChild(close);

    var caption = document.createElement('figcaption');
    caption.className = 'pw-lb-caption';
    var counter = document.createElement('div');
    counter.className = 'pw-lb-counter';
    var title = document.createElement('h2');
    title.className = 'pw-lb-title';
    var desc = document.createElement('p');
    desc.className = 'pw-lb-desc';
    var meta = document.createElement('p');
    meta.className = 'pw-lb-meta';
    caption.appendChild(counter);
    caption.appendChild(title);
    caption.appendChild(desc);
    caption.appendChild(meta);
    root.appendChild(caption);

    document.body.appendChild(root);

    this.el = root;
    this.backdrop = backdrop;
    this.stage = stage;
    this.figure = figure;
    this.img = img;
    this.spinner = spinner;
    this.toolbar = toolbar;
    this.prevBtn = prev;
    this.nextBtn = next;
    this.closeBtn = close;
    this.counterEl = counter;
    this.titleEl = title;
    this.descEl = desc;
    this.metaEl = meta;
  };

  Lightbox.prototype.buildToolbar = function () {
    var self = this;
    var opts = this.opts;
    this.toolbar.innerHTML = '';

    function addButton(label, iconName, handler) {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'pw-lb-btn';
      btn.setAttribute('aria-label', label);
      btn.title = label;
      btn.innerHTML = icon(iconName);
      btn.addEventListener('click', handler);
      self.toolbar.appendChild(btn);
      return btn;
    }

    function addAnchor(label, iconName) {
      var link = document.createElement('a');
      link.className = 'pw-lb-btn';
      link.setAttribute('aria-label', label);
      link.title = label;
      link.target = '_blank';
      link.rel = 'noopener';
      link.innerHTML = icon(iconName);
      self.toolbar.appendChild(link);
      return link;
    }

    if (opts.download) {
      this.downloadLink = addAnchor('下载图片', 'download');
    }
    if (opts.original) {
      this.originalLink = addAnchor('查看原图', 'original');
    }
    if (opts.share) {
      this.shareBtn = addButton('分享', 'share', function () {
        self.share();
      });
    }
    if (opts.gotoPost) {
      this.gotoLink = addAnchor('查看文章', 'goto');
    }
    addButton('放大（+）', 'zoomIn', function () {
      self.zoomBy(1.35);
    });
    addButton('缩小（-）', 'zoomOut', function () {
      self.zoomBy(1 / 1.35);
    });
  };

  /* ---------- 打开 / 关闭 ---------- */

  Lightbox.prototype.open = function (items, index) {
    if (!this.opts.enabled || !items || items.length === 0) {
      return false;
    }
    this.lastFocus = document.activeElement;
    this.items = items;
    this.opened = true;
    this.el.hidden = false;
    document.body.style.overflow = 'hidden';
    this.buildToolbar();
    this.show(typeof index === 'number' ? index : 0);

    var hasMarker = false;
    try {
      hasMarker = !!(history.state && history.state.pwLightbox);
    } catch (err) {
      hasMarker = false;
    }

    if (location.hash !== '#pw-view') {
      try {
        history.pushState({ pwLightbox: true }, '', '#pw-view');
        this.pushed = true;
      } catch (err) {
        /* 忽略历史 API 异常 */
      }
    } else if (!hasMarker) {
      // 带着旧 #pw-view 刷新进来的情况：原地标记，关闭时直接清掉 hash
      try {
        history.replaceState({ pwLightbox: true }, '', '#pw-view');
        this.usedReplace = true;
      } catch (err) {
        /* 忽略 */
      }
    }

    this.closeBtn.focus();
    return true;
  };

  Lightbox.prototype.close = function (fromPop) {
    if (!this.opened) {
      return;
    }
    this.opened = false;
    this.el.hidden = true;
    document.body.style.overflow = '';
    this.img.removeAttribute('src');
    this.resetTransform(true);

    if (!fromPop && this.pushed) {
      this.pushed = false;
      try {
        history.back();
      } catch (err) {
        /* 忽略 */
      }
    } else if (!fromPop && this.usedReplace) {
      this.usedReplace = false;
      try {
        history.replaceState(null, '', location.pathname + location.search);
      } catch (err) {
        /* 忽略 */
      }
    } else {
      this.pushed = false;
      this.usedReplace = false;
    }

    if (this.lastFocus && typeof this.lastFocus.focus === 'function') {
      this.lastFocus.focus();
    }
  };

  /* ---------- 显示 ---------- */

  Lightbox.prototype.show = function (index) {
    var len = this.items.length;
    if (len === 0) {
      return;
    }
    this.index = ((index % len) + len) % len;
    var item = this.items[this.index];

    this.resetTransform(true);
    this.spinner.hidden = false;
    var self = this;
    var loaded = false;
    this.img.onload = function () {
      loaded = true;
      self.spinner.hidden = true;
    };
    this.img.onerror = function () {
      loaded = true;
      self.spinner.hidden = true;
    };
    window.setTimeout(function () {
      self.spinner.hidden = true;
    }, 6000);
    this.img.src = item.url;
    this.img.alt = item.alt || item.title || '';

    var opts = this.opts;
    this.titleEl.textContent = opts.title ? (item.title || '') : '';
    this.titleEl.style.display = opts.title ? '' : 'none';
    this.descEl.textContent = opts.description ? (item.description || '') : '';
    this.descEl.style.display = opts.description && item.description ? '' : 'none';

    var metaParts = [];
    if (opts.category && item.categories && item.categories.length) {
      metaParts.push(item.categories.join('、'));
    }
    if (opts.tags && item.tags && item.tags.length) {
      metaParts.push(item.tags.join('、'));
    }
    if (opts.date && item.date) {
      metaParts.push(item.date);
    }
    this.metaEl.innerHTML = '';
    for (var i = 0; i < metaParts.length; i++) {
      if (i > 0) {
          var sep = document.createElement('span');
          sep.textContent = '·';
          this.metaEl.appendChild(sep);
      }
      var part = document.createElement('span');
      part.textContent = metaParts[i];
      this.metaEl.appendChild(part);
    }
    this.metaEl.style.display = metaParts.length ? '' : 'none';

    this.counterEl.textContent = (this.index + 1) + ' / ' + len;
    this.prevBtn.disabled = len < 2;
    this.nextBtn.disabled = len < 2;

    if (this.downloadLink) {
      this.downloadLink.href = item.url;
      this.downloadLink.setAttribute('download', '');
    }
    if (this.originalLink) {
      this.originalLink.href = item.url;
    }
    if (this.gotoLink) {
      this.gotoLink.href = item.permalink || '#';
    }

    this.preload(this.index + 1);
    this.preload(this.index - 1);
  };

  Lightbox.prototype.preload = function (index) {
    var len = this.items.length;
    if (len < 2) {
      return;
    }
    var item = this.items[((index % len) + len) % len];
    if (item) {
      var preloader = new Image();
      preloader.src = item.url;
    }
  };

  Lightbox.prototype.nav = function (delta) {
    if (this.items.length < 2) {
      return;
    }
    this.show(this.index + delta);
  };

  /* ---------- 缩放 ---------- */

  Lightbox.prototype.applyTransform = function () {
    this.img.style.transform = 'translate(' + this.tx + 'px, ' + this.ty + 'px) scale(' + this.scale + ')';
    this.img.classList.toggle('is-zoomed', this.scale > 1);
  };

  Lightbox.prototype.resetTransform = function (instant) {
    this.scale = 1;
    this.tx = 0;
    this.ty = 0;
    if (instant) {
      this.figure.classList.add('is-dragging');
      this.figure.style.transform = '';
    }
    this.applyTransform();
    if (instant) {
      var self = this;
      requestAnimationFrame(function () {
        self.figure.classList.remove('is-dragging');
      });
    }
  };

  Lightbox.prototype.zoomBy = function (factor) {
    var next = Math.min(MAX_SCALE, Math.max(MIN_SCALE, this.scale * factor));
    if (next === this.scale) {
      return;
    }
    if (next === 1) {
      this.tx = 0;
      this.ty = 0;
    }
    this.scale = next;
    this.applyTransform();
  };

  Lightbox.prototype.toggleZoom = function () {
    if (this.scale > 1) {
      this.resetTransform();
    } else {
      this.scale = 2.5;
      this.applyTransform();
    }
  };

  Lightbox.prototype.share = function () {
    var item = this.items[this.index];
    if (!item || !this.shareBtn) {
      return;
    }
    if (navigator.share) {
      navigator.share({ title: item.title || '', url: item.permalink || item.url }).catch(function () {});
      return;
    }
    var link = item.permalink || item.url || '';
    var btn = this.shareBtn;
    var done = function () {
      btn.classList.add('pw-lb-copied');
      window.setTimeout(function () {
        btn.classList.remove('pw-lb-copied');
      }, 1600);
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(link).then(done).catch(function () {});
    }
  };

  /* ---------- 事件 ---------- */

  Lightbox.prototype.bindEvents = function () {
    var self = this;

    this.closeBtn.addEventListener('click', function () {
      self.close();
    });
    this.backdrop.addEventListener('click', function () {
      self.close();
    });
    this.prevBtn.addEventListener('click', function () {
      self.nav(-1);
    });
    this.nextBtn.addEventListener('click', function () {
      self.nav(1);
    });

    this.img.addEventListener('dblclick', function (event) {
      event.preventDefault();
      self.toggleZoom();
    });

    this.stage.addEventListener('wheel', function (event) {
      if (!self.opened) {
        return;
      }
      event.preventDefault();
      self.zoomBy(event.deltaY < 0 ? 1.12 : 1 / 1.12);
    }, { passive: false });

    document.addEventListener('keydown', function (event) {
      if (!self.opened) {
        return;
      }
      if (event.key === 'Escape') {
        event.preventDefault();
        self.close();
      } else if (event.key === 'ArrowLeft' && self.opts.keyboard) {
        event.preventDefault();
        self.nav(-1);
      } else if (event.key === 'ArrowRight' && self.opts.keyboard) {
        event.preventDefault();
        self.nav(1);
      } else if ((event.key === '+' || event.key === '=') && self.opts.keyboard) {
        event.preventDefault();
        self.zoomBy(1.35);
      } else if ((event.key === '-' || event.key === '_') && self.opts.keyboard) {
        event.preventDefault();
        self.zoomBy(1 / 1.35);
      } else if (event.key === '0' && self.opts.keyboard) {
        event.preventDefault();
        self.resetTransform();
      } else if (event.key === 'Tab') {
        self.trapFocus(event);
      }
    });

    window.addEventListener('popstate', function () {
      if (self.opened) {
        self.close(true);
      }
    });

    if (this.opts.gesture) {
      this.bindGestures();
    }
  };

  Lightbox.prototype.trapFocus = function (event) {
    var focusables = this.el.querySelectorAll('button:not([disabled]), a[href]');
    if (focusables.length === 0) {
      return;
    }
    var first = focusables[0];
    var last = focusables[focusables.length - 1];
    var active = document.activeElement;
    if (event.shiftKey && active === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && active === last) {
      event.preventDefault();
      first.focus();
    }
  };

  Lightbox.prototype.bindGestures = function () {
    var self = this;

    function dist(a, b) {
      var dx = a.x - b.x;
      var dy = a.y - b.y;
      return Math.sqrt(dx * dx + dy * dy);
    }

    function midpoint(a, b) {
      return { x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 };
    }

    this.stage.addEventListener('pointerdown', function (event) {
      if (!self.opened) {
        return;
      }
      self.pointers.set(event.pointerId, { x: event.clientX, y: event.clientY });
      if (self.pointers.size === 1) {
        self.dragStart = {
          x: event.clientX,
          y: event.clientY,
          tx: self.tx,
          ty: self.ty,
          scale: self.scale
        };
        self.swipe = null;
      } else if (self.pointers.size === 2) {
        var ids = Array.from(self.pointers.keys());
        var p1 = self.pointers.get(ids[0]);
        var p2 = self.pointers.get(ids[1]);
        self.pinchDist = dist(p1, p2);
        self.pinchScale = self.scale;
        self.pinchMid = midpoint(p1, p2);
        self.dragStart = null;
        self.swipe = null;
      }
      try {
        self.stage.setPointerCapture(event.pointerId);
      } catch (err) {
        /* 忽略 */
      }
    });

    this.stage.addEventListener('pointermove', function (event) {
      if (!self.opened || !self.pointers.has(event.pointerId)) {
        return;
      }
      self.pointers.set(event.pointerId, { x: event.clientX, y: event.clientY });

      if (self.pointers.size >= 2 && self.pinchDist > 0) {
        var ids = Array.from(self.pointers.keys()).slice(0, 2);
        var p1 = self.pointers.get(ids[0]);
        var p2 = self.pointers.get(ids[1]);
        var d = dist(p1, p2);
        var mid = midpoint(p1, p2);
        self.scale = Math.min(MAX_SCALE, Math.max(MIN_SCALE, self.pinchScale * (d / self.pinchDist)));
        if (self.pinchMid) {
          self.tx += mid.x - self.pinchMid.x;
          self.ty += mid.y - self.pinchMid.y;
        }
        self.pinchMid = mid;
        self.figure.classList.add('is-dragging');
        self.applyTransform();
        return;
      }

      if (!self.dragStart) {
        return;
      }

      var dx = event.clientX - self.dragStart.x;
      var dy = event.clientY - self.dragStart.y;

      if (self.dragStart.scale > 1) {
        self.tx = self.dragStart.tx + dx;
        self.ty = self.dragStart.ty + dy;
        self.figure.classList.add('is-dragging');
        self.applyTransform();
        self.img.classList.add('is-panning');
        return;
      }

      if (Math.abs(dx) > 8 || Math.abs(dy) > 8) {
        self.figure.classList.add('is-dragging');
        if (!self.swipe) {
          self.swipe = Math.abs(dx) > Math.abs(dy) ? 'x' : 'y';
        }
        if (self.swipe === 'x') {
          self.figure.style.transform = 'translateX(' + dx * 0.85 + 'px)';
        } else if (dy > 0) {
          self.figure.style.transform = 'translateY(' + dy * 0.6 + 'px)';
          self.figure.style.opacity = String(Math.max(0.35, 1 - dy / 420));
        }
      }
    });

    function endPointer(event) {
      if (!self.pointers.has(event.pointerId)) {
        return;
      }
      self.pointers.delete(event.pointerId);
      if (self.pointers.size < 2) {
        self.pinchDist = 0;
        self.pinchMid = null;
      }
      if (self.pointers.size > 0) {
        return;
      }

      self.figure.classList.remove('is-dragging');
      self.img.classList.remove('is-panning');

      if (self.dragStart) {
        var dx = event.clientX - self.dragStart.x;
        var dy = event.clientY - self.dragStart.y;
        var moved = Math.abs(dx) > 10 || Math.abs(dy) > 10;
        if (moved && self.dragStart.scale <= 1) {
          if (Math.abs(dx) > 64 && Math.abs(dx) > Math.abs(dy)) {
            self.figure.style.transform = '';
            self.figure.style.opacity = '';
            self.nav(dx < 0 ? 1 : -1);
            self.dragStart = null;
            return;
          }
          if (dy > 96 && Math.abs(dy) > Math.abs(dx)) {
            self.figure.style.transform = '';
            self.figure.style.opacity = '';
            self.close();
            self.dragStart = null;
            return;
          }
        }
      }

      self.figure.style.transform = '';
      self.figure.style.opacity = '';
      self.dragStart = null;
      self.swipe = null;
    }

    this.stage.addEventListener('pointerup', endPointer);
    this.stage.addEventListener('pointercancel', endPointer);
  };

  PW.Lightbox = Lightbox;
})();
