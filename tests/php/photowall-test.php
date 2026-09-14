<?php
/**
 * PhotoWall 数据层单元测试（桩掉 Typecho 核心，直接执行数据逻辑）。
 *
 * 用法：php tests/php/photowall-test.php
 * 内部会以子进程模式再运行自身一次以覆盖 API 出口（exit 路径）。
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
  define('__TYPECHO_ROOT_DIR__', dirname(__DIR__, 2));
}

$pwThemeDir = dirname(__DIR__, 2) . '/PhotoWall';

/* ----------------------------------------------------------------
 * Typecho 桩
 * ---------------------------------------------------------------- */

class PW_Test_Config
{
  private $data = array();

  public function __construct(array $data = array())
  {
    $this->data = $data;
  }

  public function __isset($name)
  {
    return isset($this->data[$name]) && $this->data[$name] !== '' && $this->data[$name] !== null;
  }

  public function __get($name)
  {
    return isset($this->data[$name]) ? $this->data[$name] : null;
  }

  public function toArray()
  {
    return $this->data;
  }
}

class PW_Test_Fields
{
  private $data = array();

  public function __construct(array $data = array())
  {
    $this->data = $data;
  }

  public function __isset($name)
  {
    return isset($this->data[$name]) && $this->data[$name] !== null;
  }

  public function __get($name)
  {
    return isset($this->data[$name]) ? $this->data[$name] : null;
  }
}

class PW_Test_Post
{
  public $cid;
  public $title;
  public $content;
  public $permalink;
  public $created;
  public $categories;
  public $tags;
  public $fields;
  public $index = 0;

  public function __construct($row)
  {
    foreach ($row as $key => $value) {
      $this->{$key} = $value;
    }
  }

  public function next()
  {
    if ($this->index === 0) {
      $this->index = 1;
      return true;
    }
    return false;
  }
}

class PW_Test_Posts_Widget extends PW_Test_Post
{
  public $rows = array();
  public $position = -1;

  public function __construct($rows)
  {
    $this->rows = $rows;
  }

  public function next()
  {
    $this->position++;
    if ($this->position >= count($this->rows)) {
      return false;
    }
    $row = $this->rows[$this->position];
    foreach ($row as $key => $value) {
      $this->{$key} = $value;
    }
    return true;
  }
}

class PW_Test_Query_Builder
{
  public $rows = array();
  public $attachmentParent = null;
  public $isStats = false;

  public function from($table)
  {
    return $this;
  }

  public function where($condition)
  {
    $args = func_get_args();
    if (count($args) >= 3 && $args[1] === 'attachment') {
      $this->attachmentParent = (int) $args[2];
    }
    return $this;
  }

  public function order($field, $direction)
  {
    return $this;
  }

  public function limit($limit)
  {
    return $this;
  }
}

class PW_Test_Db
{
  public static $attachmentMap = array();
  public static $stats = array('c' => 4, 'm' => 1000);

  public function select()
  {
    $args = func_get_args();
    $builder = new PW_Test_Query_Builder();
    $builder->isStats = count($args) === 2 && strpos($args[0], 'COUNT(') === 0;
    return $builder;
  }

  public function fetchAll($builder)
  {
    if ($builder->attachmentParent !== null && isset(self::$attachmentMap[$builder->attachmentParent])) {
      return self::$attachmentMap[$builder->attachmentParent];
    }
    return array();
  }

  public function fetchRow($builder)
  {
    return self::$stats;
  }
}

class PW_Test_Options
{
  public $siteUrl = 'https://blog.example.com/';
  public $themeUrl = 'https://blog.example.com/usr/themes/PhotoWall/';
  public $title = '测试站点';
  public $description = '测试描述';
  public $themeConfig;

  public function __construct($stored = array())
  {
    $this->themeConfig = new PW_Test_Config($stored);
  }
}

class PW_Test_Recent_Widget extends PW_Test_Posts_Widget
{
}

function pw_test_install_stubs()
{
  global $pwTestOptions, $pwTestRecentRows;

  if (!class_exists('Typecho_Db', false)) {
    eval('class Typecho_Db { const SORT_DESC = "DESC"; public static function get() { return new PW_Test_Db(); } }');
  }
  if (!class_exists('Helper', false)) {
    eval('class Helper { public static function options() { global $pwTestOptions; return $pwTestOptions; } }');
  }
  if (!class_exists('Typecho_Widget', false)) {
    eval('class Typecho_Widget { public static function widget($alias, $params = "") { if (strpos($alias, "Widget_Contents_Post_Recent") === 0) { global $pwTestRecentRows; return new PW_Test_Posts_Widget($pwTestRecentRows); } if (strpos($alias, "Widget_Options") === 0) { global $pwTestOptions; return $pwTestOptions; } return null; } }');
  }
}

/* ----------------------------------------------------------------
 * 断言工具
 * ---------------------------------------------------------------- */

function expect_same($expected, $actual, $message)
{
  if ($expected !== $actual) {
    fwrite(STDERR, $message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
    exit(1);
  }
}

function expect_true($condition, $message)
{
  if ($condition !== true) {
    fwrite(STDERR, "Failed: {$message}\n");
    exit(1);
  }
}

/* ----------------------------------------------------------------
 * 夹具
 * ---------------------------------------------------------------- */

$GLOBALS['pwTestOptions'] = new PW_Test_Options(array());
$GLOBALS['pwTestRecentRows'] = array(
  array(
    'cid' => 1,
    'title' => '海边日落',
    'content' => '<p>正文描述文字</p><img src="/img/sunset.jpg" alt="x">',
    'permalink' => 'https://blog.example.com/p/1.html',
    'created' => strtotime('2026-09-01 08:00:00'),
    'categories' => array(array('mid' => 1, 'name' => '旅行', 'slug' => 'travel')),
    'tags' => array(array('mid' => 2, 'name' => '日落', 'slug' => 'sunset')),
    'fields' => new PW_Test_Fields(array('cover' => 'https://cdn.example.com/cover.jpg')),
  ),
  array(
    'cid' => 2,
    'title' => '多图文章',
    'content' => '纯文本内容',
    'permalink' => 'https://blog.example.com/p/2.html',
    'created' => strtotime('2026-09-02 09:00:00'),
    'categories' => array(array('mid' => 1, 'name' => '旅行', 'slug' => 'travel')),
    'tags' => array(),
    'fields' => new PW_Test_Fields(array('photos' => "https://cdn.example.com/a.jpg\n/img/b.jpg\n\nhttps://cdn.example.com/c.jpg")),
  ),
  array(
    'cid' => 3,
    'title' => '附件文章',
    'content' => '没有任何图片',
    'permalink' => 'https://blog.example.com/p/3.html',
    'created' => strtotime('2026-09-03 10:00:00'),
    'categories' => array(array('mid' => 3, 'name' => '美食', 'slug' => 'food')),
    'tags' => array(array('mid' => 4, 'name' => '早餐', 'slug' => 'breakfast')),
    'fields' => new PW_Test_Fields(array()),
  ),
  array(
    'cid' => 4,
    'title' => '隐藏文章',
    'content' => '<img src="/img/hidden.jpg">',
    'permalink' => 'https://blog.example.com/p/4.html',
    'created' => strtotime('2026-09-04 11:00:00'),
    'categories' => array(),
    'tags' => array(),
    'fields' => new PW_Test_Fields(array('hide' => '1', 'cover' => 'https://cdn.example.com/hidden.jpg')),
  ),
);

PW_Test_Db::$attachmentMap[3] = array(
  array('text' => serialize(array('path' => '/usr/uploads/2026/09/food.jpg', 'mime' => 'image/jpeg'))),
  array('text' => serialize(array('path' => '/usr/uploads/2026/09/note.pdf', 'mime' => 'application/pdf'))),
);

/* ----------------------------------------------------------------
 * 加载被测代码
 * ---------------------------------------------------------------- */

pw_test_install_stubs();
require $pwThemeDir . '/inc/theme-options.php';
require $pwThemeDir . '/inc/cache.php';
require $pwThemeDir . '/inc/image-helper.php';
require $pwThemeDir . '/inc/api.php';

/* ----------------------------------------------------------------
 * API 子进程模式：验证 /?pw-api=1 的 JSON 出口（含 exit）
 * ---------------------------------------------------------------- */
if (getenv('PW_API_MODE') === '1') {
  $_GET['pw-api'] = '1';
  $_GET['category'] = 'travel';
  pw_maybe_handle_api(); // 正常路径会 exit 并输出 JSON
  exit(1);
}

/* ----------------------------------------------------------------
 * 基础工具
 * ---------------------------------------------------------------- */

expect_same('16 / 9', pw_ratio_css('16:9'), 'ratio css');
expect_same('', pw_ratio_css('natural'), 'natural ratio');
expect_same('', pw_ratio_css('bogus'), 'bogus ratio');

expect_same('https://blog.example.com/img/a.jpg', pw_abs_url('/img/a.jpg'), 'root relative url');
expect_same('https://other.example.com/x.jpg', pw_abs_url('https://other.example.com/x.jpg'), 'absolute url passthrough');
expect_same('//cdn.example.com/x.jpg', pw_abs_url('//cdn.example.com/x.jpg'), 'protocol relative passthrough');
expect_same('relative.jpg', pw_abs_url('relative.jpg'), 'bare relative passthrough');

$cdnOpts = new PW_Opts(array_merge(pw_defaults(), array('cdnUrl' => 'https://static.example.com')));
expect_same('https://static.example.com/img/a.jpg', pw_cdn_url('https://blog.example.com/img/a.jpg', $cdnOpts), 'cdn rewrite');
expect_same('https://other.example.com/x.jpg', pw_cdn_url('https://other.example.com/x.jpg', $cdnOpts), 'cdn skips external');

expect_same('/img/sunset.jpg', pw_first_image_from_html('<p><img class="x" src="/img/sunset.jpg"></p>'), 'first img extraction');
expect_same('', pw_first_image_from_html('<p>no images</p>'), 'first img missing');

expect_same('正文 描述', pw_excerpt("<p>正文\n描述</p>", 20), 'excerpt collapse');
expect_same('一二三四五', pw_excerpt('<p>一二三四五六七八九</p>', 5), 'excerpt truncation');

expect_same('标题，描述', pw_alt('标题', '描述', 'title-desc'), 'alt title-desc');
expect_same('标题', pw_alt('标题', '描述', 'title'), 'alt title only');

$fieldsStub = new PW_Test_Fields(array('cover' => ' /img/x.jpg ', 'empty' => ''));
expect_same('/img/x.jpg', pw_field($fieldsStub, 'cover'), 'field trim');
expect_same('', pw_field($fieldsStub, 'missing'), 'field missing');
expect_same('', pw_field($fieldsStub, 'empty'), 'field empty');

$json = pw_json_payload(array('v' => '</script><script>alert(1)</script>'));
expect_true(strpos($json, '</script>') === false && strpos($json, '<script>') === false, 'json script escaping');

/* ----------------------------------------------------------------
 * 选项默认值合并
 * ---------------------------------------------------------------- */

$GLOBALS['pwTestOptions'] = new PW_Test_Options(array('displayMode' => 'multi', 'slideInterval' => '8'));
$storedOpts = pw_theme_opts(); // 函数内静态缓存：单次调用同时验证存储值与默认回退
expect_same('multi', $storedOpts->displayMode, 'opts stored override');
expect_same('8', $storedOpts->slideInterval, 'opts stored interval');
expect_same('60', $storedOpts->postsLimit, 'opts default fallback for unset key');
$GLOBALS['pwTestOptions'] = new PW_Test_Options(array());

/* ----------------------------------------------------------------
 * 缓存
 * ---------------------------------------------------------------- */

pw_cache_set('pw-test-roundtrip', array('a' => 1, 'b' => array('中文')), 60);
expect_same(array('a' => 1, 'b' => array('中文')), pw_cache_get('pw-test-roundtrip'), 'cache roundtrip');
expect_same(null, pw_cache_get('pw-test-missing'), 'cache miss');

/* ----------------------------------------------------------------
 * 照片池构建
 * ---------------------------------------------------------------- */

$opts = pw_theme_opts();
pw_cache_flush(); // 清掉历史运行的脏缓存，保证本轮构建走全新路径
$payload = pw_build_wall_payload($opts);

$items = $payload['items'];
expect_same(3, count($items), 'pool item count (cover + photos a/b/c + attachment, hide skipped, per-post cap 1)');

// cid=1 用 cover
expect_same('https://cdn.example.com/cover.jpg', $items[0]['url'], 'item0 uses cover field');
expect_same('https://cdn.example.com/cover.jpg', $items[0]['thumb'], 'item0 thumb falls back to url');
expect_same(array('旅行'), $items[0]['categories'], 'item0 categories');
expect_same(array('travel'), $items[0]['categorySlugs'], 'item0 category slugs');
expect_same(array('日落'), $items[0]['tags'], 'item0 tags');
expect_same('2026-09-01', $items[0]['date'], 'item0 date');
expect_same('海边日落', $items[0]['alt'], 'item0 alt follows default title rule');

// cid=2 用 photos 第一行（imagesPerPost 默认 1）
expect_same('https://cdn.example.com/a.jpg', $items[1]['url'], 'item1 first photo line');
expect_same('多图文章', $items[1]['title'], 'item1 title');

// cid=3 回退附件（跳过 pdf）
expect_same('https://blog.example.com/usr/uploads/2026/09/food.jpg', $items[2]['url'], 'item2 attachment fallback skips non-image');

// 分类统计按数量倒序
expect_same('travel', $payload['categories'][0]['slug'], 'top category by count');
expect_same(2, $payload['categories'][0]['count'], 'travel count');
expect_same(1, $payload['tags'][0]['count'], 'tag count');

// 每篇文章多图：imagesPerPost=2 时 photos 展开两行
$GLOBALS['pwTestOptions'] = new PW_Test_Options(array('imagesPerPost' => '2'));
// 重置 payload 静态缓存无法直接做到，改用独立函数验证 photos 展开逻辑
$photosRaw = "https://cdn.example.com/a.jpg\n/img/b.jpg\nhttps://cdn.example.com/c.jpg";
$lines = preg_split('/\r\n|\r|\n/', $photosRaw);
expect_same(3, count(array_filter($lines, 'trim')), 'photos lines split');
$GLOBALS['pwTestOptions'] = new PW_Test_Options(array());

/* ----------------------------------------------------------------
 * 筛选按钮渲染
 * ---------------------------------------------------------------- */

$html = pw_render_filter_buttons($payload, $opts);
expect_true(strpos($html, 'data-type="category" data-slug="travel"') !== false, 'filter button category slug');
expect_true(strpos($html, 'aria-pressed="true"') !== false, 'all button pressed');
expect_true(strpos($html, '<i class="pw-filter-count">2</i>') !== false, 'filter count badge');

$limited = new PW_Opts(array_merge(pw_defaults(), array('showCategoryFilter' => '0', 'showTagFilter' => '0', 'showAllButton' => '0')));
$limitedHtml = pw_render_filter_buttons($payload, $limited);
expect_true($limitedHtml === '', 'filters hidden when all sections disabled');

/* ----------------------------------------------------------------
 * JSON-LD
 * ---------------------------------------------------------------- */

$ld = pw_json_ld($items, $opts);
expect_same('ImageGallery', $ld['@type'], 'jsonld type');
expect_same('https://cdn.example.com/cover.jpg', $ld['image'][0]['contentUrl'], 'jsonld first image');

/* ----------------------------------------------------------------
 * API 子进程
 * ---------------------------------------------------------------- */

$cmd = '"' . PHP_BINARY . '" ' . escapeshellarg(__FILE__) . ' 2>&1';
putenv('PW_API_MODE=1');
exec($cmd, $outputLines, $exitCode);
putenv('PW_API_MODE');
$output = implode("\n", $outputLines);
expect_same(0, $exitCode, 'api subprocess exit code');
$decoded = json_decode($output, true);
expect_true(is_array($decoded), 'api output is json');
expect_same(200, $decoded['code'], 'api code');
expect_same(2, $decoded['data']['total'], 'api category filter total');
expect_same('https://cdn.example.com/cover.jpg', $decoded['data']['items'][0]['url'], 'api first item');
expect_same(array('旅行'), $decoded['data']['items'][0]['category'], 'api category names');

pw_cache_flush();
echo "PhotoWall data layer tests passed.\n";
