<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
  exit;
}

$timeplusSiteName = trim((string) $this->options->IndexName);
$timeplusSuffix = trim((string) $this->options->Indexdict);
$timeplusPageTitle = $timeplusSiteName;
if (!$this->is('index')) {
  $timeplusEntryTitle = trim((string) $this->title);
  if ($timeplusEntryTitle !== '') {
    $timeplusPageTitle = $timeplusEntryTitle . ' - ' . $timeplusSiteName;
  }
} elseif ($timeplusSuffix !== '') {
  $timeplusPageTitle .= ' - ' . $timeplusSuffix;
}

$timeplusSiteUrl = timeplus_safe_url((string) $this->options->siteUrl, true) ?: '/';
$timeplusIconUrl = timeplus_safe_url((string) $this->options->IconUrl, true);
$timeplusAppleIcon = timeplus_safe_url((string) $this->options->AppleIcon, true);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="<?php echo timeplus_escape_attr((string) $this->options->charset); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo timeplus_escape_html($timeplusPageTitle); ?></title>
  <meta name="keywords" content="<?php echo timeplus_escape_attr((string) $this->options->keywords); ?>">
  <meta name="description" content="<?php echo timeplus_escape_attr((string) $this->options->description); ?>">
  <?php if ($timeplusAppleIcon !== null): ?>
    <link rel="apple-touch-icon" href="<?php echo timeplus_escape_attr($timeplusAppleIcon); ?>">
    <link rel="apple-touch-icon-precomposed" sizes="180x180" href="<?php echo timeplus_escape_attr($timeplusAppleIcon); ?>">
  <?php endif; ?>
  <meta name="apple-mobile-web-app-title" content="<?php echo timeplus_escape_attr($timeplusSiteName); ?>">
  <?php if ($timeplusIconUrl !== null): ?>
    <link rel="icon" href="<?php echo timeplus_escape_attr($timeplusIconUrl); ?>">
  <?php endif; ?>
  <link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/main.css'); ?>">
  <link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/timeplus-enhancements.css'); ?>">
  <noscript><link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/noscript.css'); ?>"></noscript>
  <?php $this->header(); ?>
</head>
<body class="is-preload">
  <header id="header">
    <a class="site-brand" href="<?php echo timeplus_escape_attr($timeplusSiteUrl); ?>">
      <?php if ($timeplusIconUrl !== null): ?>
        <img class="site-logo" src="<?php echo timeplus_escape_attr($timeplusIconUrl); ?>" alt="">
      <?php endif; ?>
      <span><strong><?php echo timeplus_escape_html((string) $this->options->zmkiabout); ?></strong></span>
    </a>
    <span class="discription"><?php echo timeplus_escape_html((string) $this->options->zmkiabouts); ?></span>
    <nav aria-label="站点操作">
      <ul class="nav_links">
        <li>
          <button class="header-action-button category-panel-trigger" id="open-category-panel" type="button" aria-haspopup="dialog" aria-controls="category-panel" aria-expanded="false">分类</button>
        </li>
        <li>
          <button class="header-action-button" id="fullscreen" type="button" aria-pressed="false">
            <?php echo timeplus_icon('fullscreen'); ?><span data-fullscreen-label>全屏</span>
          </button>
        </li>
        <li><button class="header-action-button" type="button" data-panel-target="footer" aria-expanded="false">关于</button></li>
      </ul>
    </nav>
  </header>
  <div id="wrapper">
