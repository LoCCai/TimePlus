<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
  exit;
}

$this->need('header.php');

$pwOpts = pw_theme_opts();

// 分类 / 标签归档复用照片墙，并预设筛选条件；
// 日期 / 作者 / 搜索归档退回轻量列表布局。
$pwUseWall = $this->is('category') || $this->is('tag');
?>
<main id="pw-main" class="pw-main<?php echo $pwUseWall ? '' : ' pw-main-inner'; ?>">
  <header class="pw-archive-head">
    <h1 class="pw-archive-title"><?php $this->archiveTitle(array(
      'category' => '分类：%s',
      'tag' => '标签：%s',
      'author' => '作者：%s',
      'date' => '日期：%s',
      'search' => '搜索：%s',
    ), '', ''); ?></h1>
    <?php if (trim((string) $this->getDescription()) !== ''): ?>
    <p class="pw-archive-desc"><?php echo pw_e(trim((string) $this->getDescription())); ?></p>
    <?php endif; ?>
  </header>

  <?php if ($pwUseWall): ?>
  <?php
  $pwWall = pw_build_wall_payload($pwOpts);
  ?>
  <nav id="pw-filters" class="pw-filters" aria-label="照片筛选">
    <?php echo pw_render_filter_buttons($pwWall, $pwOpts, false); ?>
  </nav>

  <div id="pw-wall" class="pw-wall pw-mode-<?php echo pw_e($pwOpts->displayMode); ?>" aria-label="照片墙">
    <div class="pw-empty" id="pw-empty" hidden>
      <p>当前筛选条件下没有照片。</p>
    </div>
  </div>

  <?php if ($pwWall['items'] !== array()): ?>
  <noscript>
    <div class="pw-noscript" aria-label="照片列表">
      <?php
      $pwPresetSlug = (string) $this->getArchiveSlug();
      $pwPresetType = $this->is('category') ? 'category' : 'tag';
      $pwRatio = pw_ratio_css($pwOpts->cardRatio);
      $pwShown = 0;
      for ($pwI = 0; $pwI < count($pwWall['items']) && $pwShown < 24; $pwI++):
        $pwItem = $pwWall['items'][$pwI];
        $pwSlugs = $pwPresetType === 'category' ? $pwItem['categorySlugs'] : $pwItem['tagSlugs'];
        if (!in_array($pwPresetSlug, $pwSlugs, true)) {
          continue;
        }
        $pwShown++;
      ?>
      <a class="pw-noscript-item" href="<?php echo pw_e($pwItem['permalink']); ?>">
        <img src="<?php echo pw_e($pwItem['thumb']); ?>" alt="<?php echo pw_e($pwItem['title']); ?>" loading="lazy" decoding="async"<?php echo $pwRatio !== '' ? ' style="aspect-ratio:' . pw_e($pwRatio) . '"' : ''; ?>>
        <span class="pw-noscript-title"><?php echo pw_e($pwItem['title']); ?></span>
      </a>
      <?php endfor; ?>
    </div>
  </noscript>
  <?php endif; ?>

  <script id="pw-data" type="application/json"><?php echo pw_json_payload($pwWall['items']); ?></script>

  <?php else: ?>

  <div class="pw-archive-list">
    <?php while ($this->next()): ?>
    <article class="pw-archive-item">
      <a class="pw-archive-item-link" href="<?php $this->permalink(); ?>">
        <?php
        $pwItemThumb = '';
        if (isset($this->fields->cover) && trim((string) $this->fields->cover) !== '') {
          $pwItemThumb = pw_abs_url(trim((string) $this->fields->cover));
        } else {
          $pwItemThumb = pw_first_image_from_html((string) $this->content);
        }
        ?>
        <?php if ($pwItemThumb !== ''): ?>
        <span class="pw-archive-item-thumb"><img src="<?php echo pw_e($pwItemThumb); ?>" alt="<?php echo pw_e(trim((string) $this->title)); ?>" loading="lazy" decoding="async"></span>
        <?php endif; ?>
        <span class="pw-archive-item-body">
          <span class="pw-archive-item-title"><?php $this->title(); ?></span>
          <time class="pw-archive-item-date" datetime="<?php echo pw_e(date('c', $this->created)); ?>"><?php echo pw_e(date('Y-m-d', $this->created)); ?></time>
          <span class="pw-archive-item-excerpt"><?php echo pw_e(pw_excerpt((string) $this->content, 120)); ?></span>
        </span>
      </a>
    </article>
    <?php endwhile; ?>
  </div>

  <nav class="pw-pagination" aria-label="分页导航">
    <?php $this->pageNav('上一页', '下一页', 1, '...'); ?>
  </nav>

  <?php endif; ?>
</main>
<?php $this->need('footer.php'); ?>
