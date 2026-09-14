<?php
/**
 * PhotoWall 光影墙 主题功能入口
 *
 * @package PhotoWall
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
  exit;
}

require_once __DIR__ . '/inc/theme-options.php';
require_once __DIR__ . '/inc/cache.php';
require_once __DIR__ . '/inc/image-helper.php';
require_once __DIR__ . '/inc/api.php';

/**
 * 后台主题设置面板。
 */
function themeConfig($form)
{
  pw_theme_config_fields($form);
}

/**
 * 文章编辑器中的照片墙自定义字段。
 */
function themeFields($layout)
{
  $cover = new Typecho_Widget_Helper_Form_Element_Text(
    'cover',
    null,
    '',
    _t('封面图'),
    _t('照片墙优先使用的图片 URL，支持 HTTP(S) 或站内根相对地址')
  );
  $layout->addItem($cover);

  $photos = new Typecho_Widget_Helper_Form_Element_Textarea(
    'photos',
    null,
    '',
    _t('多图列表'),
    _t('每行一个图片 URL；留空时依次回退到文章附件、正文首图')
  );
  $layout->addItem($photos);

  $thumb = new Typecho_Widget_Helper_Form_Element_Text(
    'thumb',
    null,
    '',
    _t('缩略图'),
    _t('照片墙卡片使用的小图 URL；留空时使用原图')
  );
  $layout->addItem($thumb);

  $description = new Typecho_Widget_Helper_Form_Element_Text(
    'description',
    null,
    '',
    _t('照片描述'),
    _t('弹窗大图中显示的描述文字；留空时截取正文')
  );
  $layout->addItem($description);

  $ratio = new Typecho_Widget_Helper_Form_Element_Select(
    'ratio',
    array(
      '' => _t('跟随主题设置'),
      '1:1' => '1:1',
      '4:3' => '4:3',
      '3:2' => '3:2',
      '16:9' => '16:9',
    ),
    '',
    _t('图片比例'),
    _t('仅作用于照片墙卡片，防止布局抖动')
  );
  $layout->addItem($ratio);

  $hide = new Typecho_Widget_Helper_Form_Element_Select(
    'hide',
    array(
      '' => _t('参与照片墙'),
      '1' => _t('从照片墙隐藏'),
    ),
    '',
    _t('照片墙可见性'),
    _t('隐藏后文章仍可正常访问，只是不出现在照片墙中')
  );
  $layout->addItem($hide);
}

// 在模板渲染前拦截照片墙 AJAX 接口请求。
pw_maybe_handle_api();
