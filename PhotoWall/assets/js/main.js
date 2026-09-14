/* ============================================================
   PhotoWall 光影墙 — 启动装配
   ============================================================ */
(function () {
  'use strict';

  var PW = (window.PW = window.PW || {});

  function readJson(id) {
    try {
      var el = document.getElementById(id);
      if (!el) {
        return null;
      }
      return JSON.parse(el.textContent);
    } catch (err) {
      return null;
    }
  }

  function initThemeToggle() {
    var order = ['auto', 'light', 'dark'];
    var button = document.querySelector('.pw-theme-toggle');
    if (!button) {
      return;
    }
    button.addEventListener('click', function () {
      var html = document.documentElement;
      var current = html.getAttribute('data-pw-theme') || 'auto';
      var next = order[(order.indexOf(current) + 1) % order.length];
      html.setAttribute('data-pw-theme', next);
      try {
        if (next === 'auto') {
          localStorage.removeItem('pw-theme');
        } else {
          localStorage.setItem('pw-theme', next);
        }
      } catch (err) {
        /* 忽略 */
      }
      button.setAttribute('aria-label', '当前配色：' + next + '，点击切换');
    });
  }

  function fetchApiItems(endpoint, onDone) {
    var url = endpoint + '&limit=100&orderby=date&page=1';
    fetch(url, { credentials: 'same-origin' })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('http ' + response.status);
        }
        return response.json();
      })
      .then(function (json) {
        if (json && json.code === 200 && json.data && Array.isArray(json.data.items)) {
          var items = json.data.items.slice();
          var total = json.data.total || items.length;
          // 分页补齐（最多 5 页，避免极端站点拖慢首屏）
          var pages = Math.min(5, Math.ceil(total / 100) - 1);
          var pending = pages;
          if (pending <= 0) {
            onDone(items);
            return;
          }
          for (var page = 2; page <= pages + 1; page++) {
            (function (pageNo) {
              fetch(endpoint + '&limit=100&orderby=date&page=' + pageNo, { credentials: 'same-origin' })
                .then(function (response) {
                  return response.ok ? response.json() : null;
                })
                .then(function (part) {
                  if (part && part.code === 200 && part.data && Array.isArray(part.data.items)) {
                    items = items.concat(part.data.items);
                  }
                })
                .catch(function () {})
                .then(function () {
                  pending--;
                  if (pending === 0) {
                    onDone(items);
                  }
                });
            })(page);
          }
        } else {
          onDone(null);
        }
      })
      .catch(function () {
        onDone(null);
      });
  }

  // API 条目转换为内嵌数据结构
  function normalizeApiItems(items, embedded) {
    var byUrl = {};
    if (Array.isArray(embedded)) {
      embedded.forEach(function (item) {
        byUrl[item.url] = item;
      });
    }
    return items.map(function (apiItem) {
      var rich = byUrl[apiItem.url];
      if (rich) {
        return rich;
      }
      return {
        id: apiItem.id,
        title: apiItem.title,
        url: apiItem.url,
        thumb: apiItem.thumb,
        alt: apiItem.alt,
        description: apiItem.description,
        permalink: apiItem.permalink,
        categories: apiItem.category || [],
        categorySlugs: [],
        tags: apiItem.tags || [],
        tagSlugs: [],
        date: apiItem.date,
        ratio: '',
        placeholder: false
      };
    });
  }

  function initWall(cfg) {
    var wallEl = document.getElementById('pw-wall');
    if (!wallEl || !PW.Wall) {
      return;
    }

    var items = readJson('pw-data');
    if (!Array.isArray(items)) {
      items = [];
    }

    var lightbox = null;
    if (PW.Lightbox) {
      lightbox = new PW.Lightbox(cfg.lightbox || {});
    }

    var wall = new PW.Wall(wallEl, cfg);
    wall.setData(items);

    if (lightbox) {
      wall.onOpen = function (list, index) {
        lightbox.open(list, index);
      };
    }

    function boot() {
      if (PW.Filter) {
        var filterNav = document.getElementById('pw-filters');
        var filter = new PW.Filter(filterNav, wall, cfg);
        filter.init();
      } else {
        wall.applyFilter('all', '');
        wall.restart();
      }
    }

    var threshold = cfg.api && cfg.api.threshold ? cfg.api.threshold : 0;
    if (cfg.api && cfg.api.enabled && threshold > 0 && items.length >= threshold && typeof fetch === 'function') {
      fetchApiItems(cfg.api.endpoint, function (apiItems) {
        if (Array.isArray(apiItems) && apiItems.length > 0) {
          wall.setData(normalizeApiItems(apiItems, items));
        }
        boot();
      });
    } else {
      boot();
    }
  }

  function initPostLightbox(cfg) {
    var content = document.querySelector('.pw-post-content');
    if (!content || !PW.Lightbox || !cfg.lightbox || !cfg.lightbox.enabled) {
      return;
    }
    var lightbox = new PW.Lightbox(cfg.lightbox);
    var title = '';
    var titleEl = document.querySelector('.pw-post-title');
    if (titleEl) {
      title = titleEl.textContent || '';
    }

    var images = Array.prototype.slice.call(content.querySelectorAll('img'));
    var items = images.map(function (img) {
      return {
        id: 0,
        title: title,
        url: img.currentSrc || img.src,
        thumb: img.currentSrc || img.src,
        alt: img.alt || title,
        description: img.alt || title,
        permalink: window.location.href,
        categories: [],
        categorySlugs: [],
        tags: [],
        tagSlugs: [],
        date: '',
        ratio: '',
        placeholder: false
      };
    });

    images.forEach(function (img, index) {
      img.classList.add('pw-lb-ready');
      img.addEventListener('click', function (event) {
        if (img.closest('a')) {
          return; // 图片本身带链接时遵循链接
        }
        event.preventDefault();
        lightbox.open(items, index);
      });
    });
  }

  function boot() {
    var cfg = readJson('pw-config') || {};
    initThemeToggle();
    initWall(cfg);
    initPostLightbox(cfg);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
