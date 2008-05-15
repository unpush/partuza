<? $this->template('/common/header.php'); ?>
<div id="profileInfo" class="blue">
	<? $this->template('profile/profile_info.php', $vars); ?>
</div>
<div id="profileContent">
	<div class="gadgets-gadget-chrome">
		<div class="gadgets-gadget-title-bar"><span class="gadgets-gadget-title"><?=$vars['person']['first_name']?>'s activities</span></div>
		<? $this->template('profile/profile_activities.php', $vars); ?>
	</div>
	<? $this->template('profile/profile_content.php', $vars); ?>
</div>
<div id="profileRight">
<? $this->template('profile/profile_friends.php', $vars); ?>
</div>
<div style="clear:both"></div>
<? $this->template('/common/footer.php'); ?>