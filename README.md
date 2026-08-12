# TimePlus For Typecho

<p align="center">
<img src="https://img.shields.io/badge/%E5%BF%AB%E6%9D%A5%E5%88%9B%E5%BB%BA%E8%87%AA%E5%B7%B1%E7%9A%84%E7%85%A7%E7%89%87%E5%A2%99%E5%90%A7-8A2BE2?style=for-the-badge"><br />
<img src="https://img.shields.io/github/languages/code-size/LoCCai/TimePlus?label=TimePlus%20code%20size">
<img src="https://img.shields.io/github/stars/LoCCai/TimePlus?style=social"><br />
<img src="https://img.shields.io/badge/Language-javascript-blue?logo=javascript&logoColor=f7cb4f">
<img src="https://img.shields.io/badge/Language-php-blue?logo=php&logoColor=f7cb4f">
<img src="https://img.shields.io/badge/Language-html-blue?logo=html5&logoColor=f7cb4f">
<img src="https://img.shields.io/badge/Language-css-blue?logo=css3&logoColor=f7cb4f"><br />
</p>

由 [LoCCai](https://loccai.top) 修改自 [zhheo/TimePlus](https://github.com/zhheo/TimePlus)。

[English README](README_EN.md)

![洪墨时光](https://github.com/user-attachments/assets/09d44b6d-5d75-45fb-9a47-1a9ff6e43d6f)

本主题基于原作者已停止维护的 [Time 主题](https://github.com/wclk/time)改进。TimePlus 寓意让这个主题继续延续下去；主题包含个人设计偏好，如不喜欢这些改动，也可以尝试原主题。

## LoCCai 修改说明

本分支在上游版本基础上替换了一些样式、添加了一些功能，并对部分代码添加注释，方便其他开发者进行个人定制。

### 分类导航

- 使用全屏分类面板展示 Typecho 的多级分类树
- 分类链接由 Typecho 生成，兼容伪静态、`index.php` 路由和子目录安装
- 支持桌面悬停/点击、移动端横向分类栏和键盘操作
- 支持 ESC、遮罩和关闭按钮退出，并在打开期间锁定背景滚动

### 瀑布流卡片与弹出式图片卡片

- 左上角增加标签信息，支持按标签分类展示
- 左上角增加图片处理支持，改进弹出卡片压缩效果并提供查看原图按钮
- 左下角增加文章 slug 信息

### 页面底栏

- 左侧底栏支持数组随机内容或分类的一言随机内容
- 右侧底栏增加其他站点传送门
- 右侧底栏增加设备信息等补充信息页

### 关于页面

- 增加文章、评论、分类、标签、独立页面和访客统计
- 增加站点运行时长和页面加载性能信息
- 可选显示访客 IP、运营商和地区；该功能默认关闭

### 站点信息配置

- `建站时间` 使用 ISO 8601 格式，默认 `2022-03-03T05:21:00+08:00`；留空可隐藏运行时长
- `显示访客网络信息` 默认关闭；开启后访客浏览器会向配置的第三方接口发送请求
- `访客信息接口` 仅接受 HTTPS，接口异常或超时不会影响页面其他功能

## 使用文档

查看[使用文档](https://github.com/LoCCai/TimePlus/wiki)

查看安装教程：[第一次上手](https://github.com/LoCCai/TimePlus/wiki/%E7%AC%AC%E4%B8%80%E6%AC%A1%E4%B8%8A%E6%89%8B)

## 相关链接

Demo：[立即查看](https://pblog.loccai.top/)

优化图片占用：[查看教程](https://github.com/LoCCai/TimePlus/wiki/Time%E7%9B%B8%E5%86%8C%E5%9B%BE%E5%86%8C%E4%BC%98%E5%8C%96%E6%96%B9%E6%A1%88-%E7%BC%A9%E7%95%A5%E5%9B%BE%E5%8E%8B%E7%BC%A9%E5%92%8Cwebp%E8%87%AA%E9%80%82%E5%BA%94)

配合图片处理：[查看教程](https://github.com/LoCCai/TimePlus/wiki/%E9%98%BF%E9%87%8C%E4%BA%91oss%E3%80%81%E5%8F%88%E6%8B%8D%E4%BA%91%E5%82%A8%E5%AD%98%E7%AD%89%E5%82%A8%E5%AD%98%E6%A1%B6%E5%9B%BE%E7%89%87%E5%A4%84%E7%90%86%E4%BB%8B%E7%BB%8D-%E2%80%93%E9%85%8D%E5%90%88-Time%E6%97%B6%E5%85%89%E7%9B%B8%E5%86%8C%E4%BD%BF%E7%94%A8)

## 支持上游作者

如果你喜欢这个主题，可以[支持上游作者](https://rewards.zhheo.com/)。

## 常见问题

### 关于信息的换行

站点描述可以将 `/n` 作为换行符。

### Class "Widget\Metas\Category\Rows" not found

根据上游 issue #6，将 Typecho 升级到最新版本。

## 致谢

- wclk
- HTML UP
- [ZigaoWang](https://github.com/ZigaoWang)
- [zhheo](https://github.com/zhheo)
