<?php
/**
 * PhotoWall AJAX 接口：/?pw-api=1&category=&tag=&page=&limit=&orderby=
 *
 * @package PhotoWall
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
  exit;
}

/**
 * 拦截照片墙 API 请求；非 API 请求直接返回。
 */
function pw_maybe_handle_api()
{
  if (!isset($_GET['pw-api'])) {
    return;
  }

  $opts = pw_theme_opts();

  header('Content-Type: application/json; charset=utf-8');
  header('X-Content-Type-Options: nosniff');
  header('Cache-Control: no-cache');

  if (pw_yn($opts, 'apiEnabled', 1) !== 1) {
    echo pw_json_payload(array('code' => 403, 'message' => 'api disabled', 'data' => null));
    exit;
  }

  $category = isset($_GET['category']) ? (string) $_GET['category'] : '';
  $tag = isset($_GET['tag']) ? (string) $_GET['tag'] : '';
  $slugPattern = '/^[A-Za-z0-9_-]{1,128}$/';
  if ($category !== '' && !preg_match($slugPattern, $category)) {
    echo pw_json_payload(array('code' => 400, 'message' => 'invalid category', 'data' => null));
    exit;
  }
  if ($tag !== '' && !preg_match($slugPattern, $tag)) {
    echo pw_json_payload(array('code' => 400, 'message' => 'invalid tag', 'data' => null));
    exit;
  }

  $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
  $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
  $page = max(1, min(1000, $page));
  $limit = max(1, min(100, $limit));
  $orderby = isset($_GET['orderby']) ? (string) $_GET['orderby'] : 'date';
  if ($orderby !== 'date' && $orderby !== 'random') {
    $orderby = 'date';
  }

  $payload = pw_build_wall_payload($opts);
  $items = $payload['items'];

  if ($category !== '') {
    $filtered = array();
    foreach ($items as $item) {
      if (in_array($category, $item['categorySlugs'], true)) {
        $filtered[] = $item;
      }
    }
    $items = $filtered;
  }

  if ($tag !== '') {
    $filtered = array();
    foreach ($items as $item) {
      if (in_array($tag, $item['tagSlugs'], true)) {
        $filtered[] = $item;
      }
    }
    $items = $filtered;
  }

  if ($orderby === 'random') {
    shuffle($items);
  }

  $total = count($items);
  $offset = ($page - 1) * $limit;
  $slice = array_slice($items, $offset, $limit);

  $apiItems = array();
  foreach ($slice as $item) {
    $apiItems[] = array(
      'id' => (int) $item['id'],
      'title' => (string) $item['title'],
      'url' => (string) $item['url'],
      'thumb' => (string) $item['thumb'],
      'alt' => (string) $item['alt'],
      'description' => (string) $item['description'],
      'permalink' => (string) $item['permalink'],
      'category' => $item['categories'],
      'tags' => $item['tags'],
      'date' => (string) $item['date'],
    );
  }

  echo pw_json_payload(array(
    'code' => 200,
    'message' => 'ok',
    'data' => array(
      'items' => $apiItems,
      'total' => $total,
      'page' => $page,
      'limit' => $limit,
    ),
  ));
  exit;
}
