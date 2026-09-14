<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
  exit;
}

$this->comments()->to($comments);
?>
<section id="comments" class="pw-comments" aria-labelledby="pw-comments-title">
  <h2 id="pw-comments-title"><?php $this->commentsNum(_t('暂无评论'), _t('1 条评论'), _t('%d 条评论')); ?></h2>
  <?php if ($comments->have()): ?>
    <ol class="pw-comment-list"><?php $comments->listComments(); ?></ol>
    <nav class="pw-comment-pagination" aria-label="评论分页"><?php $comments->pageNav('上一页', '下一页'); ?></nav>
  <?php endif; ?>

  <?php if ($this->allow('comment')): ?>
    <div id="<?php $this->respondId(); ?>" class="pw-respond">
      <div class="pw-cancel-reply"><?php $comments->cancelReply(); ?></div>
      <h3 id="pw-response"><?php _e('添加新评论'); ?></h3>
      <form method="post" action="<?php $this->commentUrl(); ?>" id="pw-comment-form" class="pw-comment-form">
        <?php if ($this->user->hasLogin()): ?>
          <p class="pw-comment-user"><?php _e('登录身份：'); ?><a href="<?php $this->options->profileUrl(); ?>"><?php $this->user->screenName(); ?></a> · <a href="<?php $this->options->logoutUrl(); ?>"><?php _e('退出'); ?></a></p>
        <?php else: ?>
          <p class="pw-comment-field"><label for="author"><?php _e('称呼'); ?></label><input type="text" name="author" id="author" value="<?php $this->remember('author'); ?>" autocomplete="name" required></p>
          <p class="pw-comment-field"><label for="mail"><?php _e('Email'); ?></label><input type="email" name="mail" id="mail" value="<?php $this->remember('mail'); ?>" autocomplete="email"<?php if ($this->options->commentsRequireMail): ?> required<?php endif; ?>></p>
          <p class="pw-comment-field"><label for="url"><?php _e('网站'); ?></label><input type="url" name="url" id="url" value="<?php $this->remember('url'); ?>" autocomplete="url"<?php if ($this->options->commentsRequireURL): ?> required<?php endif; ?>></p>
        <?php endif; ?>
        <p class="pw-comment-field"><label for="textarea"><?php _e('内容'); ?></label><textarea rows="6" name="text" id="textarea" required><?php $this->remember('text'); ?></textarea></p>
        <p class="pw-comment-actions"><button type="submit" class="pw-btn"><?php _e('提交评论'); ?></button></p>
      </form>
    </div>
  <?php else: ?>
    <p class="pw-comments-closed"><?php _e('评论已关闭'); ?></p>
  <?php endif; ?>
</section>
