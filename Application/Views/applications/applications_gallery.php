<? $this->template('/common/header.php'); ?>

<div id="profileInfo" class="blue">
	<? $this->template('profile/profile_info.php', $vars); ?>
</div>

<div id="profileContentWide">
	<p><b>Application Gallery</b></p>
		<?
		if (!count($vars['app_gallery'])) {
			echo "No applications available";
		} else {
			foreach ($vars['app_gallery'] as $app) {
				// This makes it more compatible with iGoogle type gadgets
				// since they didn't have directory titles it seems
				if (empty($app['directory_title']) && !empty($app['title'])) {
					$app['directory_title'] = $app['title'];
				}
				echo "<div class=\"app\">
				<div class=\"options\"><a href=\"/profile/preview/{$app['id']}\">Preview</a></div>
				<div class=\"app_thumbnail\">";
				if (!empty($app['thumbnail'])) {
					// ugly hack to make it work with iGoogle images
					if (substr($app['thumbnail'], 0, strlen('/ig/')) == '/ig/') {
						$app['thumbnail'] = 'http://www.google.com'.$app['thumbnail'];
					}
					echo "<img src=\"{$app['thumbnail']}\" />";
				}
				echo "</div><b>{$app['directory_title']}</b><br />{$app['description']}<br />";
				echo "</div>";
			}
		}
		?>
</div>

<div style="clear:both"></div>

<? $this->template('/common/footer.php'); ?>