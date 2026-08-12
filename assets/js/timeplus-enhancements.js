(function () {
  'use strict';

  function readCategoryTree() {
    var node = document.getElementById('timeplus-category-data');
    if (!node) return [];

    try {
      var data = JSON.parse(node.textContent || '[]');
      return Array.isArray(data) ? data : [];
    } catch (error) {
      return [];
    }
  }

  function safeLink(value) {
    if (typeof value !== 'string' || value.trim() === '') return null;

    try {
      var url = new URL(value, window.location.href);
      return url.protocol === 'http:' || url.protocol === 'https:' ? url.href : null;
    } catch (error) {
      return null;
    }
  }

  function createMessage(text) {
    var message = document.createElement('p');
    message.className = 'category-empty';
    message.textContent = text;
    return message;
  }

  function initCategoryPanel() {
    var overlay = document.getElementById('category-panel');
    var dialog = overlay && overlay.querySelector('.category-dialog');
    var openButton = document.getElementById('open-category-panel');
    var closeButton = document.getElementById('close-category-panel');
    var parentList = document.getElementById('category-parent-list');
    var childrenArea = document.getElementById('category-children');

    if (!overlay || !dialog || !openButton || !closeButton || !parentList || !childrenArea) return;

    var tree = readCategoryTree();
    var activeId = null;
    var previousFocus = null;
    var rendered = false;

    function getFocusable() {
      return Array.prototype.slice.call(
        dialog.querySelectorAll('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])')
      ).filter(function (element) {
        return !element.hidden && element.getAttribute('aria-hidden') !== 'true';
      });
    }

    function createChildList(children) {
      var list = document.createElement('ul');
      list.className = 'category-child-list';

      children.forEach(function (category) {
        if (!category || typeof category !== 'object') return;

        var item = document.createElement('li');
        var href = safeLink(category.permalink);
        var label = typeof category.name === 'string' && category.name.trim() !== ''
          ? category.name
          : '未命名分类';

        if (href) {
          var link = document.createElement('a');
          link.className = 'category-child-link';
          link.href = href;
          link.textContent = label;
          item.appendChild(link);
        } else {
          var text = document.createElement('span');
          text.className = 'category-child-link';
          text.textContent = label;
          item.appendChild(text);
        }

        if (Array.isArray(category.children) && category.children.length > 0) {
          item.appendChild(createChildList(category.children));
        }

        list.appendChild(item);
      });

      return list;
    }

    function selectCategory(category) {
      if (!category || typeof category !== 'object') return;
      activeId = String(category.id);
      var selectedTabId = '';

      parentList.querySelectorAll('.category-parent-row').forEach(function (row) {
        var selected = row.dataset.categoryId === activeId;
        row.classList.toggle('is-active', selected);
        var button = row.querySelector('.category-parent-button');
        if (button) {
          button.setAttribute('aria-pressed', selected ? 'true' : 'false');
          if (selected) selectedTabId = button.id;
        }
      });
      if (selectedTabId) childrenArea.setAttribute('aria-labelledby', selectedTabId);

      childrenArea.replaceChildren();
      if (!Array.isArray(category.children) || category.children.length === 0) {
        childrenArea.appendChild(createMessage('该分类暂无子分类，可使用左侧箭头直接进入。'));
        return;
      }
      childrenArea.appendChild(createChildList(category.children));
    }

    function render() {
      if (rendered) return;
      rendered = true;
      parentList.replaceChildren();
      childrenArea.replaceChildren();

      if (tree.length === 0) {
        parentList.removeAttribute('role');
        parentList.appendChild(createMessage('暂无分类'));
        childrenArea.appendChild(createMessage('创建分类后会在这里显示。'));
        return;
      }

      tree.forEach(function (category) {
        if (!category || typeof category !== 'object') return;

        var row = document.createElement('div');
        row.className = 'category-parent-row';
        row.dataset.categoryId = String(category.id);

        var button = document.createElement('button');
        button.className = 'category-parent-button';
        button.id = 'timeplus-category-tab-' + String(category.id).replace(/[^a-zA-Z0-9_-]/g, '-');
        button.type = 'button';
        button.setAttribute('aria-controls', 'category-children');
        button.setAttribute('aria-pressed', 'false');
        button.textContent = typeof category.name === 'string' && category.name.trim() !== ''
          ? category.name
          : '未命名分类';
        button.addEventListener('click', function () { selectCategory(category); });
        button.addEventListener('mouseenter', function () { selectCategory(category); });
        row.appendChild(button);

        var href = safeLink(category.permalink);
        if (href) {
          var directLink = document.createElement('a');
          directLink.className = 'category-parent-link';
          directLink.href = href;
          directLink.setAttribute('aria-label', '进入“' + button.textContent + '”分类');
          directLink.title = '直接进入“' + button.textContent + '”分类';
          directLink.textContent = '→';
          row.appendChild(directLink);
        }

        parentList.appendChild(row);
      });

      if (tree[0]) selectCategory(tree[0]);
    }

    function openPanel() {
      if (!overlay.hidden) return;
      render();
      previousFocus = document.activeElement;
      overlay.hidden = false;
      document.documentElement.classList.add('timeplus-category-open');
      document.body.classList.add('timeplus-category-open');
      openButton.setAttribute('aria-expanded', 'true');
      closeButton.focus();
    }

    function closePanel() {
      if (overlay.hidden) return;
      overlay.hidden = true;
      document.documentElement.classList.remove('timeplus-category-open');
      document.body.classList.remove('timeplus-category-open');
      openButton.setAttribute('aria-expanded', 'false');
      if (previousFocus && typeof previousFocus.focus === 'function') {
        previousFocus.focus();
      }
    }

    openButton.addEventListener('click', openPanel);
    closeButton.addEventListener('click', closePanel);
    parentList.addEventListener('keydown', function (event) {
      var tabs = Array.prototype.slice.call(parentList.querySelectorAll('.category-parent-button'));
      var currentIndex = tabs.indexOf(document.activeElement);
      if (currentIndex < 0 || tabs.length === 0) return;

      var nextIndex = null;
      if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
        nextIndex = (currentIndex + 1) % tabs.length;
      } else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
        nextIndex = (currentIndex - 1 + tabs.length) % tabs.length;
      } else if (event.key === 'Home') {
        nextIndex = 0;
      } else if (event.key === 'End') {
        nextIndex = tabs.length - 1;
      }

      if (nextIndex === null) return;
      event.preventDefault();
      tabs[nextIndex].focus();
      tabs[nextIndex].click();
    });
    overlay.addEventListener('click', function (event) {
      if (event.target === overlay) closePanel();
    });
    document.addEventListener('keydown', function (event) {
      if (overlay.hidden) return;
      if (event.key === 'Escape') {
        event.preventDefault();
        closePanel();
        return;
      }
      if (event.key !== 'Tab') return;

      var focusable = getFocusable();
      if (focusable.length === 0) {
        event.preventDefault();
        dialog.focus();
        return;
      }

      var first = focusable[0];
      var last = focusable[focusable.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    });
  }

  function formatRuntime(milliseconds) {
    var totalSeconds = Math.max(0, Math.floor(milliseconds / 1000));
    var days = Math.floor(totalSeconds / 86400);
    var hours = Math.floor((totalSeconds % 86400) / 3600);
    var minutes = Math.floor((totalSeconds % 3600) / 60);
    var seconds = totalSeconds % 60;
    return days + '天 ' + hours + '时 ' + minutes + '分 ' + seconds + '秒';
  }

  function initRuntime(config) {
    var row = config.querySelector('[data-runtime-row]');
    var value = config.querySelector('[data-runtime-value]');
    var source = (config.dataset.siteEstablishedAt || '').trim();
    if (!row || !value || source === '') return;

    var start = Date.parse(source);
    if (!Number.isFinite(start) || start > Date.now()) return;

    function update() {
      value.textContent = formatRuntime(Date.now() - start);
    }

    row.hidden = false;
    update();
    window.setInterval(update, 1000);
  }

  function showMetric(config, name, value) {
    if (!Number.isFinite(value) || value < 0) return;
    var row = config.querySelector('[data-performance-row="' + name + '"]');
    var target = config.querySelector('[data-performance-value="' + name + '"]');
    if (!row || !target) return;
    target.textContent = Math.round(value) + ' ms';
    row.hidden = false;
  }

  function initPerformance(config) {
    function render() {
      if (!window.performance || typeof window.performance.getEntriesByType !== 'function') return;
      var navigation = window.performance.getEntriesByType('navigation')[0];
      if (!navigation) return;

      showMetric(config, 'response', navigation.responseStart - navigation.requestStart);
      showMetric(config, 'download', navigation.responseEnd - navigation.responseStart);
      showMetric(config, 'interactive', navigation.domInteractive - navigation.startTime);
      showMetric(config, 'load', navigation.loadEventEnd - navigation.startTime);
    }

    if (document.readyState === 'complete') {
      window.setTimeout(render, 0);
    } else {
      window.addEventListener('load', function () { window.setTimeout(render, 0); }, { once: true });
    }
  }

  function joinLocation(data) {
    return ['country', 'prov', 'city', 'district']
      .map(function (key) { return typeof data[key] === 'string' ? data[key].trim() : ''; })
      .filter(function (part, index, parts) { return part !== '' && parts.indexOf(part) === index; })
      .join(' · ');
  }

  function setVisitorField(container, selector, rowSelector, value) {
    if ((typeof value !== 'string' && typeof value !== 'number') || String(value).trim() === '') return false;
    var target = container.querySelector(selector);
    var row = container.querySelector(rowSelector);
    if (!target || !row) return false;
    target.textContent = String(value).trim();
    row.hidden = false;
    return true;
  }

  function initVisitorInfo(config) {
    if (config.dataset.visitorInfoEnabled !== '1') return;

    var endpoint = safeLink(config.dataset.visitorInfoEndpoint || '');
    if (!endpoint || new URL(endpoint).protocol !== 'https:') return;

    var container = config.querySelector('[data-visitor-info]');
    if (!container || typeof window.fetch !== 'function') return;

    var controller = typeof AbortController === 'function' ? new AbortController() : null;
    var fetchRequest = window.fetch(endpoint, {
      cache: 'no-store',
      credentials: 'omit',
      referrerPolicy: 'no-referrer',
      signal: controller ? controller.signal : undefined
    }).then(function (response) {
      if (!response.ok) throw new Error('Visitor endpoint failed');
      return response.json();
    });

    var timeoutId;
    var timeoutRequest = new Promise(function (_, reject) {
      timeoutId = window.setTimeout(function () {
        if (controller) controller.abort();
        reject(new Error('Visitor endpoint timed out'));
      }, 5000);
    });

    Promise.race([fetchRequest, timeoutRequest]).then(function (payload) {
      if (!payload || typeof payload !== 'object') return;
      var data = payload.data && typeof payload.data === 'object' ? payload.data : {};
      var isp = [data.continent, data.isp]
        .filter(function (part) { return typeof part === 'string' && part.trim() !== ''; })
        .join(' · ');
      var shown = false;
      shown = setVisitorField(container, '[data-visitor-ip]', '[data-visitor-ip-row]', payload.ip) || shown;
      shown = setVisitorField(container, '[data-visitor-isp]', '[data-visitor-isp-row]', isp) || shown;
      shown = setVisitorField(container, '[data-visitor-location]', '[data-visitor-location-row]', joinLocation(data)) || shown;
      if (shown) container.hidden = false;
    }).catch(function () {
      // Visitor information is optional; network, timeout and schema errors degrade silently.
    }).finally(function () {
      window.clearTimeout(timeoutId);
    });
  }

  function initInsights() {
    var config = document.getElementById('timeplus-enhancements-config');
    if (!config) return;
    initRuntime(config);
    initPerformance(config);
    initVisitorInfo(config);
  }

  function init() {
    initCategoryPanel();
    initInsights();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
