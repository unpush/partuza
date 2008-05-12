<? $this->template('/common/header.php'); ?>

<div id="profileInfo" class="blue">
	<? $this->template('profile/profile_info.php', $vars); ?>
</div>

<div id="profileContent">
<div class="gadgets-gadget-chrome">
	<? $this->template('profile/profile_activities.php', $vars); ?>
</div>
<?
if (!empty($vars['message'])) {
	echo "<b>{$vars['message']}</b><br /><br />";
}
foreach ($vars['applications'] as $gadget) {
	$width = 488;
	$view = 'home';
	$this->template('/gadget/gadget.php', array('width' => $width, 'gadget' => $gadget, 'person' => $vars['person'], 'view' => $view));
}
?>
</div>
<div id="profileFriends">
<? $this->template('profile/profile_friends.php', $vars); ?>
</div>

<div style="clear:both"></div>

<? $this->template('/common/footer.php'); ?>