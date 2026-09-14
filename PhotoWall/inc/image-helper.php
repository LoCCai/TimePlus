<?php
/**
 * PhotoWall 数据层：选项读取、图片提取、照片池构建、筛选按钮与结构化数据。
 *
 * @package PhotoWall
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
  exit;
}

/**
 * HTML 转义（同时用于属性输出）。
 */
function pw_e($value)
{
  return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * 兼容 Typecho 1.2 / 1.3 的全局选项读取。
 */
function pw_options()
{
  if (class_exists('Helper', false)) {
    return Helper::options();
  }
  $widget = pw_widget('Widget_Options');
  return $widget;
}

/**
 * 兼容 Typecho 1.2 / 1.3 的数据库句柄；失败返回 null。
 */
function pw_db()
{
  try {
    if (class_exists('Typecho_Db')) {
      return Typecho_Db::get();
    }
    if (class_exists('Typecho\\Db')) {
      return Typecho\Db::get();
    }
  } catch (Exception $e) {
    return null;
  }
  return null;
}

/**
 * 兼容 Typecho 1.2 / 1.3 的 Widget 工厂；失败返回 null。
 */
function pw_widget($alias, $params = '')
{
  try {
    if (class_exists('Typecho_Widget')) {
      return Typecho_Widget::widget($alias, $params);
    }
    if (class_exists('Typecho\\Widget')) {
      return Typecho\Widget::widget($alias, $params);
    }
  } catch (Exception $e) {
    return null;
  }
  return null;
}

/**
 * 带默认值的选项集合。
 */
final class PW_Opts
{
  private $data = array();

  public function __construct(array $data)
  {
    $this->data = $data;
  }

  public function __isset($name)
  {
    $value = $this->raw($name);
    return $value !== null && $value !== '';
  }

  public function __get($name)
  {
    return $this->get($name, null);
  }

  public function get($name, $default = null)
  {
    $defaults = pw_defaults();
    if ($default === null) {
      $default = isset($defaults[$name]) ? $defaults[$name] : '';
    }
    $value = $this->raw($name);
    if ($value === null || $value === '' || $value === false) {
      return $default;
    }
    return $value;
  }

  public function raw($name)
  {
    return array_key_exists($name, $this->data) ? $this->data[$name] : null;
  }

  public function toArray()
  {
    return $this->data;
  }
}

/**
 * 读取主题设置（含默认值合并，单次请求内共享）。
 */
function pw_theme_opts()
{
  static $opts = null;
  if ($opts !== null) {
    return $opts;
  }

  $stored = array();
  $options = pw_options();
  if ($options !== null && isset($options->themeConfig)) {
    $config = $options->themeConfig;
    if (is_object($config) && method_exists($config, 'toArray')) {
      $stored = $config->toArray();
    } elseif (is_array($config)) {
      $stored = $config;
    }
  }

  $opts = new PW_Opts(array_merge(pw_defaults(), $stored));
  return $opts;
}

/**
 * 读取单个设置项。
 */
function pw_opt($opts, $name, $default = null)
{
  return $opts->get($name, $default);
}

/**
 * 读取开关型设置项，返回 1 / 0。
 */
function pw_yn($opts, $name, $default = 1)
{
  $value = (string) $opts->get($name, $default === 1 ? '1' : '0');
  return $value === '1' ? 1 : 0;
}

/**
 * 比例设置转 CSS aspect-ratio 值；natural 或非法值返回空字符串。
 */
function pw_ratio_css($ratio)
{
  $map = array(
    '1:1' => '1 / 1',
    '4:3' => '4 / 3',
    '3:2' => '3 / 2',
    '16:9' => '16 / 9',
  );
  $ratio = (string) $ratio;
  return isset($map[$ratio]) ? $map[$ratio] : '';
}

/**
 * 相对地址转绝对地址。
 */
function pw_abs_url($url)
{
  $url = trim((string) $url);
  if ($url === '') {
    return '';
  }
  if (preg_match('#^([a-z][a-z0-9+\-.]*:|//)#i', $url)) {
    return $url;
  }
  if (strpos($url, '/') === 0) {
    return rtrim((string) pw_options()->siteUrl, '/') . $url;
  }
  return $url;
}

/**
 * 应用 CDN 替换。
 */
function pw_cdn_url($url, $opts)
{
  $cdn = trim((string) $opts->get('cdnUrl', ''));
  if ($cdn === '') {
    return $url;
  }
  $siteBase = rtrim((string) pw_options()->siteUrl, '/');
  if (strpos($url, $siteBase) === 0) {
    return rtrim($cdn, '/') . substr($url, strlen($siteBase));
  }
  return $url;
}

/**
 * 安全读取文章自定义字段。
 */
function pw_field($fields, $name)
{
  if ($fields === null) {
    return '';
  }
  try {
    if (isset($fields->$name)) {
      return trim((string) $fields->$name);
    }
  } catch (Exception $e) {
    return '';
  }
  return '';
}

/**
 * 从 HTML 中提取第一张图片地址。
 */
function pw_first_image_from_html($html)
{
  $html = (string) $html;
  if ($html === '') {
    return '';
  }
  if (preg_match('#<img[^>]+src=["\']([^"\']+)["\']#i', $html, $matches)) {
    return trim($matches[1]);
  }
  return '';
}

/**
 * 纯文本摘要。
 */
function pw_excerpt($html, $length = 120)
{
  $text = trim((string) $html);
  if ($text === '') {
    return '';
  }
  $text = strip_tags($text);
  $text = preg_replace('/\s+/u', ' ', $text);
  $text = trim($text);
  if ($text === '') {
    return '';
  }
  if (function_exists('mb_substr')) {
    return mb_substr($text, 0, (int) $length, 'UTF-8');
  }
  return substr($text, 0, (int) $length * 3);
}

/**
 * 照片 alt 文本。
 */
function pw_alt($title, $description, $rule)
{
  if ($rule === 'title-desc' && trim((string) $description) !== '') {
    return trim($title . '，' . $description);
  }
  return $title;
}

/**
 * 读取文章附件中的图片 URL（直接查询，作为回退来源）。
 */
function pw_attachment_urls($cid, $limit = 3)
{
  $urls = array();
  $db = pw_db();
  if ($db === null || (int) $cid <= 0 || (int) $limit <= 0) {
    return $urls;
  }

  try {
    $sortDesc = 'DESC';
    if (defined('Typecho_Db::SORT_DESC')) {
      $sortDesc = Typecho_Db::SORT_DESC;
    } elseif (defined('Typecho\\Db::SORT_DESC')) {
      $sortDesc = Typecho\Db::SORT_DESC;
    }
    $rows = $db->fetchAll($db->select('text')
      ->from('table.contents')
      ->where('type = ? AND parent = ?', 'attachment', (int) $cid)
      ->order('created', $sortDesc)
      ->limit((int) $limit));
  } catch (Exception $e) {
    return $urls;
  }

  if (!is_array($rows)) {
    return $urls;
  }

  foreach ($rows as $row) {
    $meta = @unserialize((string) $row['text']);
    if (!is_array($meta) || empty($meta['path'])) {
      continue;
    }
    $path = (string) $meta['path'];
    $mime = isset($meta['mime']) ? (string) $meta['mime'] : '';
    if ($mime !== '' && !preg_match('#^image/#i', $mime)) {
      continue;
    }
    if ($mime === '' && !preg_match('#\.(jpe?g|png|gif|webp|avif|bmp|svg)$#i', $path)) {
      continue;
    }
    $urls[] = pw_abs_url($path);
    if (count($urls) >= (int) $limit) {
      break;
    }
  }

  return $urls;
}

/**
 * 内容表指纹：文章数量 + 最新创建时间，用于缓存失效。
 */
function pw_stats_fingerprint()
{
  $db = pw_db();
  if ($db !== null) {
    try {
      $row = $db->fetchRow($db->select('COUNT(cid) AS c', 'MAX(created) AS m')
        ->from('table.contents')
        ->where('type = ? AND status = ?', 'post', 'publish'));
      if (is_array($row) && isset($row['c'])) {
        return (int) $row['c'] . '-' . (isset($row['m']) ? (int) $row['m'] : 0);
      }
    } catch (Exception $e) {
      // 落回时间桶。
    }
  }
  return 't-' . floor(time() / 300);
}

/**
 * 构建照片条目列表。
 */
function pw_build_items($opts, $limit)
{
  $items = array();
  $perPost = max(1, min(10, (int) $opts->get('imagesPerPost', 1)));
  $altRule = (string) $opts->get('altRule', 'title');
  $themeAssets = rtrim((string) pw_options()->themeUrl, '/');
  $placeholder = $themeAssets . '/assets/img/placeholder.svg';

  $posts = pw_widget('Widget_Contents_Post_Recent@photowall', 'pageSize=' . (int) $limit);
  if ($posts === null) {
    return $items;
  }

  while ($posts->next()) {
    $fields = $posts->fields;
    if (pw_field($fields, 'hide') === '1') {
      continue;
    }

    $cid = (int) $posts->cid;
    $title = trim((string) $posts->title);
    $content = (string) $posts->content;
    $permalink = trim((string) $posts->permalink);

    $categories = array();
    $categorySlugs = array();
    $rawCategories = $posts->categories;
    if (is_array($rawCategories)) {
      foreach ($rawCategories as $category) {
        if (!is_array($category) || !isset($category['slug'])) {
          continue;
        }
        $categories[] = (string) $category['name'];
        $categorySlugs[] = (string) $category['slug'];
      }
    }

    $tags = array();
    $tagSlugs = array();
    $rawTags = $posts->tags;
    if (is_array($rawTags)) {
      foreach ($rawTags as $tag) {
        if (!is_array($tag) || !isset($tag['slug'])) {
          continue;
        }
        $tags[] = (string) $tag['name'];
        $tagSlugs[] = (string) $tag['slug'];
      }
    }

    $images = array();
    $isPlaceholder = false;

    $cover = pw_cdn_url(pw_abs_url(pw_field($fields, 'cover')), $opts);
    if ($cover !== '') {
      $images[] = $cover;
    }

    if (count($images) < $perPost) {
      $photosRaw = pw_field($fields, 'photos');
      if ($photosRaw !== '') {
        $photoLines = preg_split('/\r\n|\r|\n/', $photosRaw);
        if (is_array($photoLines)) {
          foreach ($photoLines as $line) {
            if (count($images) >= $perPost) {
              break;
            }
            $line = trim($line);
            if ($line === '') {
              continue;
            }
            $imageUrl = pw_cdn_url(pw_abs_url($line), $opts);
            if ($imageUrl !== '' && !in_array($imageUrl, $images, true)) {
              $images[] = $imageUrl;
            }
          }
        }
      }
    }

    if (count($images) === 0) {
      $attachmentUrls = pw_attachment_urls($cid, $perPost);
      foreach ($attachmentUrls as $attachmentUrl) {
        if ($attachmentUrl !== '' && !in_array($attachmentUrl, $images, true)) {
          $images[] = $attachmentUrl;
        }
      }
    }

    if (count($images) === 0) {
      $firstImage = pw_cdn_url(pw_abs_url(pw_first_image_from_html($content)), $opts);
      if ($firstImage !== '') {
        $images[] = $firstImage;
      }
    }

    if (count($images) === 0) {
      $images[] = $placeholder;
      $isPlaceholder = true;
    }

    $thumbField = pw_cdn_url(pw_abs_url(pw_field($fields, 'thumb')), $opts);
    $description = pw_field($fields, 'description');
    if ($description === '') {
      $description = pw_excerpt($content, 140);
    }
    $ratio = pw_ratio_css(pw_field($fields, 'ratio'));
    $alt = pw_alt($title, $description, $altRule);
    $date = date('Y-m-d', (int) $posts->created);

    foreach ($images as $imageUrl) {
      $items[] = array(
        'id' => $cid,
        'title' => $title,
        'url' => $imageUrl,
        'thumb' => $thumbField !== '' ? $thumbField : $imageUrl,
        'alt' => $alt,
        'description' => $description,
        'permalink' => $permalink,
        'categories' => $categories,
        'categorySlugs' => $categorySlugs,
        'tags' => $tags,
        'tagSlugs' => $tagSlugs,
        'date' => $date,
        'ratio' => $ratio,
        'placeholder' => $isPlaceholder,
      );
    }
  }

  return $items;
}

/**
 * 构建完整照片墙数据（照片池 + 分类/标签统计），带缓存。
 */
function pw_build_wall_payload($opts)
{
  static $payload = null;
  if ($payload !== null) {
    return $payload;
  }

  $limit = max(1, min(500, (int) $opts->get('postsLimit', 60)));
  $fingerprint = pw_stats_fingerprint();
  $cacheKey = 'pw-wall-v1|' . $fingerprint . '|' . $limit . '|' . md5(serialize(array(
    'cdn' => (string) $opts->get('cdnUrl', ''),
    'perPost' => (string) $opts->get('imagesPerPost', 1),
    'alt' => (string) $opts->get('altRule', 'title'),
  )));

  $useCache = pw_yn($opts, 'cacheEnabled', 1) === 1;
  if ($useCache) {
    $cached = pw_cache_get($cacheKey);
    if (is_array($cached) && isset($cached['items']) && is_array($cached['items'])) {
      $payload = $cached;
      return $payload;
    }
  }

  $items = pw_build_items($opts, $limit);

  $categoryCounts = array();
  $categoryNames = array();
  $tagCounts = array();
  $tagNames = array();
  foreach ($items as $item) {
    foreach ($item['categorySlugs'] as $i => $slug) {
      $slug = (string) $slug;
      if (!isset($categoryCounts[$slug])) {
        $categoryCounts[$slug] = 0;
        $categoryNames[$slug] = isset($item['categories'][$i]) ? (string) $item['categories'][$i] : $slug;
      }
      $categoryCounts[$slug]++;
    }
    foreach ($item['tagSlugs'] as $i => $slug) {
      $slug = (string) $slug;
      if (!isset($tagCounts[$slug])) {
        $tagCounts[$slug] = 0;
        $tagNames[$slug] = isset($item['tags'][$i]) ? (string) $item['tags'][$i] : $slug;
      }
      $tagCounts[$slug]++;
    }
  }

  $categories = array();
  foreach ($categoryCounts as $slug => $count) {
    $categories[] = array('slug' => (string) $slug, 'name' => $categoryNames[$slug], 'count' => $count);
  }
  usort($categories, 'pw_sort_metas_by_count');

  $tags = array();
  foreach ($tagCounts as $slug => $count) {
    $tags[] = array('slug' => (string) $slug, 'name' => $tagNames[$slug], 'count' => $count);
  }
  usort($tags, 'pw_sort_metas_by_count');

  $payload = array(
    'items' => $items,
    'categories' => $categories,
    'tags' => $tags,
  );

  if ($useCache) {
    pw_cache_set($cacheKey, $payload, (int) $opts->get('cacheTtl', 600));
  }

  return $payload;
}

/**
 * 分类/标签按照片数倒序、名称升序。
 */
function pw_sort_metas_by_count($a, $b)
{
  if ($a['count'] === $b['count']) {
    return strcmp($a['name'], $b['name']);
  }
  return $b['count'] - $a['count'];
}

/**
 * 第一张照片地址（OG 图回退用）。
 */
function pw_first_photo_url($opts)
{
  $payload = pw_build_wall_payload($opts);
  if (isset($payload['items'][0])) {
    return (string) $payload['items'][0]['thumb'];
  }
  return '';
}

/**
 * 渲染筛选按钮（服务端输出，JS 仅负责交互）。
 */
function pw_render_filter_buttons($wall, $opts, $withAll = true)
{
  $showCategories = pw_yn($opts, 'showCategoryFilter', 1) === 1 && count($wall['categories']) > 0;
  $showTags = pw_yn($opts, 'showTagFilter', 1) === 1 && count($wall['tags']) > 0;
  if (!$showCategories && !$showTags) {
    return '';
  }

  $html = '';

  if ($withAll && pw_yn($opts, 'showAllButton', 1) === 1) {
    $html .= '<div class="pw-filter-group pw-filter-group-all" role="group" aria-label="全部照片">';
    $html .= '<button type="button" class="pw-filter-btn pw-filter-all" data-type="all" data-slug="" aria-pressed="true">全部<i class="pw-filter-count"></i></button>';
    $html .= '</div>';
  }

  if ($showCategories) {
    $maxCategories = max(0, min(50, (int) $opts->get('maxCategories', 12)));
    $html .= '<div class="pw-filter-group" role="group" aria-label="按分类筛选">';
    $html .= '<span class="pw-filter-label">分类</span>';
    foreach ($wall['categories'] as $i => $category) {
      if ($maxCategories > 0 && $i >= $maxCategories) {
        break;
      }
      $html .= '<button type="button" class="pw-filter-btn" data-type="category" data-slug="' . pw_e($category['slug']) . '" aria-pressed="false">'
        . pw_e($category['name']) . '<i class="pw-filter-count">' . (int) $category['count'] . '</i></button>';
    }
    $html .= '</div>';
  }

  if ($showTags) {
    $maxTags = max(0, min(50, (int) $opts->get('maxTags', 12)));
    $html .= '<div class="pw-filter-group" role="group" aria-label="按标签筛选">';
    $html .= '<span class="pw-filter-label">标签</span>';
    foreach ($wall['tags'] as $i => $tag) {
      if ($maxTags > 0 && $i >= $maxTags) {
        break;
      }
      $html .= '<button type="button" class="pw-filter-btn" data-type="tag" data-slug="' . pw_e($tag['slug']) . '" aria-pressed="false">'
        . pw_e($tag['name']) . '<i class="pw-filter-count">' . (int) $tag['count'] . '</i></button>';
    }
    $html .= '</div>';
  }

  return $html;
}

/**
 * 安全的内嵌 JSON 输出。
 */
function pw_json_payload($data)
{
  $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
  if ($json === false) {
    $json = '[]';
  }
  return $json;
}

/**
 * 首页 ImageGallery 结构化数据。
 */
function pw_json_ld($items, $opts)
{
  $images = array();
  $count = 0;
  foreach ($items as $item) {
    if ($count >= 30) {
      break;
    }
    $images[] = array(
      '@type' => 'ImageObject',
      'contentUrl' => (string) $item['url'],
      'thumbnailUrl' => (string) $item['thumb'],
      'name' => (string) $item['title'],
      'description' => (string) $item['description'],
    );
    $count++;
  }

  $options = pw_options();
  return array(
    '@context' => 'https://schema.org',
    '@type' => 'ImageGallery',
    'name' => (string) $options->title,
    'description' => (string) $options->description,
    'image' => $images,
  );
}
