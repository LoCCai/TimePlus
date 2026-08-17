<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
  exit;
}

$timeplusContacts = [
  ['url' => timeplus_safe_url((string) $this->options->xxhome, true), 'label' => '主页', 'icon' => 'home'],
  ['url' => timeplus_safe_url((string) $this->options->xxweibo, true), 'label' => '微博', 'icon' => 'social'],
  ['url' => timeplus_safe_url((string) $this->options->xxgithub, true), 'label' => 'GitHub', 'icon' => 'github']
];
$timeplusCategoryJson = timeplus_json_encode(timeplus_get_category_tree());
?>
    <footer id="footer" class="panel" aria-hidden="true">
      <div id="about">
        <section>
          <h2>关于<?php echo timeplus_escape_html((string) $this->options->IndexName); ?></h2>
          <div class="trusted-html"><?php echo (string) $this->options->Biglogo; ?></div>
        </section>
        <section>
          <h2>联系我</h2>
          <ul class="icons">
            <?php foreach ($timeplusContacts as $timeplusContact): ?>
              <?php if ($timeplusContact['url'] !== null): ?>
                <li>
                  <a class="contact_link" href="<?php echo timeplus_escape_attr($timeplusContact['url']); ?>" target="_blank" rel="noopener noreferrer nofollow" aria-label="<?php echo timeplus_escape_attr($timeplusContact['label']); ?>">
                    <?php echo timeplus_icon($timeplusContact['icon']); ?>
                  </a>
                </li>
              <?php endif; ?>
            <?php endforeach; ?>
          </ul>
        </section>
        <section class="timeplus-site-insights" aria-labelledby="timeplus-insights-title">
          <h2 id="timeplus-insights-title">站点信息</h2>
          <div
            id="timeplus-enhancements-config"
            data-site-established-at="<?php echo timeplus_escape_attr((string) $this->options->siteEstablishedAt); ?>"
            data-visitor-info-enabled="<?php echo (string) $this->options->enableVisitorInfo === '1' ? '1' : '0'; ?>"
            data-visitor-info-endpoint="<?php echo timeplus_escape_attr((string) $this->options->visitorInfoEndpoint); ?>"
          >
            <p class="timeplus-runtime" data-runtime-row hidden>已运行：<strong data-runtime-value></strong></p>
            <dl class="timeplus-performance" aria-label="页面性能">
              <div data-performance-row="response" hidden><dt>服务器响应</dt><dd data-performance-value="response"></dd></div>
              <div data-performance-row="download" hidden><dt>内容下载</dt><dd data-performance-value="download"></dd></div>
              <div data-performance-row="interactive" hidden><dt>可交互</dt><dd data-performance-value="interactive"></dd></div>
              <div data-performance-row="load" hidden><dt>页面加载</dt><dd data-performance-value="load"></dd></div>
            </dl>
            <div class="timeplus-visitor-info" data-visitor-info hidden>
              <p data-visitor-isp-row hidden>网络：<strong data-visitor-isp></strong></p>
              <p data-visitor-ip-row hidden>IP：<strong data-visitor-ip></strong></p>
              <p data-visitor-location-row hidden>位置：<strong data-visitor-location></strong></p>
            </div>
          </div>
        </section>
        <div class="footer-meta">
          <?php echo (string) $this->options->cnzz; ?>
          <div class="copyright-info">
            <span class="copyright">&copy; 设计 ZHHEO &amp; ZMKI</span>
            <span class="theme">主题：<a href="https://github.com/LoCCai/TimePlus" target="_blank" rel="noopener noreferrer nofollow">洪墨时光</a></span>
            <?php if (trim((string) $this->options->police) !== ''): ?>
              <span class="police"><img src="<?php $this->options->themeUrl('assets/img/police.png'); ?>" alt="" width="14" height="14"><a href="https://beian.mps.gov.cn/#/query/webSearch" target="_blank" rel="noopener noreferrer nofollow"><?php echo timeplus_escape_html((string) $this->options->police); ?></a></span>
            <?php endif; ?>
            <?php if (trim((string) $this->options->icp) !== ''): ?>
              <span class="icp"><a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener noreferrer nofollow"><?php echo timeplus_escape_html((string) $this->options->icp); ?></a></span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </footer>
  </div>

  <div class="category-overlay" id="category-panel" hidden>
    <section class="category-dialog" role="dialog" aria-modal="true" aria-labelledby="category-panel-title" tabindex="-1">
      <header class="category-dialog-header">
        <div><h2 id="category-panel-title">全部分类</h2><a class="category-all-link" href="<?php echo timeplus_escape_attr((string) $this->options->siteUrl); ?>">查看全部内容</a></div>
        <button class="category-dialog-close" id="close-category-panel" type="button" aria-label="关闭分类面板">&times;</button>
      </header>
      <div class="category-dialog-body">
        <nav class="category-parent-list" id="category-parent-list" aria-label="一级分类"></nav>
        <div class="category-children" id="category-children" role="region" aria-live="polite"></div>
      </div>
    </section>
  </div>
  <script id="timeplus-category-data" type="application/json"><?php echo $timeplusCategoryJson; ?></script>
  <?php $this->footer(); ?>
  <script src="<?php $this->options->themeUrl('assets/js/jquery.min.js'); ?>"></script>
  <script src="<?php $this->options->themeUrl('assets/js/jquery.poptrox.min.js'); ?>"></script>
  <script src="<?php $this->options->themeUrl('assets/js/browser.min.js'); ?>"></script>
  <script src="<?php $this->options->themeUrl('assets/js/breakpoints.min.js'); ?>"></script>
  <script src="<?php $this->options->themeUrl('assets/js/util.js'); ?>"></script>
  <script src="<?php $this->options->themeUrl('assets/js/main.js'); ?>"></script>
  <script src="<?php $this->options->themeUrl('assets/js/timeplus-gallery.js'); ?>"></script>
  <script src="<?php $this->options->themeUrl('assets/js/timeplus-enhancements.js'); ?>"></script>
</body>
</html>
