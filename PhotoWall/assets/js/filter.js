/* ============================================================
   PhotoWall 光影墙 — 分类 / 标签筛选
   URL 参数：?category=slug / ?tag=slug
   ============================================================ */
(function () {
  'use strict';

  var PW = (window.PW = window.PW || {});

  function Filter(nav, wall, cfg) {
    this.nav = nav || null;
    this.wall = wall;
    this.cfg = cfg || {};
    this.type = 'all';
    this.slug = '';
    this.initialized = false;
  }

  Filter.prototype.readUrl = function () {
    if (!this.cfg.urlFilter) {
      return null;
    }
    try {
      var params = new URLSearchParams(window.location.search);
      var category = params.get('category');
      var tag = params.get('tag');
      if (category) {
        return { type: 'category', slug: category };
      }
      if (tag) {
        return { type: 'tag', slug: tag };
      }
    } catch (err) {
      /* 忽略 */
    }
    return null;
  };

  Filter.prototype.readDefault = function () {
    var value = (this.cfg.defaultFilter || '').trim();
    if (!value) {
      return null;
    }
    var pair = value.split(':');
    if (pair.length === 2 && (pair[0] === 'category' || pair[0] === 'tag') && pair[1]) {
      return { type: pair[0], slug: pair[1].trim() };
    }
    return null;
  };

  Filter.prototype.init = function () {
    var self = this;
    var initial =
      (this.cfg.presetFilter && this.cfg.presetFilter.slug ? this.cfg.presetFilter : null) ||
      this.readUrl() ||
      this.readDefault() ||
      { type: 'all', slug: '' };

    if (this.nav) {
      var buttons = this.nav.querySelectorAll('.pw-filter-btn');
      Array.prototype.forEach.call(buttons, function (button) {
        button.addEventListener('click', function () {
          var type = button.getAttribute('data-type') || 'all';
          var slug = button.getAttribute('data-slug') || '';
          self.apply(type, slug, true);
        });
      });
    }

    this.apply(initial.type, initial.slug, false);
    this.initialized = true;
  };

  Filter.prototype.apply = function (type, slug, updateUrl) {
    this.type = type || 'all';
    this.slug = slug || '';

    this.wall.applyFilter(this.type, this.slug);
    this.wall.restart();

    this.syncButtons();

    if (updateUrl && this.cfg.urlFilter) {
      this.updateUrl();
    }
  };

  Filter.prototype.syncButtons = function () {
    if (!this.nav) {
      return;
    }
    var self = this;
    var buttons = this.nav.querySelectorAll('.pw-filter-btn');
    Array.prototype.forEach.call(buttons, function (button) {
      var type = button.getAttribute('data-type') || 'all';
      var slug = button.getAttribute('data-slug') || '';
      var active = type === self.type && slug === self.slug;
      if (!active && self.type !== 'all' && type === 'all') {
        active = false;
      }
      button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
  };

  Filter.prototype.updateUrl = function () {
    try {
      var url = new URL(window.location.href);
      url.searchParams.delete('category');
      url.searchParams.delete('tag');
      if (this.type === 'category' && this.slug) {
        url.searchParams.set('category', this.slug);
      } else if (this.type === 'tag' && this.slug) {
        url.searchParams.set('tag', this.slug);
      }
      window.history.replaceState(window.history.state, '', url.toString());
    } catch (err) {
      /* 忽略 */
    }
  };

  PW.Filter = Filter;
})();
