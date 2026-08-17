<?php
/**
 * 一款简约的相册主题
 * @package 洪墨时光
 * @author zhheo & LoCCai
 * @version 2.20
 * @link https://github.com/LoCCai/TimePlus
 */
if (!defined('__TYPECHO_ROOT_DIR__')) {
  exit;
}

$this->need('header.php');
$timeplusItemIndex = 0;
$timeplusThumbnailRule = (string) $this->options->zmki_ys;
$timeplusFullImageRule = (string) $this->options->zmki_sy;
?>
    <main id="main">
      <?php while ($this->next()): ?>
        <?php
        $timeplusItemIndex++;
        $timeplusGalleryId = 'timeplus-gallery-' . (int) $this->cid;
        $timeplusTitle = trim((string) $this->title);
        $timeplusPermalink = timeplus_safe_url((string) $this->permalink, true) ?: (string) $this->options->siteUrl;
        $timeplusImages = timeplus_normalize_images((string) $this->fields->img);
        $timeplusFullImages = [];
        foreach ($timeplusImages as $timeplusImage) {
          $timeplusFullImage = timeplus_append_image_rule($timeplusImage, $timeplusFullImageRule);
          if ($timeplusFullImage !== null) {
            $timeplusFullImages[] = $timeplusFullImage;
          }
        }
        $timeplusGalleryData = [
          'id' => $timeplusGalleryId,
          'context' => 'archive',
          'images' => $timeplusFullImages,
          'originals' => $timeplusImages,
          'fallback' => (string) $this->options->themeUrl . 'assets/img/loading.gif'
        ];
        ?>
        <article class="thumb img-area<?php echo $timeplusFullImages === [] ? ' timeplus-no-image-card' : ''; ?>" data-gallery-id="<?php echo timeplus_escape_attr($timeplusGalleryId); ?>">
          <?php if ($timeplusFullImages !== []): ?>
            <?php $timeplusThumbnail = timeplus_append_image_rule($timeplusImages[0], $timeplusThumbnailRule) ?: $timeplusImages[0]; ?>
            <a class="image my-photo timeplus-gallery-trigger" href="<?php echo timeplus_escape_attr($timeplusFullImages[0]); ?>" data-image-index="0" aria-label="查看“<?php echo timeplus_escape_attr($timeplusTitle); ?>”图片">
              <img
                class="zmki_px my-photo"
                src="<?php echo timeplus_escape_attr($timeplusThumbnail); ?>"
                alt="<?php echo timeplus_escape_attr($timeplusTitle); ?>"
                loading="<?php echo $timeplusItemIndex <= 2 ? 'eager' : 'lazy'; ?>"
                decoding="async"
                data-fallback-src="<?php $this->options->themeUrl('assets/img/loading.gif'); ?>"
              >
            </a>
          <?php else: ?>
            <a class="image timeplus-empty-image" href="<?php echo timeplus_escape_attr($timeplusPermalink); ?>" aria-label="进入“<?php echo timeplus_escape_attr($timeplusTitle); ?>”文章页">
              <?php echo timeplus_icon('image'); ?><span>暂无可用图片</span>
            </a>
          <?php endif; ?>

          <h2><a href="<?php echo timeplus_escape_attr($timeplusPermalink); ?>"><?php echo timeplus_escape_html($timeplusTitle); ?></a></h2>
          <div class="content-wrapper"><?php $this->content(); ?></div>
          <div class="tag-info tag-info-bottom">
            <?php if (trim((string) $this->fields->device) !== ''): ?>
              <span class="tag-device"><?php echo timeplus_icon('camera'); ?><?php echo timeplus_escape_html((string) $this->fields->device); ?></span>
            <?php endif; ?>
            <?php if (trim((string) $this->fields->location) !== ''): ?>
              <span class="tag-location"><?php echo timeplus_icon('location'); ?><?php echo timeplus_escape_html((string) $this->fields->location); ?></span>
            <?php endif; ?>
            <span class="tag-time"><?php echo timeplus_icon('clock'); ?><?php $this->date(); ?></span>
          </div>
          <div class="tag-info">
            <span class="tag-categorys"><?php $this->category(''); ?></span>
            <?php if ($this->tags): ?><span class="tag-list"><?php $this->tags('', true); ?></span><?php endif; ?>
          </div>
          <?php if (count($timeplusFullImages) > 1): ?>
            <div class="breadcrumb-nav" role="group" aria-label="文章图片">
              <?php foreach ($timeplusFullImages as $timeplusImageIndex => $_timeplusImage): ?>
                <button class="nav-dot<?php echo $timeplusImageIndex === 0 ? ' active' : ''; ?>" type="button" data-index="<?php echo $timeplusImageIndex; ?>" aria-label="第 <?php echo $timeplusImageIndex + 1; ?> 张图片" aria-pressed="<?php echo $timeplusImageIndex === 0 ? 'true' : 'false'; ?>"></button>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <?php if ($timeplusFullImages !== []): ?>
            <a class="timeplus-original-link" href="<?php echo timeplus_escape_attr($timeplusImages[0]); ?>" target="_blank" rel="noopener noreferrer">查看原图</a>
          <?php endif; ?>
          <script class="timeplus-gallery-data" type="application/json"><?php echo timeplus_json_encode($timeplusGalleryData); ?></script>
        </article>
      <?php endwhile; ?>

      <?php
      $timeplusPageSize = max(1, (int) $this->parameter->pageSize);
      $timeplusTotalPages = (int) ceil($this->getTotal() / $timeplusPageSize);
      if ($timeplusTotalPages > 1):
      ?>
        <nav class="pagination-container" aria-label="文章分页">
          <?php $this->pageNav('上一页', '下一页', 3, '…'); ?>
        </nav>
      <?php endif; ?>
    </main>
<?php $this->need('footer.php'); ?>
