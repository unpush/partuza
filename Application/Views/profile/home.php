<?
$this->template('/common/header.php');
?>

<div id="profileInfo" class="blue">
	<?
	$this->template('profile/profile_info.php', $vars);
	?>
</div>

<div id="profileContent">
<?
$this->template('profile/profile_friendrequests.php', $vars);
?>
<div class="gadgets-gadget-chrome">
<div class="gadgets-gadget-title-bar"><span class="gadgets-gadget-title">Friend's
activities</span></div>
	<?
	$this->template('profile/profile_activities.php', $vars);
	?>
</div>
<?
if (! empty($_SESSION['message'])) {
	echo "<b>{$_SESSION['message']}</b><br /><br />";
	unset($_SESSION['message']);
}
foreach ($vars['applications'] as $gadget) {
	$width = 488;
	$view = 'home';
	$this->template('/gadget/gadget.php', array('width' => $width, 'gadget' => $gadget, 
			'person' => $vars['person'], 'view' => $view));
}
?>
</div>
<div id="profileRight">
<?
$this->template('profile/profile_friends.php', $vars);
?>
</div>

<div style="clear: both"></div>

<?
$this->template('/common/footer.php');
?>