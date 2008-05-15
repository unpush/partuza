<? $this->template('/common/header.php'); ?>
<div id="profileInfo" class="blue">
	<? $this->template('profile/profile_info.php', $vars); ?>
</div>
<div id="profileContentWide">
	<div class="gadgets-gadget-chrome" style="width:790px">
		<div class="gadgets-gadget-title-bar">
			<span class="gadgets-gadget-title"><?=$vars['person']['first_name']?>'s friends (<?=count($vars['friends'])?>)</span>
		</div>
		<? foreach ($vars['friends'] as $friend) {
			echo "<div class=\"friend\">
					<div class=\"thumb\"><center>image</center></div>
					<p class=\"uname\"><a href=\"/profile/{$friend['id']}\">{$friend['first_name']}</a></p>
			</div>";
		}
		?>
	</div>
	<div style="clear:both"></div>
</div>
<div style="clear:both"></div>
<? $this->template('/common/footer.php'); ?>