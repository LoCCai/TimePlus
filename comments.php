<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
  exit;
}
$this->comments()->to($comments);
?>
<section id="comments" class="timeplus-comments" aria-labelledby="comments-title">
  <h2 id="comments-title"><?php $this->commentsNum(_t('暂无评论'), _t('1 条评论'), _t('%d 条评论')); ?></h2>
  <?php if ($comments->have()): ?>
    <ol class="comment-list"><?php $comments->listComments(); ?></ol>
    <nav class="comment-pagination" aria-label="评论分页"><?php $comments->pageNav('上一页', '下一页'); ?></nav>
  <?php endif; ?>

  <?php if ($this->allow('comment')): ?>
    <div id="<?php $this->respondId(); ?>" class="respond">
      <div class="cancel-comment-reply"><?php $comments->cancelReply(); ?></div>
      <h3 id="response"><?php _e('添加新评论'); ?></h3>
      <form method="post" action="<?php $this->commentUrl(); ?>" id="comment-form">
        <?php if ($this->user->hasLogin()): ?>
          <p><?php _e('登录身份：'); ?><a href="<?php $this->options->profileUrl(); ?>"><?php $this->user->screenName(); ?></a> · <a href="<?php $this->options->logoutUrl(); ?>"><?php _e('退出'); ?></a></p>
        <?php else: ?>
          <p><label for="author"><?php _e('称呼'); ?></label><input type="text" name="author" id="author" value="<?php $this->remember('author'); ?>" autocomplete="name" required></p>
          <p><label for="mail"><?php _e('Email'); ?></label><input type="email" name="mail" id="mail" value="<?php $this->remember('mail'); ?>" autocomplete="email"<?php if ($this->options->commentsRequireMail): ?> required<?php endif; ?>></p>
          <p><label for="url"><?php _e('网站'); ?></label><input type="url" name="url" id="url" value="<?php $this->remember('url'); ?>" autocomplete="url"<?php if ($this->options->commentsRequireURL): ?> required<?php endif; ?>></p>
        <?php endif; ?>
        <p><label for="textarea"><?php _e('内容'); ?></label><textarea rows="8" name="text" id="textarea" required><?php $this->remember('text'); ?></textarea></p>
        <p><button type="submit" class="submit"><?php _e('提交评论'); ?></button></p>
      </form>
    </div>
  <?php else: ?>
    <p><?php _e('评论已关闭'); ?></p>
  <?php endif; ?>
</section>
