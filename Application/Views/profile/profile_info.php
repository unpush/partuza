<div>
	<div style="text-align:center">
		<a href="/profile/<?=$vars['person']['id']?>"><img src="<?=Config::get('gadget_server')?>/gadgets/files/samplecontainer/examples/nophoto.gif" /></a><br />
	</div>
	<div class="header"><a href="/profile/<?=$vars['person']['id']?>"><?=$vars['person']['first_name'].' '.$vars['person']['last_name']?></a></div>
</div>
<a href="/profile/friends/<?=$vars['person']['id']?>">View <?=$vars['is_owner']?'my':$vars['person']['first_name']."'s"?> friends</a><br />
<?
if ($vars['is_owner']) {
	$this->template('profile/profile_info_owner.php');
} else {
	$this->template('profile/profile_info_viewer.php');
}
?>
<br />
<div class="header">
<? if ($vars['is_owner']) { ?>
	<a href="/profile/myapps">
<? } ?>
Applications
<? if ($vars['is_owner']) { ?>
	</a>
<? } ?>
</div>
<? if (isset($vars['applications']) && count($vars['applications'])) {
	foreach ($vars['applications'] as $app) {
		echo "<div class=\"application_link\"><a href=\"/profile/application/{$vars['person']['id']}/{$app['id']}/{$app['mod_id']}\">".(!empty($app['directory_title']) ? $app['directory_title'] : $app['title'])."</a></div>";
	}
}
?>