<?
$this->template('/common/header.php');
?>
<div id="profileInfo" class="blue">
	<?
	$this->template('profile/profile_info.php', $vars);
	?>
</div>
<div id="profileContentWide">
<div class="gadgets-gadget-chrome" style="width: 790px">
<div class="gadgets-gadget-title-bar"><span class="gadgets-gadget-title"><?=$vars['person']['first_name']?>'s friends (<?=count($vars['friends'])?>)</span>
</div>
		<?
		foreach ($vars['friends'] as $friend) {
			$thumb = PartuzaConfig::get('site_root') . '/images/people/' . $friend['id'] . '.jpg';
			if (! file_exists($thumb)) {
				$thumb = PartuzaConfig::get('site_root') . '/images/people/nophoto.gif';
			}
			$thumb = Image::by_size($thumb, 64, 64);
			echo "<div class=\"friend\">
					<div class=\"thumb\">
						<center>
							<a href=\"" . PartuzaConfig::get('web_prefix') . "/profile/{$friend['id']}\">
								<img src=\"$thumb\" alt=\"{$friend['first_name']} {$friend['last_name']}\" title=\"{$friend['first_name']} {$friend['last_name']}\"/>
							</a>
						</center>
					</div>
					<p class=\"uname\"><a href=\"" . PartuzaConfig::get('web_prefix') . "/profile/{$friend['id']}\">{$friend['first_name']}</a></p>
			</div>";
		}
		?>
	</div>
<div style="clear: both"></div>
</div>
<div style="clear: both"></div>
<?
$this->template('/common/footer.php');
?>