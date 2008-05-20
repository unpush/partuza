<? $this->template('/common/header.php'); ?>

<div id="profileInfo" class="blue">
	<? $this->template('profile/profile_info.php', $vars); ?>
</div>

<div id="profileContentWide">
	<p><b>Manage Applications</b></p>
		<a href="<?=Config::get('web_prefix');?>/profile/appgallery">Browse the application directory >></a>
		<br /><br />
		
		Or add an application by url:<br />
		<form method="get" action="<?=Config::get('web_prefix');?>/profile/addapp"><input type="text" name="appUrl" size="35" /> <input class="submit" type="submit" value="Add Application" /></form>
		<hr>
		<b>Your Applications:</b><br /><br />
		<?
		if (!count($vars['applications'])) {
			echo "You have not yet added any applications to your profile";
		} else {
			foreach ($vars['applications'] as $app) {
				// This makes it more compatible with iGoogle type gadgets
				// since they didn't have directory titles it seems
				if (empty($app['directory_title']) && !empty($app['title'])) {
					$app['directory_title'] = $app['title'];
				}
				echo "<div class=\"app\"><div class=\"options\">";
				if (is_object(unserialize($app['settings']))) { 
					echo "<a href=\"" . Config::get('web_prefix') . "/profile/appsettings/{$app['id']}/{$app['mod_id']}\">Settings</a><br />";
				}			
				echo "<a href=\"" . Config::get('web_prefix') . "/profile/removeapp/{$app['id']}/{$app['mod_id']}\">Remove</a></div>
				<div class=\"app_thumbnail\">";
				if (!empty($app['thumbnail'])) {
					// ugly hack to make it work with iGoogle images
					if (substr($app['thumbnail'], 0, strlen('/ig/')) == '/ig/') {
						$app['thumbnail'] = 'http://www.google.com'.$app['thumbnail'];
					}
					echo "<img src=\"{$app['thumbnail']}\" />";
				}
				echo "</div><b>{$app['directory_title']}</b><br />{$app['description']}<br />";
				if (!empty($app['author_email'])) {
					$app['author'] = "<a href=\"mailto: {$app['author_email']}\">{$app['author']}</a>";
				}
				if (!empty($app['author'])) {
					echo "By {$app['author']}";
				}
				echo "</div>";
			}
		}
		?>
</div>

<div style="clear:both"></div>

<? $this->template('/common/footer.php'); ?>