<?php
/**
 * PhotoWall 主题设置默认值与后台设置面板定义。
 *
 * @package PhotoWall
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
  exit;
}

/**
 * 全部设置项的默认值（唯一事实来源）。
 */
function pw_defaults()
{
  return array(
    // 基础设置
    'logoUrl' => '',
    'indexTitle' => '',
    'indexSubtitle' => '',
    'backgroundColor' => '#0e1014',
    'colorScheme' => 'auto',
    'footerText' => '',
    // 布局设置
    'displayMode' => 'single',
    'gridColumns' => '3',
    'gap' => '12',
    'radius' => '12',
    'cardRatio' => 'natural',
    'imagesPerPost' => '1',
    'postsLimit' => '60',
    'noscriptCount' => '24',
    // 动效设置
    'transitionEffect' => 'fade',
    'slideInterval' => '5',
    'transitionDuration' => '800',
    'easing' => 'cubic-bezier(.4,0,.2,1)',
    'randomOrder' => '1',
    'hoverPause' => '1',
    'pauseOnHidden' => '1',
    'calmOnMobile' => '1',
    // 筛选设置
    'showCategoryFilter' => '1',
    'showTagFilter' => '1',
    'showAllButton' => '1',
    'maxCategories' => '12',
    'maxTags' => '12',
    'defaultFilter' => '',
    'urlFilter' => '1',
    // 弹窗设置
    'enableLightbox' => '1',
    'showTitle' => '1',
    'showDescription' => '1',
    'showCategory' => '1',
    'showTags' => '1',
    'showDate' => '1',
    'showDownload' => '1',
    'showOriginal' => '1',
    'showShare' => '1',
    'showGotoPost' => '1',
    'lightboxKeyboard' => '1',
    'lightboxGesture' => '1',
    // 性能设置
    'lazyload' => '1',
    'preloadCount' => '2',
    'cdnUrl' => '',
    'cacheEnabled' => '1',
    'cacheTtl' => '600',
    // SEO 设置
    'seoTitle' => '',
    'seoDescription' => '',
    'ogImage' => '',
    'jsonldEnabled' => '1',
    'altRule' => 'title',
    // 高级设置
    'apiEnabled' => '1',
    'apiThreshold' => '300',
    'customCss' => '',
    'customHead' => '',
  );
}

/**
 * 后台主题设置表单。
 */
function pw_theme_config_fields($form)
{
  $d = pw_defaults();
  $yesNo = array('1' => _t('开启'), '0' => _t('关闭'));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Text('logoUrl', null, $d['logoUrl'],
    _t('【基础】站点 Logo'),
    _t('显示在顶部导航的 Logo 图片 URL；留空则只显示站点名称')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Text('indexTitle', null, $d['indexTitle'],
    _t('【基础】首页大标题'),
    _t('显示在照片墙上方的标题；留空则不显示')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Text('indexSubtitle', null, $d['indexSubtitle'],
    _t('【基础】首页副标题'),
    _t('显示在首页大标题下方的一句介绍；留空则不显示')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Text('backgroundColor', null, $d['backgroundColor'],
    _t('【基础】背景色'),
    _t('十六进制颜色值，如 #0e1014；同时用于浅色模式下的页面基底做参考')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('colorScheme', array(
    'auto' => _t('自动（跟随系统）'),
    'dark' => _t('深色'),
    'light' => _t('浅色'),
  ), $d['colorScheme'],
    _t('【基础】配色模式'),
    _t('默认自动；访客可通过顶部按钮切换并记忆选择')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Textarea('footerText', null, $d['footerText'],
    _t('【基础】页脚版权'),
    _t('支持简单 HTML；留空则只显示 Powered by 信息')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('displayMode', array(
    'single' => _t('单图模式'),
    'multi' => _t('多图网格'),
    'mixed' => _t('混合模式（大图 + 网格）'),
  ), $d['displayMode'],
    _t('【布局】展示模式'),
    _t('单图适合摄影首页；多图为网格墙；混合为上方大图、下方网格')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Select('gridColumns', array(
    '2' => '2', '3' => '3', '4' => '4', '5' => '5',
  ), $d['gridColumns'],
    _t('【布局】多图列数'),
    _t('移动端会自动降为 2 列')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Text('gap', null, $d['gap'],
    _t('【布局】图片间距'),
    _t('像素，0 - 64')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Text('radius', null, $d['radius'],
    _t('【布局】图片圆角'),
    _t('像素，0 - 48')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Select('cardRatio', array(
    'natural' => _t('原始比例'),
    '1:1' => '1:1', '4:3' => '4:3', '3:2' => '3:2', '16:9' => '16:9',
  ), $d['cardRatio'],
    _t('【布局】卡片比例'),
    _t('统一比例可避免加载时布局抖动；文章级可用 ratio 字段覆盖')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Text('imagesPerPost', null, $d['imagesPerPost'],
    _t('【布局】每篇文章取图数'),
    _t('一篇文章最多贡献到照片墙的图片数量，1 - 10')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Text('postsLimit', null, $d['postsLimit'],
    _t('【布局】照片池文章数'),
    _t('照片墙加载的最新文章数量上限，1 - 500')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Text('noscriptCount', null, $d['noscriptCount'],
    _t('【布局】无脚本回退数量'),
    _t('禁用 JavaScript 时显示的照片数量，1 - 60')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Select('transitionEffect', array(
    'fade' => _t('渐入渐出'),
    'randomFade' => _t('随机淡入'),
    'zoom' => _t('缩放渐入'),
    'slide' => _t('滑动切换'),
    'kenburns' => _t('Ken Burns 缓慢缩放'),
    'random' => _t('随机组合'),
  ), $d['transitionEffect'],
    _t('【动效】过渡效果'),
    _t('单图模式用于整屏切换；多图模式用于卡片替换')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Text('slideInterval', null, $d['slideInterval'],
    _t('【动效】轮播间隔'),
    _t('秒，2 - 60')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Text('transitionDuration', null, $d['transitionDuration'],
    _t('【动效】过渡时长'),
    _t('毫秒，150 - 4000')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Select('easing', array(
    'ease' => 'ease',
    'ease-in' => 'ease-in',
    'ease-out' => 'ease-out',
    'ease-in-out' => 'ease-in-out',
    'cubic-bezier(.4,0,.2,1)' => 'cubic-bezier(.4,0,.2,1)',
  ), $d['easing'],
    _t('【动效】缓动函数'),
    _t('过渡使用的 CSS 缓动曲线')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('randomOrder', $yesNo, $d['randomOrder'],
    _t('【动效】随机顺序'),
    _t('开启后照片按随机顺序流转')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('hoverPause', $yesNo, $d['hoverPause'],
    _t('【动效】鼠标悬停暂停'),
    _t('鼠标移入照片墙时暂停轮播')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('pauseOnHidden', $yesNo, $d['pauseOnHidden'],
    _t('【动效】页面不可见暂停'),
    _t('切换浏览器标签页时暂停轮播，避免资源浪费')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('calmOnMobile', $yesNo, $d['calmOnMobile'],
    _t('【动效】移动端降低动效'),
    _t('触屏设备自动简化为轻量淡入；同时遵循系统“减少动态效果”偏好')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('showCategoryFilter', $yesNo, $d['showCategoryFilter'],
    _t('【筛选】显示分类按钮'),
    _t('读取 Typecho 分类生成筛选按钮')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('showTagFilter', $yesNo, $d['showTagFilter'],
    _t('【筛选】显示标签按钮'),
    _t('读取 Typecho 标签生成筛选按钮')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('showAllButton', $yesNo, $d['showAllButton'],
    _t('【筛选】显示“全部”按钮'),
    _t('点击后取消筛选，展示全部照片')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Text('maxCategories', null, $d['maxCategories'],
    _t('【筛选】最多显示分类数'),
    _t('超出部分不显示按钮，0 - 50')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Text('maxTags', null, $d['maxTags'],
    _t('【筛选】最多显示标签数'),
    _t('超出部分不显示按钮，0 - 50')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Text('defaultFilter', null, $d['defaultFilter'],
    _t('【筛选】默认选中项'),
    _t('格式如 category:slug 或 tag:slug；留空表示“全部”')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('urlFilter', $yesNo, $d['urlFilter'],
    _t('【筛选】支持 URL 参数'),
    _t('支持 ?category=slug 与 ?tag=slug 分享链接，筛选变化会更新地址栏')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('enableLightbox', $yesNo, $d['enableLightbox'],
    _t('【弹窗】启用 Lightbox'),
    _t('关闭后点击图片直接跳转文章')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('showTitle', $yesNo, $d['showTitle'],
    _t('【弹窗】显示标题'), ''));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('showDescription', $yesNo, $d['showDescription'],
    _t('【弹窗】显示描述'), ''));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('showCategory', $yesNo, $d['showCategory'],
    _t('【弹窗】显示分类'), ''));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('showTags', $yesNo, $d['showTags'],
    _t('【弹窗】显示标签'), ''));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('showDate', $yesNo, $d['showDate'],
    _t('【弹窗】显示日期'), ''));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('showDownload', $yesNo, $d['showDownload'],
    _t('【弹窗】显示下载按钮'), ''));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('showOriginal', $yesNo, $d['showOriginal'],
    _t('【弹窗】显示原图按钮'), _t('在新标签页打开原图')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('showShare', $yesNo, $d['showShare'],
    _t('【弹窗】显示分享按钮'), _t('优先调用系统分享，不支持时复制链接')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('showGotoPost', $yesNo, $d['showGotoPost'],
    _t('【弹窗】显示跳转文章按钮'), ''));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('lightboxKeyboard', $yesNo, $d['lightboxKeyboard'],
    _t('【弹窗】键盘操作'),
    _t('Esc 关闭，方向键切换，+/- 缩放')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('lightboxGesture', $yesNo, $d['lightboxGesture'],
    _t('【弹窗】手势支持'),
    _t('左右滑动切换、双指缩放、下拉关闭')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('lazyload', $yesNo, $d['lazyload'],
    _t('【性能】图片懒加载'),
    _t('视口外图片延迟加载')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Text('preloadCount', null, $d['preloadCount'],
    _t('【性能】首屏预加载数量'),
    _t('预加载前 N 张图片，0 - 4')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Text('cdnUrl', null, $d['cdnUrl'],
    _t('【性能】CDN 域名'),
    _t('例如 https://cdn.example.com；会将站内图片地址替换为 CDN 地址，留空不启用')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('cacheEnabled', $yesNo, $d['cacheEnabled'],
    _t('【性能】照片池缓存'),
    _t('缓存照片数据 JSON，减少每次请求的查询开销；发布或编辑文章后自动失效')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Text('cacheTtl', null, $d['cacheTtl'],
    _t('【性能】缓存时间'),
    _t('秒，建议 300 - 3600；填 0 表示每次都刷新缓存但保留防并发能力')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Text('seoTitle', null, $d['seoTitle'],
    _t('【SEO】首页标题'),
    _t('仅作用于首页 <title>；留空使用“站点名 - 站点描述”结构')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Textarea('seoDescription', null, $d['seoDescription'],
    _t('【SEO】首页描述'),
    _t('meta description 与 OG description；留空使用站点描述')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Text('ogImage', null, $d['ogImage'],
    _t('【SEO】OG 图片'),
    _t('社交分享卡片图片；留空时使用照片墙第一张图')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('jsonldEnabled', $yesNo, $d['jsonldEnabled'],
    _t('【SEO】输出 JSON-LD'),
    _t('首页输出 schema.org ImageGallery 结构化数据')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Select('altRule', array(
    'title' => _t('文章标题'),
    'title-desc' => _t('文章标题 + 描述'),
  ), $d['altRule'],
    _t('【SEO】图片 alt 规则'),
    _t('照片墙图片的 alt 文本生成规则')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('apiEnabled', $yesNo, $d['apiEnabled'],
    _t('【高级】启用 AJAX 接口'),
    _t('提供 /?pw-api=1 照片数据接口，供前端筛选与外部调用')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Text('apiThreshold', null, $d['apiThreshold'],
    _t('【高级】AJAX 启用阈值'),
    _t('照片超过该数量时前端自动改用接口分页加载，0 表示始终用内嵌数据')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Textarea('customCss', null, $d['customCss'],
    _t('【高级】自定义 CSS'),
    _t('直接输出到页面 <head>；请勿粘贴来源不明的代码')));

  $form->addInput(new Typecho_Widget_Helper_Form_Element_Textarea('customHead', null, $d['customHead'],
    _t('【高级】自定义头部代码'),
    _t('统计代码等，直接输出到 </head> 之前；允许管理员填写受信任的 HTML')));
}
