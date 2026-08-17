<?php

const TIMEPLUS_RELEASE_MANIFEST_URL = 'https://raw.githubusercontent.com/LoCCai/TimePlus/self-main/release.json';
const TIMEPLUS_RELEASE_CACHE_TTL = 43200;

function timeplus_escape_html($value)
{
  return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function timeplus_escape_attr($value)
{
  return timeplus_escape_html($value);
}

/** Accept HTTP(S), protocol-relative, and root-relative public URLs. */
function timeplus_safe_url($value, $allowRootRelative = true)
{
  if (!is_scalar($value)) {
    return null;
  }

  $url = trim((string) $value);
  if ($url === '' || preg_match('/[\x00-\x1F\x7F]/', $url)) {
    return null;
  }

  if (str_starts_with($url, '//')) {
    $parts = parse_url('https:' . $url);
    return is_array($parts) && !empty($parts['host']) ? $url : null;
  }

  if ($allowRootRelative && str_starts_with($url, '/') && !str_starts_with($url, '/\\')) {
    return $url;
  }

  $parts = parse_url($url);
  if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
    return null;
  }

  return in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true) ? $url : null;
}

function timeplus_normalize_images($raw)
{
  if (!is_scalar($raw)) {
    return [];
  }

  $images = [];
  foreach (preg_split('/\R/u', (string) $raw) ?: [] as $line) {
    $url = timeplus_safe_url($line, true);
    if ($url !== null) {
      $images[] = $url;
    }
  }

  return array_values(array_unique($images));
}

function timeplus_append_image_rule($url, $rule)
{
  $safeUrl = timeplus_safe_url($url, true);
  return $safeUrl === null ? null : $safeUrl . trim((string) $rule);
}

/** Encode JSON safely for an application/json script element. */
function timeplus_json_encode($value)
{
  $json = json_encode(
    $value,
    JSON_UNESCAPED_UNICODE
      | JSON_UNESCAPED_SLASHES
      | JSON_HEX_TAG
      | JSON_HEX_AMP
      | JSON_HEX_APOS
      | JSON_HEX_QUOT
      | JSON_INVALID_UTF8_SUBSTITUTE
  );
  return $json === false ? 'null' : $json;
}

/** Local SVG icon set, independent from third-party icon fonts. */
function timeplus_icon($name)
{
  $paths = [
    'camera' => '<path d="M9 4.5 10.2 3h3.6L15 4.5h3A2 2 0 0 1 20 6.5v10A2 2 0 0 1 18 18.5H6a2 2 0 0 1-2-2v-10a2 2 0 0 1 2-2h3Zm3 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0-2a2 2 0 1 1 0-4 2 2 0 0 1 0 4Z"/>',
    'location' => '<path d="M12 2a7 7 0 0 0-7 7c0 5.1 7 13 7 13s7-7.9 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5Z"/>',
    'clock' => '<path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm1 5v4.6l3.2 1.9-1 1.7-4.2-2.5V7h2Z"/>',
    'home' => '<path d="m3 11 9-8 9 8v10h-6v-6H9v6H3V11Z"/>',
    'social' => '<path d="M7.5 6.5a3 3 0 1 0 0 6 3 3 0 0 0 0-6Zm9 5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5ZM4 20a5 5 0 0 1 7-4.6A6 6 0 0 0 10.5 20H4Zm8.5 0a4 4 0 0 1 8 0h-8Z"/>',
    'github' => '<path d="M12 2a10 10 0 0 0-3.2 19.5v-2.2c-2.6.6-3.1-1.1-3.1-1.1-.4-1.1-1.1-1.4-1.1-1.4-.9-.6.1-.6.1-.6 1 0 1.5 1 1.5 1 .9 1.5 2.3 1.1 2.8.8.1-.7.4-1.1.7-1.4-2.1-.2-4.3-1-4.3-4.6 0-1 .4-1.9 1-2.5-.1-.3-.4-1.2.1-2.5 0 0 .8-.3 2.7 1a9.3 9.3 0 0 1 4.9 0c1.9-1.3 2.7-1 2.7-1 .5 1.3.2 2.2.1 2.5.7.7 1 1.5 1 2.5 0 3.6-2.2 4.4-4.3 4.6.4.3.7.9.7 1.7v3.1A10 10 0 0 0 12 2Z"/>',
    'fullscreen' => '<path d="M4 4h6v2H6v4H4V4Zm10 0h6v6h-2V6h-4V4ZM4 14h2v4h4v2H4v-6Zm14 0h2v6h-6v-2h4v-4Z"/>',
    'image' => '<path d="M4 3h16a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm0 16h16v-3.5l-4.2-4.2-3.3 3.3-2.2-2.2L4 18.7V19Zm4-9a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/>'
  ];
  if (!isset($paths[$name])) {
    return '';
  }
  return '<svg class="timeplus-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $paths[$name] . '</svg>';
}

function get_theme_info()
{
  $indexFile = __DIR__ . '/index.php';
  if (!is_readable($indexFile)) {
    return ['version' => '0.0'];
  }
  $content = file_get_contents($indexFile);
  if ($content === false || !preg_match('/@version\s+([0-9]+(?:\.[0-9]+)*)/i', $content, $matches)) {
    return ['version' => '0.0'];
  }
  return ['version' => $matches[1]];
}

function timeplus_parse_release_manifest($content)
{
  if (!is_string($content) || $content === '') {
    return null;
  }
  $data = json_decode($content, true);
  if (!is_array($data) || !isset($data['tag_name']) || !is_scalar($data['tag_name'])) {
    return null;
  }
  $version = trim((string) $data['tag_name']);
  return preg_match('/^[0-9]+(?:\.[0-9]+)*$/', $version) ? $version : null;
}

function timeplus_release_cache_path()
{
  return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
    . DIRECTORY_SEPARATOR
    . 'timeplus-release-' . hash('sha256', TIMEPLUS_RELEASE_MANIFEST_URL) . '.json';
}

function timeplus_read_release_cache($cachePath)
{
  if (!is_readable($cachePath)) {
    return null;
  }
  $content = @file_get_contents($cachePath);
  $data = is_string($content) ? json_decode($content, true) : null;
  if (!is_array($data) || !isset($data['version'], $data['checked_at'])) {
    return null;
  }
  $version = trim((string) $data['version']);
  if (!preg_match('/^[0-9]+(?:\.[0-9]+)*$/', $version) || !is_numeric($data['checked_at'])) {
    return null;
  }
  return ['version' => $version, 'checked_at' => (int) $data['checked_at']];
}

function timeplus_get_latest_version()
{
  $cachePath = timeplus_release_cache_path();
  $cached = timeplus_read_release_cache($cachePath);
  if ($cached !== null && time() - $cached['checked_at'] < TIMEPLUS_RELEASE_CACHE_TTL) {
    return $cached['version'];
  }

  $context = stream_context_create([
    'http' => [
      'timeout' => 2,
      'follow_location' => 1,
      'max_redirects' => 3,
      'user_agent' => 'TimePlus theme update checker'
    ]
  ]);
  $content = @file_get_contents(TIMEPLUS_RELEASE_MANIFEST_URL, false, $context);
  $version = $content === false ? null : timeplus_parse_release_manifest($content);
  if ($version !== null) {
    $cacheValue = json_encode(['version' => $version, 'checked_at' => time()]);
    if (is_string($cacheValue)) {
      @file_put_contents($cachePath, $cacheValue, LOCK_EX);
    }
    return $version;
  }
  return $cached['version'] ?? null;
}

function themeConfig($form)
{
  $localVersion = get_theme_info()['version'];
  $latestVersion = timeplus_get_latest_version();
  echo 'TimePlus&nbsp;&nbsp;&nbsp;当前版本：v' . timeplus_escape_html($localVersion);
  if ($latestVersion === null) {
    echo '&nbsp;&nbsp;&nbsp;<span>暂时无法检查新版本</span>';
  } elseif (version_compare($localVersion, $latestVersion, '<')) {
    echo '&nbsp;&nbsp;&nbsp;发现新版本：<strong style="color:red;">v' . timeplus_escape_html($latestVersion) . '</strong>';
    echo '&nbsp;&nbsp;请更新，<a href="https://github.com/LoCCai/TimePlus" target="_blank" rel="noopener noreferrer">查看版本信息</a>';
  } elseif (version_compare($localVersion, $latestVersion, '>')) {
    echo '&nbsp;&nbsp;&nbsp;开发版本（当前发布版：v' . timeplus_escape_html($latestVersion) . '）';
  } else {
    echo '&nbsp;&nbsp;&nbsp;当前已是最新版本';
  }

  $IndexName = new Typecho_Widget_Helper_Form_Element_Text('IndexName', null, '洪墨时光', _t('首页的名称(必填)'), _t('输入你的首页显示的名称'));
  $form->addInput($IndexName);
  $IconUrl = new Typecho_Widget_Helper_Form_Element_Text('IconUrl', null, 'https://bu.dusays.com/2024/04/23/662770aaee40e.webp', _t('网站图标地址'), _t('仅支持 HTTP(S) 或站内根相对地址，建议使用 200px 宽度图片'));
  $form->addInput($IconUrl);
  $AppleIcon = new Typecho_Widget_Helper_Form_Element_Text('AppleIcon', null, '', _t('兼容 Apple 设备的图标'), _t('仅支持 HTTP(S) 或站内根相对地址；建议使用有背景的无圆角矩形图标'));
  $form->addInput($AppleIcon);
  $Indexdict = new Typecho_Widget_Helper_Form_Element_Text('Indexdict', null, '采用洪墨时光主题。', _t('首页的名称后缀(必填)'), _t('输入你的首页显示的名称后缀'));
  $form->addInput($Indexdict);
  $zmkiabout = new Typecho_Widget_Helper_Form_Element_Text('zmkiabout', null, '洪墨时光', _t('自定义底栏前缀'), _t('输入你的首页底部栏前缀'));
  $form->addInput($zmkiabout);
  $zmkiabouts = new Typecho_Widget_Helper_Form_Element_Text('zmkiabouts', null, '采用洪墨时光主题', _t('自定义底栏后缀'), _t('输入你的首页底部栏后缀'));
  $form->addInput($zmkiabouts);
  $Biglogo = new Typecho_Widget_Helper_Form_Element_Text('Biglogo', null, '欢迎使用TimePlus，这里填写你的介绍。', _t('关于-详细介绍'), _t('允许管理员填写受信任的 HTML；请勿粘贴来源不明的代码'));
  $form->addInput($Biglogo);
  $zmki_ys = new Typecho_Widget_Helper_Form_Element_Text('zmki_ys', null, '', _t('缩略图处理规则（选填）'), _t('填写包含分隔符的 OSS/CDN 图片处理后缀'));
  $form->addInput($zmki_ys);
  $zmki_sy = new Typecho_Widget_Helper_Form_Element_Text('zmki_sy', null, '', _t('原图水印规则（选填）'), _t('填写包含分隔符的 OSS/CDN 图片处理后缀'));
  $form->addInput($zmki_sy);
  $xxhome = new Typecho_Widget_Helper_Form_Element_Text('xxhome', null, '', _t('Home'), _t('仅支持 HTTP(S) 或站内根相对地址'));
  $form->addInput($xxhome);
  $xxweibo = new Typecho_Widget_Helper_Form_Element_Text('xxweibo', null, '', _t('Weibo'), _t('仅支持 HTTP(S) 或站内根相对地址'));
  $form->addInput($xxweibo);
  $xxgithub = new Typecho_Widget_Helper_Form_Element_Text('xxgithub', null, '', _t('GitHub'), _t('仅支持 HTTP(S) 或站内根相对地址'));
  $form->addInput($xxgithub);
  $police = new Typecho_Widget_Helper_Form_Element_Text('police', null, '', _t('公安备案号'), _t('如果你在国内有公安备案，可在此处填写'));
  $form->addInput($police);
  $icp = new Typecho_Widget_Helper_Form_Element_Text('icp', null, '', _t('ICP备案号'), _t('如果你在国内有备案，可在此处填写'));
  $form->addInput($icp);
  $cnzz = new Typecho_Widget_Helper_Form_Element_Text('cnzz', null, '', _t('统计代码'), _t('允许管理员填写受信任的统计脚本；请勿粘贴来源不明的代码'));
  $form->addInput($cnzz);
  $siteEstablishedAt = new Typecho_Widget_Helper_Form_Element_Text('siteEstablishedAt', null, '2022-03-03T05:21:00+08:00', _t('建站时间'), _t('使用 ISO 8601 格式；留空则不显示运行时长'));
  $form->addInput($siteEstablishedAt);
  $enableVisitorInfo = new Typecho_Widget_Helper_Form_Element_Radio('enableVisitorInfo', ['0' => _t('关闭'), '1' => _t('开启')], '0', _t('显示访客网络信息'), _t('默认关闭；开启后访客浏览器会请求第三方接口，可能涉及 IP 与位置隐私'));
  $form->addInput($enableVisitorInfo);
  $visitorInfoEndpoint = new Typecho_Widget_Helper_Form_Element_Text('visitorInfoEndpoint', null, 'https://qifu-api.baidubce.com/ip/local/geo/v1/district', _t('访客信息接口'), _t('仅支持 HTTPS；接口需返回 ip 以及 data.country/prov/city/district/continent/isp 字段'));
  $form->addInput($visitorInfoEndpoint);
}

function themeFields($layout)
{
  $img = new Typecho_Widget_Helper_Form_Element_Textarea('img', null, null, _t('图片链接'), _t('每行一个 HTTP(S)、协议相对或站内根相对图片地址；空行和危险协议会被忽略'));
  $img->input->setAttribute('class', 'w-100 custom-textarea');
  $img->input->setAttribute('style', 'height: 200px');
  $layout->addItem($img);
  $device = new Typecho_Widget_Helper_Form_Element_Text('device', null, null, _t('设备信息'), _t('请输入拍摄设备信息'));
  $device->input->setAttribute('class', 'w-100');
  $layout->addItem($device);
  $location = new Typecho_Widget_Helper_Form_Element_Text('location', null, null, _t('拍摄地点'), _t('请输入拍摄地点信息'));
  $location->input->setAttribute('class', 'w-100');
  $layout->addItem($location);
}

function timeplus_build_category_tree($records)
{
  if (!is_array($records) || $records === []) {
    return [];
  }
  $normalized = [];
  foreach ($records as $record) {
    if (!is_array($record) || empty($record['id'])) {
      continue;
    }
    $id = (int) $record['id'];
    if ($id <= 0) {
      continue;
    }
    $normalized[$id] = [
      'id' => $id,
      'name' => isset($record['name']) ? (string) $record['name'] : '',
      'permalink' => isset($record['permalink']) ? (string) $record['permalink'] : '',
      'parent' => isset($record['parent']) ? (int) $record['parent'] : 0
    ];
  }

  $childrenByParent = [];
  $rootIds = [];
  foreach ($normalized as $id => $record) {
    $parent = $record['parent'];
    if ($parent <= 0 || $parent === $id || !isset($normalized[$parent])) {
      $rootIds[] = $id;
    } else {
      $childrenByParent[$parent][] = $id;
    }
  }

  $visited = [];
  $buildBranch = function ($id, $trail = []) use (&$buildBranch, &$visited, $normalized, $childrenByParent) {
    if (!isset($normalized[$id]) || isset($trail[$id])) {
      return null;
    }
    $trail[$id] = true;
    $visited[$id] = true;
    $record = $normalized[$id];
    $branch = ['id' => $record['id'], 'name' => $record['name'], 'permalink' => $record['permalink'], 'children' => []];
    foreach ($childrenByParent[$id] ?? [] as $childId) {
      $child = $buildBranch($childId, $trail);
      if ($child !== null) {
        $branch['children'][] = $child;
      }
    }
    return $branch;
  };

  $tree = [];
  foreach ($rootIds as $rootId) {
    $branch = $buildBranch($rootId);
    if ($branch !== null) {
      $tree[] = $branch;
    }
  }
  foreach (array_keys($normalized) as $id) {
    if (!isset($visited[$id])) {
      $branch = $buildBranch($id);
      if ($branch !== null) {
        $tree[] = $branch;
      }
    }
  }
  return $tree;
}

function timeplus_get_category_tree()
{
  $records = [];
  try {
    $categories = \Widget\Metas\Category\Rows::alloc();
    while ($categories->next()) {
      $records[] = [
        'id' => (int) $categories->mid,
        'name' => (string) $categories->name,
        'permalink' => (string) $categories->permalink,
        'parent' => (int) $categories->parent
      ];
    }
  } catch (Throwable $error) {
    return [];
  }
  return timeplus_build_category_tree($records);
}
