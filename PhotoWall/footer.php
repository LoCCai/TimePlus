<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
  exit;
}

$pwOpts = pw_theme_opts();
$pwAssets = rtrim((string) $this->options->themeUrl, '/');

$pwPreset = array();
if ($this->is('category')) {
  $pwPreset = array('type' => 'category', 'slug' => (string) $this->getArchiveSlug());
} elseif ($this->is('tag')) {
  $pwPreset = array('type' => 'tag', 'slug' => (string) $this->getArchiveSlug());
}

$pwConfig = array(
  'mode' => (string) pw_opt($pwOpts, 'displayMode', 'single'),
  'columns' => (int) pw_opt($pwOpts, 'gridColumns', 3),
  'effect' => (string) pw_opt($pwOpts, 'transitionEffect', 'fade'),
  'interval' => max(2, min(60, (int) pw_opt($pwOpts, 'slideInterval', 5))) * 1000,
  'duration' => max(150, min(4000, (int) pw_opt($pwOpts, 'transitionDuration', 800))),
  'easing' => (string) pw_opt($pwOpts, 'easing', 'cubic-bezier(.4,0,.2,1)'),
  'randomOrder' => pw_yn($pwOpts, 'randomOrder', 1) === 1,
  'hoverPause' => pw_yn($pwOpts, 'hoverPause', 1) === 1,
  'pauseOnHidden' => pw_yn($pwOpts, 'pauseOnHidden', 1) === 1,
  'calmOnMobile' => pw_yn($pwOpts, 'calmOnMobile', 1) === 1,
  'lazyload' => pw_yn($pwOpts, 'lazyload', 1) === 1,
  'ratio' => pw_ratio_css(pw_opt($pwOpts, 'cardRatio', 'natural')),
  'urlFilter' => pw_yn($pwOpts, 'urlFilter', 1) === 1,
  'presetFilter' => $pwPreset,
  'lightbox' => array(
    'enabled' => pw_yn($pwOpts, 'enableLightbox', 1) === 1,
    'title' => pw_yn($pwOpts, 'showTitle', 1) === 1,
    'description' => pw_yn($pwOpts, 'showDescription', 1) === 1,
    'category' => pw_yn($pwOpts, 'showCategory', 1) === 1,
    'tags' => pw_yn($pwOpts, 'showTags', 1) === 1,
    'date' => pw_yn($pwOpts, 'showDate', 1) === 1,
    'download' => pw_yn($pwOpts, 'showDownload', 1) === 1,
    'original' => pw_yn($pwOpts, 'showOriginal', 1) === 1,
    'share' => pw_yn($pwOpts, 'showShare', 1) === 1,
    'gotoPost' => pw_yn($pwOpts, 'showGotoPost', 1) === 1,
    'keyboard' => pw_yn($pwOpts, 'lightboxKeyboard', 1) === 1,
    'gesture' => pw_yn($pwOpts, 'lightboxGesture', 1) === 1,
  ),
  'api' => array(
    'enabled' => pw_yn($pwOpts, 'apiEnabled', 1) === 1,
    'threshold' => max(0, (int) pw_opt($pwOpts, 'apiThreshold', 300)),
    'endpoint' => rtrim((string) $this->options->siteUrl, '/') . '/?pw-api=1',
  ),
  'siteUrl' => rtrim((string) $this->options->siteUrl, '/') . '/',
);
?>
<footer class="pw-footer">
  <div class="pw-footer-inner">
    <?php if (trim((string) pw_opt($pwOpts, 'footerText', '')) !== ''): ?>
    <p class="pw-footer-text"><?php echo trim((string) $pwOpts->footerText); ?></p>
    <?php endif; ?>
    <p class="pw-footer-meta">Powered by <a href="http://typecho.org" target="_blank" rel="noopener">Typecho</a> · Theme <a href="https://github.com/LoCCai/TimePlus" target="_blank" rel="noopener">PhotoWall</a></p>
  </div>
</footer>
<script id="pw-config" type="application/json"><?php echo pw_json_payload($pwConfig); ?></script>
<script src="<?php echo pw_e($pwAssets); ?>/assets/js/photowall.js?v=1.0.0" defer></script>
<script src="<?php echo pw_e($pwAssets); ?>/assets/js/lightbox.js?v=1.0.0" defer></script>
<script src="<?php echo pw_e($pwAssets); ?>/assets/js/filter.js?v=1.0.0" defer></script>
<script src="<?php echo pw_e($pwAssets); ?>/assets/js/main.js?v=1.0.0" defer></script>
<?php $this->footer(); ?>
</body>
</html>
