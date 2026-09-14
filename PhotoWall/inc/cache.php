<?php
/**
 * PhotoWall 照片池文件缓存。
 *
 * @package PhotoWall
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
  exit;
}

/**
 * 缓存目录：优先主题内 cache/，不可写时回退系统临时目录。
 */
function pw_cache_dir()
{
  static $dir = null;
  if ($dir !== null) {
    return $dir;
  }

  $themeDir = dirname(__DIR__) . '/cache';
  if (!is_dir($themeDir)) {
    @mkdir($themeDir, 0755, true);
  }
  if (is_dir($themeDir) && is_writable($themeDir)) {
    $dir = $themeDir;
    return $dir;
  }

  $tmpDir = rtrim(sys_get_temp_dir(), '/\\') . '/photowall-cache';
  if (!is_dir($tmpDir)) {
    @mkdir($tmpDir, 0755, true);
  }
  $dir = is_dir($tmpDir) && is_writable($tmpDir) ? $tmpDir : '';
  return $dir;
}

/**
 * 读取缓存，未命中或已过期返回 null。
 */
function pw_cache_get($key)
{
  $dir = pw_cache_dir();
  if ($dir === '') {
    return null;
  }

  $file = $dir . '/' . md5((string) $key) . '.json';
  if (!is_file($file) || !is_readable($file)) {
    return null;
  }

  $raw = @file_get_contents($file);
  if ($raw === false || $raw === '') {
    return null;
  }

  $data = json_decode($raw, true);
  if (!is_array($data) || !isset($data['expires']) || !isset($data['payload'])) {
    return null;
  }

  if ((int) $data['expires'] !== 0 && time() > (int) $data['expires']) {
    return null;
  }

  return $data['payload'];
}

/**
 * 写入缓存。
 */
function pw_cache_set($key, $payload, $ttl)
{
  $dir = pw_cache_dir();
  if ($dir === '') {
    return false;
  }

  $ttl = max(0, (int) $ttl);
  $data = array(
    'expires' => $ttl === 0 ? 0 : time() + $ttl,
    'payload' => $payload,
  );

  $file = $dir . '/' . md5((string) $key) . '.json';
  $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  if ($json === false) {
    return false;
  }

  $ok = @file_put_contents($file, $json, LOCK_EX);
  return $ok !== false;
}

/**
 * 清空全部照片池缓存。
 */
function pw_cache_flush()
{
  $dir = pw_cache_dir();
  if ($dir === '' || !is_dir($dir)) {
    return;
  }

  $files = glob($dir . '/*.json');
  if (is_array($files)) {
    foreach ($files as $file) {
      @unlink($file);
    }
  }
}
