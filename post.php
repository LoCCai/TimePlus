<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
  exit;
}

$this->need('header.php');
$timeplusPostTitle = trim((string) $this->title);
$timeplusPostImages = timeplus_normalize_images((string) $this->fields->img);
$timeplusPostFullImages = [];
foreach ($timeplusPostImages as $timeplusPostImage) {
  $timeplusPostFullImage = timeplus_append_image_rule($timeplusPostImage, (string) $this->options->zmki_sy);
  if ($timeplusPostFullImage !== null) {
    $timeplusPostFullImages[] = $timeplusPostFullImage;
  }
}
$timeplusPostGalleryId = 'timeplus-post-gallery-' . (int) $this->cid;
$timeplusPostGalleryData = [
  'id' => $timeplusPostGalleryId,
  'context' => 'post',
  'images' => $timeplusPostFullImages,
  'originals' => $timeplusPostImages,
  'fallback' => (string) $this->options->themeUrl . 'assets/img/loading.gif'
];
$timeplusExternalUrl = timeplus_safe_url((string) $this->fields->url, true);
?>
    <main class="timeplus-post-main" id="main-content">
      <article class="timeplus-post" data-gallery-id="<?php echo timeplus_escape_attr($timeplusPostGalleryId); ?>">
        <header class="timeplus-post-header">
          <h1><?php echo timeplus_escape_html($timeplusPostTitle); ?></h1>
          <div class="timeplus-post-meta">
            <span><?php echo timeplus_icon('clock'); ?><?php $this->date(); ?></span>
            <?php if (trim((string) $this->fields->device) !== ''): ?><span><?php echo timeplus_icon('camera'); ?><?php echo timeplus_escape_html((string) $this->fields->device); ?></span><?php endif; ?>
            <?php if (trim((string) $this->fields->location) !== ''): ?><span><?php echo timeplus_icon('location'); ?><?php echo timeplus_escape_html((string) $this->fields->location); ?></span><?php endif; ?>
          </div>
        </header>

        <?php if ($timeplusPostFullImages !== []): ?>
          <div class="timeplus-post-gallery" aria-label="文章图片">
            <?php foreach ($timeplusPostFullImages as $timeplusPostImageIndex => $timeplusPostFullImage): ?>
              <?php $timeplusPostThumbnail = timeplus_append_image_rule($timeplusPostImages[$timeplusPostImageIndex], (string) $this->options->zmki_ys) ?: $timeplusPostImages[$timeplusPostImageIndex]; ?>
              <a class="timeplus-post-image timeplus-gallery-trigger" href="<?php echo timeplus_escape_attr($timeplusPostFullImage); ?>" data-image-index="<?php echo $timeplusPostImageIndex; ?>" aria-label="查看第 <?php echo $timeplusPostImageIndex + 1; ?> 张图片">
                <img src="<?php echo timeplus_escape_attr($timeplusPostThumbnail); ?>" alt="<?php echo timeplus_escape_attr($timeplusPostTitle . '，第 ' . ($timeplusPostImageIndex + 1) . ' 张'); ?>" loading="<?php echo $timeplusPostImageIndex === 0 ? 'eager' : 'lazy'; ?>" decoding="async" data-fallback-src="<?php $this->options->themeUrl('assets/img/loading.gif'); ?>">
              </a>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="timeplus-post-empty"><?php echo timeplus_icon('image'); ?><p>这篇文章没有可用图片。</p></div>
        <?php endif; ?>

        <div class="timeplus-post-content"><?php $this->content(); ?></div>
        <?php if ($timeplusExternalUrl !== null): ?>
          <p><a class="timeplus-external-link" href="<?php echo timeplus_escape_attr($timeplusExternalUrl); ?>" target="_blank" rel="noopener noreferrer nofollow">立即访问</a></p>
        <?php endif; ?>
        <footer class="timeplus-post-taxonomy">
          <span><?php $this->category('、'); ?></span>
          <?php if ($this->tags): ?><span><?php $this->tags('、', true); ?></span><?php endif; ?>
        </footer>

        <div class="timeplus-post-caption-template" hidden>
          <h2><?php echo timeplus_escape_html($timeplusPostTitle); ?></h2>
          <div class="tag-info tag-info-bottom">
            <?php if (trim((string) $this->fields->device) !== ''): ?><span><?php echo timeplus_icon('camera'); ?><?php echo timeplus_escape_html((string) $this->fields->device); ?></span><?php endif; ?>
            <?php if (trim((string) $this->fields->location) !== ''): ?><span><?php echo timeplus_icon('location'); ?><?php echo timeplus_escape_html((string) $this->fields->location); ?></span><?php endif; ?>
          </div>
          <?php if (count($timeplusPostFullImages) > 1): ?>
            <div class="breadcrumb-nav" role="group" aria-label="文章图片">
              <?php foreach ($timeplusPostFullImages as $timeplusPostImageIndex => $_timeplusPostImage): ?>
                <button class="nav-dot<?php echo $timeplusPostImageIndex === 0 ? ' active' : ''; ?>" type="button" data-index="<?php echo $timeplusPostImageIndex; ?>" aria-label="第 <?php echo $timeplusPostImageIndex + 1; ?> 张图片" aria-pressed="<?php echo $timeplusPostImageIndex === 0 ? 'true' : 'false'; ?>"></button>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <?php if ($timeplusPostImages !== []): ?><a class="timeplus-original-link" href="<?php echo timeplus_escape_attr($timeplusPostImages[0]); ?>" target="_blank" rel="noopener noreferrer">查看原图</a><?php endif; ?>
        </div>
        <script class="timeplus-gallery-data" type="application/json"><?php echo timeplus_json_encode($timeplusPostGalleryData); ?></script>
      </article>
      <?php $this->need('comments.php'); ?>
    </main>
<?php $this->need('footer.php'); ?>
