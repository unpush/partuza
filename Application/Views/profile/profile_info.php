<div>
	<div style="text-align:center">
		<a href="<?=Config::get('web_prefix')?>/profile/<?=$vars['person']['id']?>" rel="me"><img src="<?=Image::by_size(Config::get('site_root').(!empty($vars['person']['thumbnail_url'])?$vars['person']['thumbnail_url']:'/images/people/nophoto.gif'), 92, 96)?>" /></a><br />
	</div>
	<div class="header">
		<? if ($vars['is_owner']) {
			echo "<div class=\"gadgets-gadget-title-button-bar\"><a href=\"/profile/edit\">edit</a></div>";
		}?>
		<a href="<?=Config::get('web_prefix')?>/profile/<?=$vars['person']['id']?>" rel="me"><?=$vars['person']['first_name'].' '.$vars['person']['last_name']?></a>
	</div>
</div>
<a href="/profile/friends/<?=$vars['person']['id']?>">View <?=$vars['is_owner']?'my':$vars['person']['first_name']."'s"?> friends</a><br />
<?
if ($vars['is_owner']) {
	$this->template('profile/profile_info_owner.php', $vars);
} else {
	$this->template('profile/profile_info_viewer.php', $vars);
}
?>
<br />
<div class="header">
<? if ($vars['is_owner']) { ?>
	<div class="gadgets-gadget-title-button-bar"><a href="<?=Config::get('web_prefix')?>/profile/myapps">edit</a></div>
	<a href="<?=Config::get('web_prefix')?>/profile/myapps">
<? } ?>
Applications
<? if ($vars['is_owner']) { ?>
	</a>
	
<? } ?>
</div>
<? if (isset($vars['applications']) && count($vars['applications'])) {
	foreach ($vars['applications'] as $app) {
		echo "<div class=\"application_link\"><a href=\"" . Config::get('web_prefix') . "/profile/application/{$vars['person']['id']}/{$app['id']}/{$app['mod_id']}\">".(!empty($app['directory_title']) ? $app['directory_title'] : $app['title'])."</a></div>";
	}
}
?>