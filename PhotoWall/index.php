<?php
/**
 * PhotoWall 光影墙
 *
 * 一款以照片墙为核心视觉的 Typecho 主题，
 * 将文章、分类、标签转化为照片墙数据源，
 * 支持单图轮播、多图网格、分类/标签筛选与 Lightbox 大图预览。
 *
 * @package PhotoWall
 * @author LoCCai
 * @version 1.0.0
 * @link https://github.com/LoCCai/TimePlus
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
  exit;
}

$this->need('header.php');
?>

<main id="pw-main" class="pw-main">
  <?php
  $pwOpts = pw_theme_opts();
  $pwWall = pw_build_wall_payload($pwOpts);

  if ($pwWall['items'] !== array()):
    $pwFirst = $pwWall['items'][0];
    $pwPreloadCount = max(0, min(4, (int) pw_opt($pwOpts, 'preloadCount', 2)));
    if ($pwOpts->displayMode !== 'multi'):
      for ($pwI = 0; $pwI < $pwPreloadCount && $pwI < count($pwWall['items']); $pwI++):
        echo '<link rel="preload" as="image" href="' . pw_e($pwWall['items'][$pwI]['thumb']) . '">' . "\n";
      endfor;
    endif;
  endif;
  ?>

  <div class="pw-hero-head">
    <?php if (trim((string) pw_opt($pwOpts, 'indexTitle', '')) !== ''): ?>
      <h1 class="pw-hero-title"><?php echo pw_e(trim((string) $pwOpts->indexTitle)); ?></h1>
    <?php endif; ?>
    <?php if (trim((string) $pwOpts->indexSubtitle) !== ''): ?>
      <p class="pw-hero-subtitle"><?php echo pw_e(trim((string) $pwOpts->indexSubtitle)); ?></p>
    <?php endif; ?>
  </div>

  <nav id="pw-filters" class="pw-filters<?php echo (!pw_yn($pwOpts, 'showCategoryFilter', 1) && !pw_yn($pwOpts, 'showTagFilter', 1)) ? ' pw-filters-empty' : ''; ?>" aria-label="照片筛选">
    <?php echo pw_render_filter_buttons($pwWall, $pwOpts); ?>
  </nav>

  <div id="pw-wall" class="pw-wall pw-mode-<?php echo pw_e($pwOpts->displayMode); ?>" aria-label="照片墙" aria-live="off">
    <div class="pw-empty pw-empty-loading" id="pw-empty" hidden>
      <p>当前筛选条件下没有照片。</p>
    </div>
  </div>

  <?php if ($pwWall['items'] !== array()): ?>
  <noscript>
    <div class="pw-noscript" aria-label="照片列表">
      <?php
      $pwNoscriptCount = max(1, min(60, (int) pw_opt($pwOpts, 'noscriptCount', 24)));
      $pwRatio = pw_ratio_css($pwOpts->cardRatio);
      for ($pwI = 0; $pwI < $pwNoscriptCount && $pwI < count($pwWall['items']); $pwI++):
        $pwItem = $pwWall['items'][$pwI];
      ?>
      <a class="pw-noscript-item" href="<?php echo pw_e($pwItem['permalink']); ?>">
        <img src="<?php echo pw_e($pwItem['thumb']); ?>" alt="<?php echo pw_e($pwItem['title']); ?>" loading="lazy" decoding="async"<?php echo $pwRatio !== '' ? ' style="aspect-ratio:' . pw_e($pwRatio) . '"' : ''; ?>>
        <span class="pw-noscript-title"><?php echo pw_e($pwItem['title']); ?></span>
      </a>
      <?php endfor; ?>
    </div>
  </noscript>
  <?php else: ?>
  <div class="pw-empty">
    <p>还没有可展示的照片，发布带图片的文章试试。</p>
  </div>
  <?php endif; ?>

  <script id="pw-data" type="application/json"><?php echo pw_json_payload($pwWall['items']); ?></script>
  <?php if (pw_yn($pwOpts, 'jsonldEnabled', 1) && $pwWall['items'] !== array()): ?>
  <script type="application/ld+json"><?php echo pw_json_ld($pwWall['items'], $pwOpts); ?></script>
  <?php endif; ?>
</main>

<?php $this->need('footer.php'); ?>
