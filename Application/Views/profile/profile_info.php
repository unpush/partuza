<div>
<div style="text-align: center">
<?php
$thumb = PartuzaConfig::get('site_root') . '/images/people/' . $vars['person']['id'] . '.jpg';
if (! file_exists($thumb)) {
	$thumb = PartuzaConfig::get('site_root') . '/images/people/nophoto.gif';
}
$thumb = Image::by_size($thumb, 128, 128);
?>
		<a
	href="<?=PartuzaConfig::get('web_prefix')?>/profile/<?=$vars['person']['id']?>"
	rel="me"><img src="<?=$thumb?>" /></a><br />
</div>
<div class="header">
		<?
		if ($vars['is_owner']) {
			echo "<div class=\"gadgets-gadget-title-button-bar\"><a href=\"" . PartuzaConfig::get("web_prefix") . "/profile/edit\">edit</a></div>";
		}
		?>
		<a
	href="<?=PartuzaConfig::get('web_prefix')?>/profile/<?=$vars['person']['id']?>"
	rel="me"><?=$vars['person']['first_name'] . ' ' . $vars['person']['last_name']?></a>
</div>
</div>
<a
	href="<?php
	echo PartuzaConfig::get("web_prefix")?>/profile/friends/<?=$vars['person']['id']?>">View <?=$vars['is_owner'] ? 'my' : $vars['person']['first_name'] . "'s"?> friends</a>
<br />
<?
if ($vars['is_owner']) {
	$this->template('profile/profile_info_owner.php', $vars);
} else {
	$this->template('profile/profile_info_viewer.php', $vars);
}
?>
<br />
<div class="header">
<?
if ($vars['is_owner']) {
	?>
	<div class="gadgets-gadget-title-button-bar"><a
	href="<?=PartuzaConfig::get('web_prefix')?>/profile/myapps">edit</a></div>
<a href="<?=PartuzaConfig::get('web_prefix')?>/profile/myapps">
<?
}
?>
Applications
<?
if ($vars['is_owner']) {
	?>
	</a>
	
<?
}
?>
</div>
<?
if (isset($vars['applications']) && count($vars['applications'])) {
	foreach ($vars['applications'] as $app) {
		echo "<div class=\"application_link\"><a href=\"" . PartuzaConfig::get('web_prefix') . "/profile/application/{$vars['person']['id']}/{$app['id']}/{$app['mod_id']}\">" . (! empty($app['directory_title']) ? $app['directory_title'] : $app['title']) . "</a></div>";
	}
}
?>