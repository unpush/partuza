<? $this->template('/common/header.php'); ?>
<div id="profileInfo" class="blue">
	<? $this->template('profile/profile_info.php', $vars); ?>
</div>
<div id="profileContent">
	<? $this->template('profile/profile_content.php', $vars); ?>
</div>
<div id="profileFriends">
<? $this->template('profile/profile_friends.php', $vars); ?>
</div>
<div style="clear:both"></div>
<? $this->template('/common/footer.php'); ?>