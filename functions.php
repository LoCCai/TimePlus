<?php

/**
 * Escape a value for use in an HTML attribute.
 */
function timeplus_escape_attr($value)
{
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Read the current version from the theme header.
 */
function get_theme_info()
{
  $index_file = __DIR__ . '/index.php';
  if (!is_readable($index_file)) {
    return ['version' => '0.0'];
  }

  $content = file_get_contents($index_file);
  if ($content === false || !preg_match('/@version\s+([0-9]+(?:\.[0-9]+)*)/i', $content, $matches)) {
    return ['version' => '0.0'];
  }

  return ['version' => $matches[1]];
}

/**
 * Fetch the latest published version without blocking the settings page for long.
 */
function timeplus_get_latest_version()
{
  $context = stream_context_create([
    'http' => [
      'timeout' => 3,
      'follow_location' => 1,
      'user_agent' => 'TimePlus theme update checker'
    ]
  ]);
  $content = @file_get_contents(
    'https://plog.zhheo.com/usr/themes/TimePlus/releases.json',
    false,
    $context
  );

  if ($content === false) {
    return null;
  }

  $data = json_decode($content, true);
  if (!is_array($data) || empty($data['tag_name']) || !is_scalar($data['tag_name'])) {
    return null;
  }

  $version = trim((string) $data['tag_name']);
  return preg_match('/^[0-9]+(?:\.[0-9]+)*$/', $version) ? $version : null;
}

function themeConfig($form)
{
  $theme_info = get_theme_info();
  $selfmessage = $theme_info['version'];
  $latestVersion = timeplus_get_latest_version();

  echo 'TimePlus&nbsp;&nbsp;&nbsp;当前版本：v' . timeplus_escape_attr($selfmessage);
  if ($latestVersion === null) {
    echo '&nbsp;&nbsp;&nbsp;<span>暂时无法检查新版本</span>';
  } elseif (version_compare($selfmessage, $latestVersion, '<')) {
    echo '&nbsp;&nbsp;&nbsp;发现新版本：<strong style="color:red;">v' . timeplus_escape_attr($latestVersion) . '</strong>';
    echo '&nbsp;&nbsp;请更新，<a href="https://github.com/zhheo/TimePlus/releases" target="_blank" rel="noopener noreferrer">查看新版本特性</a>';
  } else {
    echo '&nbsp;&nbsp;&nbsp;最新版本：v' . timeplus_escape_attr($latestVersion);
  }
  //首页名称
  $IndexName = new Typecho_Widget_Helper_Form_Element_Text('IndexName', NULL, '洪墨时光', _t('首页的名称(必填)'), _t('输入你的首页显示的名称'));
  $form->addInput($IndexName);
  //网站图标
  $IconUrl = new Typecho_Widget_Helper_Form_Element_Text('IconUrl', NULL, 'https://bu.dusays.com/2024/04/23/662770aaee40e.webp', _t('网站图标地址'), _t('输入网站的图标（建议200px宽度png）'));
  $form->addInput($IconUrl);
  //Apple网站图标
  $AppleIcon = new Typecho_Widget_Helper_Form_Element_Text('AppleIcon', NULL, '', _t('兼容Apple设备的图标'), _t('建议使用有背景无圆角矩形图标，在被iOS添加到书签或桌面后显示此图标（建议200px宽度png）'));
  $form->addInput($AppleIcon);
  //首页名称后缀（必填）
  $Indexdict = new Typecho_Widget_Helper_Form_Element_Text('Indexdict', NULL, '采用洪墨时光主题。', _t('首页的名称后缀(必填)'), _t('输入你的首页显示的名称后缀'));
  $form->addInput($Indexdict);
  $zmkiabout = new Typecho_Widget_Helper_Form_Element_Text('zmkiabout', NULL, '洪墨时光', _t('自定义底栏前缀'), _t('输入你的首页底部栏前缀'));
  $form->addInput($zmkiabout);
  $zmkiabouts = new Typecho_Widget_Helper_Form_Element_Text('zmkiabouts', NULL, '采用洪墨时光主题', _t('自定义底栏后缀'), _t('输入你的首页底部栏后缀'));
  $form->addInput($zmkiabouts);
  //大logo
  $Biglogo = new Typecho_Widget_Helper_Form_Element_Text('Biglogo', NULL, '欢迎使用TimePlus，这里填写你的介绍。', _t('关于-详细介绍'), _t('底栏展开后的详细介绍，可以使用html标签'));
  $form->addInput($Biglogo);
  $zmki_ys = new Typecho_Widget_Helper_Form_Element_Text('zmki_ys', NULL, '', _t('缩略图-图片处理规则名称-(优化选项,选填)'), _t('需要带自定义分隔符;使用oss图片处理生成小缩略图可优化页面打开速度; 使用帮助:https://www.zmki.cn/4956.html'));
  $form->addInput($zmki_ys);
  $zmki_sy = new Typecho_Widget_Helper_Form_Element_Text('zmki_sy', NULL, '', _t('图片版权水印-图片处理规则名称-(优化选项,选填)'), _t('需要带自定义分隔符;此处可填写oss水印规则名称，默认对全部图片生效; 使用帮助:https://www.zmki.cn/4956.html'));
  $form->addInput($zmki_sy);
  $xxhome = new Typecho_Widget_Helper_Form_Element_Text('xxhome', NULL, '', _t('Home'), _t('填写你的主页链接 http(s)://'));
  $form->addInput($xxhome);
  $xxweibo = new Typecho_Widget_Helper_Form_Element_Text('xxweibo', NULL, '', _t('Weibo'), _t('填写你的weibo链接  http(s)://'));
  $form->addInput($xxweibo);
  $xxgithub = new Typecho_Widget_Helper_Form_Element_Text('xxgithub', NULL, '', _t('GitHub'), _t('填写你的GitHub链接  http(s)://'));
  $form->addInput($xxgithub);
  $police = new Typecho_Widget_Helper_Form_Element_Text('police', NULL, '', _t('公安备案号'), _t('如果你在国内有公安备案，可在此处填写'));
  $form->addInput($police);
  $icp = new Typecho_Widget_Helper_Form_Element_Text('icp', NULL, '', _t('ICP备案号'), _t('如果你在国内有备案，可在此处填写'));
  $form->addInput($icp);
  $cnzz = new Typecho_Widget_Helper_Form_Element_Text('cnzz', NULL, '', _t('统计代码'), _t('cnzz或百度..统计代码。可在此处填写处'));
  $form->addInput($cnzz);
  $siteEstablishedAt = new Typecho_Widget_Helper_Form_Element_Text(
    'siteEstablishedAt',
    NULL,
    '2022-03-03T05:21:00+08:00',
    _t('建站时间'),
    _t('使用 ISO 8601 格式，例如 2022-03-03T05:21:00+08:00；留空则不显示运行时长')
  );
  $form->addInput($siteEstablishedAt);
  $enableVisitorInfo = new Typecho_Widget_Helper_Form_Element_Radio(
    'enableVisitorInfo',
    ['0' => _t('关闭'), '1' => _t('开启')],
    '0',
    _t('显示访客网络信息'),
    _t('默认关闭。开启后，访客浏览器会向下方配置的第三方接口发送请求，可能涉及 IP 与位置隐私')
  );
  $form->addInput($enableVisitorInfo);
  $visitorInfoEndpoint = new Typecho_Widget_Helper_Form_Element_Text(
    'visitorInfoEndpoint',
    NULL,
    'https://qifu-api.baidubce.com/ip/local/geo/v1/district',
    _t('访客信息接口'),
    _t('仅支持 HTTPS；接口需返回 ip 以及 data.country/prov/city/district/continent/isp 字段')
  );
  $form->addInput($visitorInfoEndpoint);
}
//输出导航
function themeFields($layout)
{
  $img = new Typecho_Widget_Helper_Form_Element_Textarea('img', NULL, NULL, _t('图片链接'), _t('请输入要展示的图片链接，每行一个链接。为了保证良好的体验，建议同一个文章下图片尺寸一致。'));
  $img->input->setAttribute('class', 'w-100 custom-textarea');
  $img->input->setAttribute('style', 'height: 200px');
  $layout->addItem($img);
  
  $device = new Typecho_Widget_Helper_Form_Element_Text('device', NULL, NULL, _t('设备信息'), _t('请输入拍摄设备信息'));
  $device->input->setAttribute('class', 'w-100');
  $layout->addItem($device);

  $location = new Typecho_Widget_Helper_Form_Element_Text('location', NULL, NULL, _t('拍摄地点'), _t('请输入拍摄地点信息'));
  $location->input->setAttribute('class', 'w-100');
  $layout->addItem($location);
}

/**
 * Build a safe recursive category tree using Typecho-generated permalinks.
 */
function timeplus_get_category_tree()
{
  $records = [];

  try {
    $categories = \Widget\Metas\Category\Rows::alloc();
    while ($categories->next()) {
      $id = (int) $categories->mid;
      if ($id <= 0) {
        continue;
      }

      $records[$id] = [
        'id' => $id,
        'name' => (string) $categories->name,
        'permalink' => (string) $categories->permalink,
        'parent' => (int) $categories->parent
      ];
    }
  } catch (Throwable $error) {
    return [];
  }

  if (!$records) {
    return [];
  }

  $childrenByParent = [];
  $rootIds = [];
  foreach ($records as $id => $record) {
    $parent = $record['parent'];
    if ($parent <= 0 || $parent === $id || !isset($records[$parent])) {
      $rootIds[] = $id;
      continue;
    }
    $childrenByParent[$parent][] = $id;
  }

  $visited = [];
  $buildBranch = function ($id, $trail = []) use (&$buildBranch, &$visited, $records, $childrenByParent) {
    if (!isset($records[$id]) || isset($trail[$id])) {
      return null;
    }

    $trail[$id] = true;
    $visited[$id] = true;
    $record = $records[$id];
    $branch = [
      'id' => $record['id'],
      'name' => $record['name'],
      'permalink' => $record['permalink'],
      'children' => []
    ];

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

  // Preserve cyclic or otherwise disconnected records as recoverable roots.
  foreach (array_keys($records) as $id) {
    if (!isset($visited[$id])) {
      $branch = $buildBranch($id);
      if ($branch !== null) {
        $tree[] = $branch;
      }
    }
  }

  return $tree;
}
