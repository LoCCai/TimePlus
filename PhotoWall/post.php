<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
  exit;
}

$this->need('header.php');

$pwOpts = pw_theme_opts();
$pwPostTitle = trim((string) $this->title);
$pwCover = '';
if (isset($this->fields->cover) && trim((string) $this->fields->cover) !== '') {
  $pwCover = pw_abs_url(trim((string) $this->fields->cover));
} else {
  $pwCover = pw_first_image_from_html((string) $this->content);
}
?>
<main id="pw-main" class="pw-main pw-main-inner">
  <article class="pw-post" itemscope itemtype="https://schema.org/BlogPosting">
    <header class="pw-post-header">
      <h1 class="pw-post-title" itemprop="headline"><?php echo pw_e($pwPostTitle); ?></h1>
      <div class="pw-post-meta">
        <time datetime="<?php echo pw_e(date('c', $this->created)); ?>" itemprop="datePublished"><?php echo pw_e(date('Y-m-d', $this->created)); ?></time>
        <span class="pw-post-taxonomy"><?php $this->category('、'); ?></span>
        <?php if ($this->tags): ?><span class="pw-post-taxonomy"><?php $this->tags('、', true); ?></span><?php endif; ?>
      </div>
    </header>

    <?php if ($pwCover !== ''): ?>
    <div class="pw-post-hero">
      <img src="<?php echo pw_e($pwCover); ?>" alt="<?php echo pw_e($pwPostTitle); ?>" decoding="async" fetchpriority="high">
    </div>
    <?php endif; ?>

    <div class="pw-post-content" itemprop="articleBody">
      <?php $this->content(); ?>
    </div>

    <nav class="pw-post-adjacent" aria-label="文章导航">
      <span class="pw-post-prev"><?php $this->thePrev('上一篇：%s', '没有更多了'); ?></span>
      <span class="pw-post-next"><?php $this->theNext('下一篇：%s', '没有更多了'); ?></span>
    </nav>

    <?php $this->need('comments.php'); ?>
  </article>
</main>
<?php $this->need('footer.php'); ?>
