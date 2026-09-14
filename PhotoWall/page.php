<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
  exit;
}

$this->need('header.php');
?>
<main id="pw-main" class="pw-main pw-main-inner">
  <article class="pw-post pw-page" itemscope itemtype="https://schema.org/WebPage">
    <header class="pw-post-header">
      <h1 class="pw-post-title" itemprop="headline"><?php $this->title(); ?></h1>
    </header>
    <div class="pw-post-content" itemprop="mainEntityOfPage">
      <?php $this->content(); ?>
    </div>
    <?php $this->need('comments.php'); ?>
  </article>
</main>
<?php $this->need('footer.php'); ?>
