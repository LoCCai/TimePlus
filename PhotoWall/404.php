<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
  exit;
}

$this->need('header.php');
?>
<main id="pw-main" class="pw-main pw-main-inner">
  <div class="pw-404">
    <p class="pw-404-code" aria-hidden="true">404</p>
    <h1 class="pw-404-title">页面走丢了</h1>
    <p class="pw-404-desc">你访问的页面不存在或已被移动，去照片墙看看别的照片吧。</p>
    <p><a class="pw-btn" href="<?php $this->options->siteUrl(); ?>">返回首页</a></p>
  </div>
</main>
<?php $this->need('footer.php'); ?>
