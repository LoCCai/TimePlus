<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
  exit;
}

$pwOpts = pw_theme_opts();
$pwAssets = rtrim((string) $this->options->themeUrl, '/');

$pwIsIndex = $this->is('index');
$pwSeoTitle = trim((string) pw_opt($pwOpts, 'seoTitle', ''));
$pwSeoDesc = trim((string) pw_opt($pwOpts, 'seoDescription', ''));
if ($pwSeoDesc === '') {
  $pwSeoDesc = trim((string) $this->options->description);
}

$pwCanonical = '';
if ($pwIsIndex) {
  $pwCanonical = rtrim((string) $this->options->siteUrl, '/') . '/';
} elseif ($this->is('post') || $this->is('page')) {
  $pwCanonical = trim((string) $this->permalink);
}

$pwOgImage = trim((string) pw_opt($pwOpts, 'ogImage', ''));
if ($pwOgImage === '' && $pwIsIndex) {
  $pwOgImage = pw_first_photo_url($pwOpts);
}

$pwBgColor = trim((string) pw_opt($pwOpts, 'backgroundColor', '#0e1014'));
$pwGap = max(0, min(64, (int) pw_opt($pwOpts, 'gap', 12)));
$pwRadius = max(0, min(48, (int) pw_opt($pwOpts, 'radius', 12)));
?><!DOCTYPE html>
<html lang="zh-CN" data-pw-theme="<?php echo pw_e(pw_opt($pwOpts, 'colorScheme', 'auto')); ?>">
<head>
<meta charset="<?php $this->options->charset(); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="<?php echo pw_e($pwBgColor); ?>">
<?php if ($pwIsIndex && $pwSeoTitle !== ''): ?>
<title><?php echo pw_e($pwSeoTitle); ?></title>
<?php else: ?>
<title><?php $this->archiveTitle('', '', ' - '); ?><?php $this->options->title(); ?></title>
<?php endif; ?>
<meta name="description" content="<?php echo pw_e($pwSeoDesc); ?>">
<?php if ($pwCanonical !== ''): ?>
<link rel="canonical" href="<?php echo pw_e($pwCanonical); ?>">
<?php endif; ?>
<meta property="og:site_name" content="<?php $this->options->title(); ?>">
<meta property="og:title" content="<?php echo pw_e($pwIsIndex && $pwSeoTitle !== '' ? $pwSeoTitle : (string) $this->options->title); ?>">
<meta property="og:description" content="<?php echo pw_e($pwSeoDesc); ?>">
<meta property="og:type" content="<?php echo $pwIsIndex ? 'website' : 'article'; ?>">
<?php if ($pwCanonical !== ''): ?>
<meta property="og:url" content="<?php echo pw_e($pwCanonical); ?>">
<?php endif; ?>
<?php if ($pwOgImage !== ''): ?>
<meta property="og:image" content="<?php echo pw_e($pwOgImage); ?>">
<?php endif; ?>
<link rel="icon" href="<?php echo pw_e($pwAssets); ?>/assets/img/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="<?php echo pw_e($pwAssets); ?>/assets/css/main.css?v=1.0.0">
<link rel="stylesheet" href="<?php echo pw_e($pwAssets); ?>/assets/css/photowall.css?v=1.0.0">
<link rel="stylesheet" href="<?php echo pw_e($pwAssets); ?>/assets/css/lightbox.css?v=1.0.0">
<style>
:root{--pw-bg:<?php echo pw_e($pwBgColor); ?>;--pw-gap:<?php echo (int) $pwGap; ?>px;--pw-card-radius:<?php echo (int) $pwRadius; ?>px;}
</style>
<?php if (trim((string) pw_opt($pwOpts, 'customCss', '')) !== ''): ?>
<style><?php echo trim((string) $pwOpts->customCss); ?></style>
<?php endif; ?>
<?php if (trim((string) pw_opt($pwOpts, 'customHead', '')) !== ''): ?>
<?php echo trim((string) $pwOpts->customHead); ?>
<?php endif; ?>
<script>(function(){try{var m=localStorage.getItem('pw-theme');if(m==='light'||m==='dark'){document.documentElement.setAttribute('data-pw-theme',m);}}catch(e){}})();</script>
<?php $this->header('description=&keywords='); ?>
</head>
<body class="pw-body<?php echo $pwIsIndex ? ' pw-page-index' : ' pw-page-inner'; ?>">
<a class="pw-skip-link" href="#pw-main">跳到主要内容</a>
<header class="pw-header">
  <div class="pw-header-inner">
    <a class="pw-brand" href="<?php $this->options->siteUrl(); ?>">
      <?php if (trim((string) pw_opt($pwOpts, 'logoUrl', '')) !== ''): ?>
      <img class="pw-logo" src="<?php echo pw_e(trim((string) $pwOpts->logoUrl)); ?>" alt="" width="32" height="32">
      <?php endif; ?>
      <span class="pw-site-title"><?php $this->options->title(); ?></span>
    </a>
    <nav class="pw-nav" aria-label="站点导航">
      <a href="<?php $this->options->siteUrl(); ?>"<?php echo $pwIsIndex ? ' aria-current="page"' : ''; ?>>首页</a>
      <?php
      $pwPages = pw_widget('Widget_Contents_Page_List@photowall');
      if ($pwPages !== null) {
        while ($pwPages->next()) {
      ?>
      <a href="<?php $pwPages->permalink(); ?>"><?php $pwPages->title(); ?></a>
      <?php
        }
      }
      ?>
    </nav>
    <div class="pw-header-tools">
      <button type="button" class="pw-theme-toggle" aria-label="切换配色模式（自动 / 浅色 / 深色）" title="切换配色">
        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M12 18a6 6 0 1 1 0-12 6 6 0 0 1 0 12zm0-2a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM11 1h2v3h-2V1zm0 19h2v3h-2v-3zM3.5 4.9 4.9 3.5l2.1 2.1-1.4 1.4-2.1-2.1zm13.5 13.5 1.4-1.4 2.1 2.1-1.4 1.4-2.1-2.1zM1 11h3v2H1v-2zm19 0h3v2h-3v-2zM4.9 20.5l-1.4-1.4 2.1-2.1 1.4 1.4-2.1 2.1zM18.4 5.6l-1.4-1.4 2.1-2.1 1.4 1.4-2.1 2.1z"/></svg>
      </button>
    </div>
  </div>
</header>
