/* ============================================================
   PhotoWall 光影墙 — 照片墙引擎（单图轮播 / 多图网格 / 过渡动效）
   ============================================================ */
(function () {
  'use strict';

  var PW = (window.PW = window.PW || {});

  var EFFECT_POOL = ['fade', 'zoom', 'slide', 'kenburns'];
  var FADE_POOL = ['fade', 'zoom', 'randomfade'];

  function matchMedia(query) {
    return window.matchMedia ? window.matchMedia(query).matches : false;
  }

  function calmMotion(cfg) {
    var reduce = matchMedia('(prefers-reduced-motion: reduce)');
    var coarse = cfg.calmOnMobile && matchMedia('(any-pointer: coarse)');
    return !!(reduce || coarse);
  }

  function shuffle(list) {
    for (var i = list.length - 1; i > 0; i--) {
      var j = Math.floor(Math.random() * (i + 1));
      var tmp = list[i];
      list[i] = list[j];
      list[j] = tmp;
    }
    return list;
  }

  function pickEffect(cfg) {
    if (cfg.effect === 'random') {
      return EFFECT_POOL[Math.floor(Math.random() * EFFECT_POOL.length)];
    }
    if (cfg.effect === 'randomFade') {
      return FADE_POOL[Math.floor(Math.random() * FADE_POOL.length)];
    }
    return cfg.effect;
  }

  function loadImage(src, callback) {
    var done = false;
    var img = new Image();
    var timer = setTimeout(function () {
      if (!done) {
        done = true;
        callback(false);
      }
    }, 5000);
    img.onload = function () {
      if (!done) {
        done = true;
        clearTimeout(timer);
        callback(true);
      }
    };
    img.onerror = function () {
      if (!done) {
        done = true;
        clearTimeout(timer);
        callback(false);
      }
    };
    img.src = src;
  }

  function Wall(root, cfg) {
    this.root = root;
    this.cfg = cfg;
    this.items = [];
    this.filtered = [];
    this.queue = [];
    this.queueIndex = 0;
    this.timer = null;
    this.hoverPaused = false;
    this.hiddenPaused = false;
    this.manualPaused = false;
    this.mode = cfg.mode || 'single';
    this.calm = calmMotion(cfg);
    this.duration = this.calm ? 220 : cfg.duration;
    this.interval = Math.max(1500, cfg.interval || 5000);
    this.columns = Math.max(1, Math.min(6, cfg.columns || 3));
    this.stageLayers = [];
    this.activeLayer = 0;
    this.currentIndex = 0;
    this.cells = [];
    this.cellItems = [];
    this.gridBucket = '';
    this.onOpen = null;
    this._bindLifecycle();
  }

  Wall.prototype.setData = function (items) {
    this.items = Array.isArray(items) ? items.slice() : [];
  };

  Wall.prototype.applyFilter = function (type, slug) {
    var self = this;
    if (type === 'category' && slug) {
      this.filtered = this.items.filter(function (item) {
        return item.categorySlugs && item.categorySlugs.indexOf(slug) !== -1;
      });
    } else if (type === 'tag' && slug) {
      this.filtered = this.items.filter(function (item) {
        return item.tagSlugs && item.tagSlugs.indexOf(slug) !== -1;
      });
    } else {
      this.filtered = this.items.slice();
    }
    return this.filtered.length;
  };

  Wall.prototype.buildQueue = function () {
    var queue = this.filtered.slice();
    this.queue = this.cfg.randomOrder ? shuffle(queue) : queue;
    this.queueIndex = 0;
  };

  Wall.prototype.nextQueueItem = function () {
    if (this.queue.length === 0) {
      return null;
    }
    if (this.queueIndex >= this.queue.length) {
      if (this.cfg.randomOrder) {
        shuffle(this.queue);
      }
      this.queueIndex = 0;
    }
    var item = this.queue[this.queueIndex];
    this.queueIndex++;
    return item;
  };

  Wall.prototype.restart = function () {
    this.buildQueue();
    this.render();
    this.updateEmpty();
    this.start();
  };

  Wall.prototype.stop = function () {
    if (this.timer) {
      clearInterval(this.timer);
      this.timer = null;
    }
  };

  Wall.prototype.isPaused = function () {
    return this.manualPaused || this.hoverPaused || this.hiddenPaused;
  };

  Wall.prototype.start = function () {
    this.stop();
    if (this.filtered.length === 0) {
      return;
    }
    if (this.mode === 'single' && this.filtered.length < 2) {
      return;
    }
    if (this.mode !== 'single' && this.filtered.length <= this.cells.length && this.mode !== 'mixed') {
      return;
    }
    var self = this;
    this.timer = setInterval(function () {
      if (self.isPaused() || document.hidden) {
        return;
      }
      self.tick();
    }, this.interval);
  };

  Wall.prototype.tick = function () {
    if (this.mode === 'single' || this.mode === 'mixed') {
      this.showNextStage();
    }
    if (this.mode === 'multi' || this.mode === 'mixed') {
      this.replaceCells();
    }
  };

  /* ---------- 渲染 ---------- */

  Wall.prototype.render = function () {
    var self = this;
    this.root.innerHTML = '';
    this.stageLayers = [];
    this.cells = [];
    this.cellItems = [];
    this.activeLayer = 0;

    if (this.filtered.length === 0) {
      return;
    }

    this.gridBucket = this.gridBucketName();

    if (this.mode === 'single' || this.mode === 'mixed') {
      this.root.appendChild(this.buildStage());
    }
    if (this.mode === 'multi' || this.mode === 'mixed') {
      this.root.appendChild(this.buildGrid());
    }

    if (this.mode === 'single' || this.mode === 'mixed') {
      // 首屏双缓冲预热
      var first = this.queue.length > 0 ? this.queue[0] : this.filtered[0];
      this.currentIndex = this.filtered.indexOf(first);
      this.activateStageItem(first);
    }

    window.setTimeout(function () {
      self.handleResize();
    }, 0);
  };

  Wall.prototype.gridBucketName = function () {
    var width = window.innerWidth || 1200;
    if (width <= 560) {
      return 's';
    }
    if (width <= 900) {
      return 'm';
    }
    return 'l';
  };

  Wall.prototype.cellCount = function () {
    var cols = this.gridBucketName() === 'l' ? this.columns : 2;
    var rows = this.gridBucketName() === 's' ? 3 : 2;
    return cols * rows;
  };

  Wall.prototype.buildStage = function () {
    var self = this;
    var stage = document.createElement('div');
    stage.className = 'pw-stage';
    stage.setAttribute('role', 'group');
    stage.setAttribute('aria-label', '照片轮播，点击查看大图');

    for (var i = 0; i < 2; i++) {
      var layer = document.createElement('figure');
      layer.className = 'pw-layer';
      var img = document.createElement('img');
      img.alt = '';
      img.decoding = 'async';
      layer.appendChild(img);
      stage.appendChild(layer);
      this.stageLayers.push({ el: layer, img: img });
    }

    var caption = document.createElement('div');
    caption.className = 'pw-stage-caption';
    var captionTitle = document.createElement('h2');
    captionTitle.className = 'pw-stage-caption-title';
    var captionMeta = document.createElement('p');
    captionMeta.className = 'pw-stage-caption-meta';
    caption.appendChild(captionTitle);
    caption.appendChild(captionMeta);
    stage.appendChild(caption);

    this.stageCaption = { el: caption, title: captionTitle, meta: captionMeta };

    stage.addEventListener('click', function () {
      self.openCurrent();
    });

    this.stage = stage;
    return stage;
  };

  Wall.prototype.buildGrid = function () {
    var self = this;
    var grid = document.createElement('div');
    grid.className = 'pw-grid';
    var total = Math.max(2, this.cellCount());
    var assigned = {};

    for (var i = 0; i < total; i++) {
      var item = this.nextQueueItem();
      if (!item) {
        item = this.filtered[i % this.filtered.length];
      }
      var guard = 0;
      while (assigned[item.url] && this.filtered.length > total && guard < this.filtered.length) {
        item = this.filtered[(i + guard + 1) % this.filtered.length];
        guard++;
      }
      assigned[item.url] = true;
      var cell = this.buildCell(item);
      grid.appendChild(cell);
      this.cells.push(cell);
      this.cellItems.push(item);
    }

    this.grid = grid;
    return grid;
  };

  Wall.prototype.buildCell = function (item) {
    var self = this;
    var cell = document.createElement('button');
    cell.type = 'button';
    cell.className = 'pw-cell';
    if (item.ratio) {
      cell.style.setProperty('--pw-item-ratio', item.ratio);
    }
    cell.setAttribute('aria-label', '查看照片：' + (item.title || ''));

    var img = document.createElement('img');
    img.src = item.thumb || item.url;
    img.alt = item.alt || item.title || '';
    img.decoding = 'async';
    if (this.cfg.lazyload && this.cells.length >= this.columns * 2) {
      img.loading = 'lazy';
    }
    img.draggable = false;
    cell.appendChild(img);

    var caption = document.createElement('span');
    caption.className = 'pw-cell-caption';
    caption.textContent = item.title || '';
    cell.appendChild(caption);

    cell.addEventListener('click', function () {
      var index = self.cells.indexOf(cell);
      if (index === -1) {
        index = 0;
      }
      var list = self.cellItems.slice(0, self.cells.length);
      var clicked = self.cellItems[index];
      var filteredIndex = self.filtered.indexOf(clicked);
      if (self.onOpen) {
        self.onOpen(filteredIndex === -1 ? list : self.filtered, filteredIndex === -1 ? index : filteredIndex);
      }
    });

    return cell;
  };

  /* ---------- 单图切换 ---------- */

  Wall.prototype.activateStageItem = function (item) {
    if (!item || this.stageLayers.length < 2) {
      return;
    }
    var effect = this.calm ? 'fade' : pickEffect(this.cfg);
    if (this.stage) {
      this.stage.className = 'pw-stage pw-fx-' + effect;
      this.stage.style.setProperty('--pw-duration', this.duration + 'ms');
      this.stage.style.setProperty('--pw-easing', this.cfg.easing || 'ease');
      this.stage.style.setProperty('--pw-interval', this.interval + 'ms');
    }

    var incoming = this.stageLayers[this.activeLayer ^ 1];
    var outgoing = this.stageLayers[this.activeLayer];
    incoming.img.src = item.url;
    incoming.img.alt = item.alt || item.title || '';
    incoming.el.classList.add('is-active');
    outgoing.el.classList.remove('is-active');
    this.activeLayer = this.activeLayer ^ 1;

    if (this.stageCaption) {
      this.stageCaption.title.textContent = item.title || '';
      var meta = [];
      if (item.categories && item.categories.length) {
        meta.push(item.categories.join('、'));
      }
      if (item.date) {
        meta.push(item.date);
      }
      this.stageCaption.meta.textContent = meta.join(' · ');
    }
  };

  Wall.prototype.showNextStage = function () {
    var self = this;
    var item = this.nextQueueItem();
    if (!item) {
      return;
    }
    this.currentIndex = this.filtered.indexOf(item);
    // 预加载完成后再切换，避免空白帧
    loadImage(item.url, function () {
      if (!self.stageLayers.length) {
        return;
      }
      self.activateStageItem(item);
      self.preloadStageNext();
    });
  };

  Wall.prototype.preloadStageNext = function () {
    var nextIndex = this.queueIndex % this.queue.length;
    var next = this.queue[nextIndex];
    if (next) {
      var preloader = new Image();
      preloader.src = next.url;
    }
  };

  Wall.prototype.openCurrent = function () {
    if (this.onOpen) {
      var item = this.filtered[this.currentIndex] || this.filtered[0];
      var index = this.filtered.indexOf(item);
      this.onOpen(this.filtered, index === -1 ? 0 : index);
    }
  };

  /* ---------- 多图随机替换 ---------- */

  Wall.prototype.replaceCells = function () {
    if (this.cells.length === 0 || this.filtered.length <= this.cells.length) {
      return;
    }
    var count = Math.min(this.cells.length, 1 + Math.floor(Math.random() * 3));
    var picked = {};
    for (var n = 0; n < count; n++) {
      var cellIndex = Math.floor(Math.random() * this.cells.length);
      if (picked[cellIndex]) {
        continue;
      }
      picked[cellIndex] = true;
      this.replaceCell(cellIndex);
    }
  };

  Wall.prototype.replaceCell = function (cellIndex) {
    var self = this;
    var cell = this.cells[cellIndex];
    if (!cell || cell.classList.contains('is-leaving')) {
      return;
    }
    var item = this.nextQueueItem();
    if (!item) {
      return;
    }
    this.cellItems[cellIndex] = item;
    var img = cell.querySelector('img');
    var caption = cell.querySelector('.pw-cell-caption');

    cell.classList.add('is-leaving');
    window.setTimeout(function () {
      if (img) {
        img.src = item.thumb || item.url;
        img.alt = item.alt || item.title || '';
      }
      if (caption) {
        caption.textContent = item.title || '';
      }
      if (item.ratio) {
        cell.style.setProperty('--pw-item-ratio', item.ratio);
      } else {
        cell.style.removeProperty('--pw-item-ratio');
      }
      cell.setAttribute('aria-label', '查看照片：' + (item.title || ''));
      cell.classList.remove('is-leaving');
      cell.classList.add('is-entering');
      requestAnimationFrame(function () {
        requestAnimationFrame(function () {
          cell.classList.remove('is-entering');
        });
      });
    }, Math.max(120, this.duration * 0.55));
  };

  /* ---------- 状态 ---------- */

  Wall.prototype.updateEmpty = function () {
    var empty = document.getElementById('pw-empty');
    if (!empty) {
      return;
    }
    if (this.filtered.length === 0) {
      empty.hidden = false;
      this.root.style.display = 'none';
    } else {
      empty.hidden = true;
      this.root.style.display = '';
    }
  };

  /* ---------- 生命周期 ---------- */

  Wall.prototype._bindLifecycle = function () {
    var self = this;

    if (this.cfg.hoverPause) {
      this.root.addEventListener('pointerenter', function (event) {
        if (event.pointerType !== 'touch') {
          self.hoverPaused = true;
        }
      });
      this.root.addEventListener('pointerleave', function () {
        self.hoverPaused = false;
      });
    }

    if (this.cfg.pauseOnHidden) {
      document.addEventListener('visibilitychange', function () {
        self.hiddenPaused = document.hidden;
      });
    }

    window.addEventListener('resize', function () {
      window.clearTimeout(self._resizeTimer);
      self._resizeTimer = window.setTimeout(function () {
        self.handleResize();
      }, 200);
    });
  };

  Wall.prototype.handleResize = function () {
    if ((this.mode === 'multi' || this.mode === 'mixed') && this.filtered.length > 0) {
      var bucket = this.gridBucketName();
      if (bucket !== this.gridBucket) {
        this.gridBucket = bucket;
        var grid = this.grid;
        var total = Math.max(2, this.cellCount());
        while (this.cells.length < total) {
          var item = this.nextQueueItem() || this.filtered[this.cells.length % this.filtered.length];
          var cell = this.buildCell(item);
          if (grid) {
            grid.appendChild(cell);
          }
          this.cells.push(cell);
          this.cellItems.push(item);
        }
        while (this.cells.length > total) {
          var removed = this.cells.pop();
          this.cellItems.pop();
          if (removed && removed.parentNode) {
            removed.parentNode.removeChild(removed);
          }
        }
      }
    }
  };

  PW.Wall = Wall;
  PW.calmsMotion = calmMotion;
})();
