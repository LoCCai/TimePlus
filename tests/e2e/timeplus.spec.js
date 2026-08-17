const { test, expect } = require('@playwright/test');

async function loadVisitorScenario(page, scenario) {
  await page.addInitScript((mode) => {
    window.__visitorFetchCalls = 0;
    window.__visitorFetchSettled = false;
    window.__visitorAborted = false;
    window.fetch = function (_url, options) {
      window.__visitorFetchCalls += 1;
      if (mode === 'success') {
        return Promise.resolve({
          ok: true,
          json: function () {
            window.__visitorFetchSettled = true;
            return Promise.resolve({
              ip: '203.0.113.8',
              data: {
                continent: '亚洲',
                isp: 'Example ISP',
                country: '中国',
                prov: '上海市',
                city: '上海市',
                district: '浦东新区'
              }
            });
          }
        });
      }
      if (mode === 'exception') {
        return Promise.resolve({
          ok: true,
          json: function () {
            window.__visitorFetchSettled = true;
            return Promise.reject(new Error('Invalid visitor response'));
          }
        });
      }
      if (mode === 'timeout') {
        return new Promise(function (_resolve, reject) {
          if (options && options.signal) {
            options.signal.addEventListener('abort', function () {
              window.__visitorAborted = true;
              reject(new DOMException('Aborted', 'AbortError'));
            }, { once: true });
          }
        });
      }
      return Promise.reject(new Error('Visitor fetch must remain disabled'));
    };
    document.addEventListener('DOMContentLoaded', function () {
      if (mode === 'disabled') return;
      var config = document.getElementById('timeplus-enhancements-config');
      config.dataset.visitorInfoEnabled = '1';
      config.dataset.visitorInfoEndpoint = 'https://visitor.test/info';
    }, { once: true });
  }, scenario);
  await page.goto('/tests/fixtures/index.html');
}

test.beforeEach(async ({ page }) => {
  await page.goto('/tests/fixtures/index.html');
});

test('opens one gallery instance and restores page state on close', async ({ page }) => {
  const initialPageState = await page.evaluate(() => {
    document.querySelector('#main').style.paddingTop = '800px';
    document.body.style.backgroundColor = 'rgb(17, 19, 23)';
    document.documentElement.style.scrollBehavior = 'auto';
    window.scrollTo(0, 400);
    return {
      bodyStyle: document.body.getAttribute('style'),
      htmlStyle: document.documentElement.getAttribute('style'),
      scrollY: window.scrollY
    };
  });
  const trigger = page.locator('.timeplus-gallery-trigger').first();
  await trigger.click();
  const popup = page.locator('.poptrox-popup:visible');
  await expect(popup).toHaveCount(1);
  await expect(popup).toHaveAttribute('role', 'dialog');
  await expect(popup).toHaveAttribute('aria-modal', 'true');
  await expect(page.locator('body')).toHaveClass(/modal-active/);
  await expect(popup).not.toHaveClass(/loading/);
  await expect(popup.locator('.closer')).toBeFocused();
  await popup.locator('.nav-next').focus();
  await page.keyboard.press('Tab');
  await expect(popup.locator('h2 a')).toBeFocused();
  await page.keyboard.press('Shift+Tab');
  await expect(popup.locator('.nav-next')).toBeFocused();
  await popup.locator('.closer').focus();
  await page.keyboard.press('Enter');
  await expect(page.locator('body')).not.toHaveClass(/modal-active/);
  await expect(trigger).toBeFocused();
  await expect.poll(() => page.evaluate(() => window.scrollY)).toBe(initialPageState.scrollY);
  expect(await page.locator('body').getAttribute('style')).toBe(initialPageState.bodyStyle);
  expect(await page.locator('html').getAttribute('style')).toBe(initialPageState.htmlStyle);
  await expect(page.locator('.poptrox-overlay')).toHaveCount(1);
});

test('switches multi-image state with dots and keyboard', async ({ page }) => {
  await page.locator('.timeplus-gallery-trigger').first().click();
  const popup = page.locator('.poptrox-popup:visible');
  await popup.locator('.nav-dot').nth(1).click();
  await expect(popup.locator('.nav-dot').nth(1)).toHaveAttribute('aria-pressed', 'true');
  await expect(popup.locator('.timeplus-original-link')).toHaveAttribute('href', /fixture-image-2\.svg$/);
  await page.keyboard.press('ArrowLeft');
  await expect(popup.locator('.nav-dot').nth(0)).toHaveAttribute('aria-pressed', 'true');
});

test('wheel navigation stops at gallery boundaries', async ({ page }) => {
  await page.locator('.timeplus-gallery-trigger').first().click();
  const popup = page.locator('.poptrox-popup:visible');
  await expect(popup).not.toHaveClass(/loading/);
  const imageBox = await popup.locator('.pic img').boundingBox();
  await page.mouse.move(imageBox.x + imageBox.width / 2, imageBox.y + imageBox.height / 2);
  await page.mouse.wheel(0, 120);
  await expect(popup.locator('.nav-dot').nth(1)).toHaveAttribute('aria-pressed', 'true');
  await page.mouse.wheel(0, -120);
  await expect(popup.locator('.nav-dot').nth(0)).toHaveAttribute('aria-pressed', 'true');
  await page.mouse.wheel(0, -120);
  await expect(popup.locator('.nav-dot').nth(0)).toHaveAttribute('aria-pressed', 'true');
});

test('failed images show the fallback and release gallery navigation', async ({ page }) => {
  await page.locator('.timeplus-gallery-trigger').first().click();
  const popup = page.locator('.poptrox-popup:visible');
  await popup.locator('.nav-dot').nth(2).click();
  await expect(popup.locator('.nav-dot').nth(2)).toHaveAttribute('aria-pressed', 'true');
  await expect(popup.locator('.pic img')).toHaveAttribute('src', /assets\/img\/loading\.gif$/);
  await popup.locator('.nav-dot').nth(0).click();
  await expect(popup.locator('.nav-dot').nth(0)).toHaveAttribute('aria-pressed', 'true');
  await expect(popup.locator('.pic img')).toHaveAttribute('src', /fixture-image-1\.svg$/);
});

test('gallery buttons hand archive boundaries back to Poptrox', async ({ page }) => {
  await page.locator('.timeplus-gallery-trigger').first().click();
  const popup = page.locator('.poptrox-popup:visible');
  await popup.locator('.nav-dot').nth(2).click();
  await expect(popup.locator('.nav-dot').nth(2)).toHaveAttribute('aria-pressed', 'true');
  await popup.locator('.nav-next').click();
  await expect(popup.locator('.caption h2')).toHaveText('Adjacent article');
  await expect(popup).not.toHaveClass(/loading/);
  await page.waitForTimeout(300);
  await popup.locator('.nav-previous').click();
  await expect(popup.locator('.caption h2')).toHaveText('Fixture');
});

test('empty galleries remain native links to the article page', async ({ page }) => {
  const card = page.locator('.timeplus-no-image-card');
  await expect(card.locator('.timeplus-gallery-trigger')).toHaveCount(0);
  await expect(card.locator('.timeplus-empty-image')).toHaveAttribute('href', /tests\/fixtures\/post-empty\.html$/);
  await expect(card.locator('.timeplus-empty-image')).toContainText('暂无可用图片');
  await page.goto('/tests/fixtures/post-empty.html');
  await expect(page.locator('.timeplus-post-gallery')).toHaveCount(0);
  await expect(page.locator('.timeplus-post-empty')).toBeVisible();
  await expect(page.locator('.timeplus-post-content')).toContainText('完整正文仍然必须显示');
});

test('gallery images and links remain usable without JavaScript', async ({ browser }, testInfo) => {
  test.skip(!testInfo.project.name.startsWith('desktop-'), 'The no-script fallback only needs one browser profile.');
  const context = await browser.newContext({
    baseURL: 'http://127.0.0.1:4173',
    javaScriptEnabled: false,
    viewport: { width: 1280, height: 720 }
  });
  const page = await context.newPage();
  await page.goto('/tests/fixtures/index.html');
  const trigger = page.locator('.timeplus-gallery-trigger').first();
  await expect(trigger).toHaveAttribute('href', /fixture-image-1\.svg$/);
  await expect(trigger.locator('img')).toBeVisible();
  await expect(trigger.locator('img')).toHaveAttribute('src', /fixture-image-1\.svg$/);
  await expect(page.locator('.poptrox-popup')).toHaveCount(0);
  await context.close();
});

test('category dialog traps focus and closes with Escape', async ({ page }) => {
  await page.locator('#open-category-panel').click();
  await expect(page.locator('#category-panel')).toBeVisible();
  await expect(page.locator('#close-category-panel')).toBeFocused();
  await expect(page.locator('.category-parent-button')).toHaveCount(2);
  await page.keyboard.press('Tab');
  await expect(page.locator('.category-parent-button').first()).toBeFocused();
  await page.keyboard.press('End');
  await expect(page.locator('.category-parent-button').last()).toBeFocused();
  await page.keyboard.press('Home');
  await expect(page.locator('.category-parent-button').first()).toBeFocused();
  await page.keyboard.press('ArrowDown');
  await expect(page.locator('.category-parent-button').last()).toBeFocused();
  await page.locator('.category-child-link').last().focus();
  await page.keyboard.press('Tab');
  await expect(page.locator('#close-category-panel')).toBeFocused();
  await page.keyboard.press('Escape');
  await expect(page.locator('#category-panel')).toBeHidden();
  await expect(page.locator('#open-category-panel')).toBeFocused();
});

test('fullscreen rejection keeps the button state accurate', async ({ page }) => {
  test.skip((await page.viewportSize()).width <= 1221, 'The compact header intentionally hides fullscreen.');
  await page.addInitScript(() => {
    Object.defineProperty(Element.prototype, 'requestFullscreen', {
      configurable: true,
      value: () => Promise.reject(new Error('denied'))
    });
    Object.defineProperty(Document.prototype, 'exitFullscreen', {
      configurable: true,
      value: () => Promise.resolve()
    });
  });
  await page.goto('/tests/fixtures/index.html');
  await page.locator('#fullscreen').click();
  await expect(page.locator('#fullscreen')).toHaveAttribute('aria-pressed', 'false');
  await expect(page.locator('[data-fullscreen-label]')).toHaveText('全屏');
});

test('fullscreenchange synchronizes entry and Escape-style exit', async ({ page }) => {
  test.skip((await page.viewportSize()).width <= 1221, 'The compact header intentionally hides fullscreen.');
  await page.addInitScript(() => {
    window.__timeplusFullscreenElement = null;
    Object.defineProperty(Document.prototype, 'fullscreenElement', {
      configurable: true,
      get: () => window.__timeplusFullscreenElement
    });
    Object.defineProperty(Element.prototype, 'requestFullscreen', {
      configurable: true,
      value: function () {
        window.__timeplusFullscreenElement = this;
        document.dispatchEvent(new Event('fullscreenchange'));
        return Promise.resolve();
      }
    });
    Object.defineProperty(Document.prototype, 'exitFullscreen', {
      configurable: true,
      value: function () {
        window.__timeplusFullscreenElement = null;
        document.dispatchEvent(new Event('fullscreenchange'));
        return Promise.resolve();
      }
    });
  });
  await page.goto('/tests/fixtures/index.html');
  await page.locator('#fullscreen').click();
  await expect(page.locator('#fullscreen')).toHaveAttribute('aria-pressed', 'true');
  await expect(page.locator('[data-fullscreen-label]')).toHaveText('退出全屏');
  await page.evaluate(() => {
    window.__timeplusFullscreenElement = null;
    document.dispatchEvent(new Event('fullscreenchange'));
  });
  await expect(page.locator('#fullscreen')).toHaveAttribute('aria-pressed', 'false');
  await expect(page.locator('[data-fullscreen-label]')).toHaveText('全屏');
});

test('mobile horizontal swipe advances one image', async ({ page, context }, testInfo) => {
  test.skip(!testInfo.project.name.startsWith('mobile-'), 'Touch behavior is covered by the mobile project.');
  await page.locator('.timeplus-gallery-trigger').first().click();
  const popup = page.locator('.poptrox-popup:visible');
  await expect(popup).not.toHaveClass(/loading/);
  const box = await popup.locator('.pic img').boundingBox();
  const client = await context.newCDPSession(page);
  const start = { x: box.x + box.width * 0.65, y: box.y + box.height * 0.5 };
  const end = { x: box.x + box.width * 0.35, y: start.y };
  await client.send('Input.dispatchTouchEvent', { type: 'touchStart', touchPoints: [end] });
  await client.send('Input.dispatchTouchEvent', { type: 'touchMove', touchPoints: [start] });
  await client.send('Input.dispatchTouchEvent', { type: 'touchEnd', touchPoints: [] });
  await expect(popup.locator('.nav-dot').nth(0)).toHaveAttribute('aria-pressed', 'true');
  await client.send('Input.dispatchTouchEvent', { type: 'touchStart', touchPoints: [start] });
  await client.send('Input.dispatchTouchEvent', { type: 'touchMove', touchPoints: [end] });
  await client.send('Input.dispatchTouchEvent', { type: 'touchEnd', touchPoints: [] });
  await expect(popup.locator('.nav-dot').nth(1)).toHaveAttribute('aria-pressed', 'true');
});

test('visitor information remains disabled by default', async ({ page }) => {
  await loadVisitorScenario(page, 'disabled');
  await expect(page.locator('[data-visitor-info]')).toBeHidden();
  expect(await page.evaluate(() => window.__visitorFetchCalls)).toBe(0);
});

test('visitor information renders a successful response', async ({ page }) => {
  await loadVisitorScenario(page, 'success');
  const visitor = page.locator('[data-visitor-info]');
  await expect(visitor).toBeVisible();
  await expect(visitor.locator('[data-visitor-ip]')).toHaveText('203.0.113.8');
  await expect(visitor.locator('[data-visitor-isp]')).toHaveText('亚洲 · Example ISP');
  await expect(visitor.locator('[data-visitor-location]')).toHaveText('中国 · 上海市 · 浦东新区');
});

test('visitor information silently handles response exceptions', async ({ page }) => {
  const pageErrors = [];
  page.on('pageerror', (error) => pageErrors.push(error));
  await loadVisitorScenario(page, 'exception');
  await expect.poll(() => page.evaluate(() => window.__visitorFetchSettled)).toBe(true);
  await expect(page.locator('[data-visitor-info]')).toBeHidden();
  expect(pageErrors).toEqual([]);
});

test('visitor information aborts a timed-out request', async ({ page }) => {
  await loadVisitorScenario(page, 'timeout');
  await expect.poll(() => page.evaluate(() => window.__visitorAborted), { timeout: 7_000 }).toBe(true);
  await expect(page.locator('[data-visitor-info]')).toBeHidden();
});

test('matches the home, gallery, category and article visual baselines', async ({ page }) => {
  const homeImages = await page.locator('#main img').all();
  await Promise.all(homeImages.map((image) => image.evaluate((node) => node.decode())));
  await page.locator('#timeplus-enhancements-config').evaluate((node) => { node.hidden = true; });
  await expect(page).toHaveScreenshot('home.png', { fullPage: true });

  await page.locator('.timeplus-gallery-trigger').first().click();
  const popup = page.locator('.poptrox-popup:visible');
  await expect(popup).not.toHaveClass(/loading/);
  await expect(page).toHaveScreenshot('gallery.png');
  await popup.locator('.closer').click();
  await expect(page.locator('.poptrox-overlay')).toBeHidden();

  await page.locator('#open-category-panel').click();
  await expect(page.locator('#category-panel')).toBeVisible();
  await expect(page).toHaveScreenshot('category.png');

  await page.goto('/tests/fixtures/post.html');
  const postImages = await page.locator('.timeplus-post-gallery img').all();
  await Promise.all(postImages.map((image) => image.evaluate((node) => node.decode())));
  await expect(page).toHaveScreenshot('post.png', { fullPage: true });
});
