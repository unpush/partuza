<div>
	<div style="text-align:center">
		<img src="<?=Config::get('gadget_server')?>/gadgets/files/samplecontainer/examples/nophoto.gif" /><br />
		<strong><a href="/profile/<?=$vars['person']['id']?>"><?=$vars['person']['first_name'].' '.$vars['person']['last_name']?></a></strong>
	</div>
</div>
<br />
<?
if ($vars['is_owner']) {
	$this->template('profile/profile_info_owner.php');
} else {
	$this->template('profile/profile_info_viewer.php');
}
?>
<div class="header">
<? if ($vars['is_owner']) { ?>
	<div style="float:right; font-weight: normal"><a href="/profile/myapps">edit</a></div>
<? } ?>
Applications
</div>
<? if (isset($vars['applications']) && count($vars['applications'])) {
	foreach ($vars['applications'] as $app) {
		echo "<div class=\"application_link\"><a href=\"/profile/application/{$vars['person']['id']}/{$app['id']}/{$app['mod_id']}\">".(!empty($app['directory_title']) ? $app['directory_title'] : $app['title'])."</a></div>";
	}
}
?>