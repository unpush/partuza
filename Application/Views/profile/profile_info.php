<div><strong><a href="/profile/<?=$vars['person']['id']?>"><?=$vars['person']['first_name'].' '.$vars['person']['last_name']?></a></strong></div>
<br />
<?
if ($vars['is_owner']) {
	$this->template('profile/profile_info_owner.php');
} else {
	$this->template('profile/profile_info_viewer.php');
}
?>
<div class="header">Applications
<? if ($vars['is_owner']) { ?>
	(<a href="/profile/myapps">edit</a>)
<? } ?>
</div>
<? if (isset($vars['applications']) && count($vars['applications'])) {
	foreach ($vars['applications'] as $app) {
		echo "<div class=\"application_link\"><a href=\"/profile/application/{$vars['person']['id']}/{$app['id']}/{$app['mod_id']}\">".(!empty($app['directory_title']) ? $app['directory_title'] : $app['title'])."</a></div>";
	}
}
?>