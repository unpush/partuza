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
$gadget = $vars['application'];
$gadget['user_prefs'] = array();
$gadget['mod_id'] = 0;
$width = 488;
$view = 'preview';
$this->template('/gadget/gadget.php', array('width' => $width, 'gadget' => $gadget, 
		'person' => $vars['person'], 'view' => $view));

?>
</div>
<div id="profileRight" class="gadgets-gadget-chrome">
<div class="gadgets-gadget-title-bar"><span class="gadgets-gadget-title"><?=! empty($gadget['directory_title']) ? $gadget['directory_title'] : $gadget['title']?></span></div>
<div>
<?
echo "	<div class=\"preview_thumbnail\">";
if (! empty($gadget['thumbnail'])) {
	// ugly hack to make it work with iGoogle images
	if (substr($gadget['thumbnail'], 0, strlen('/ig/')) == '/ig/') {
		$gadget['thumbnail'] = 'http://www.google.com' . $gadget['thumbnail'];
	}
	echo "		<img src=\"{$gadget['thumbnail']}\" />";
}
?>
	</div>
<div class="preview_section">
		<?=$gadget['description']?>
	</div>
<div class="preview_section"><br />
<div class="preview_add"><a
	href="<?=Config::get('web_prefix');?>/profile/addapp?appUrl=<?=urlencode($gadget['url'])?>">Add
to my profile</a></div>
<br />
<small>Note: By installing this application you will be allowing it to
access your profile data and friends list.</small> <br />
<br />
</div>
<div class="preview_section">
<?
if (! empty($gadget['author'])) {
	echo "By {$gadget['author']}<br />";
}
if (! empty($gadget['author_email'])) {
	echo "<a href=\"mailto: {$gadget['author_email']}\">{$gadget['author_email']}</a>";
}
?>
	</div>
</div>
<div style="clear: both"></div>

<?
$this->template('/common/footer.php');
?>