<? $this->template('/common/header.php'); ?>
<div id="profileInfo" class="blue">
	<? $this->template('profile/profile_info.php', $vars); ?>
</div>
<div id="profileContentWide">
<?
$gadget = $vars['application'];
$width = 790;
$view = 'canvas';
$this->template('/gadget/gadget.php', array('width' => $width, 'gadget' => $gadget, 'person' => $vars['person'], 'view' => $view));
?>
</div>
<div style="clear:both"></div>
<? $this->template('/common/footer.php'); ?>