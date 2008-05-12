<div id="profileFriends" class="gadgets-gadget-chrome">
	<div class="gadgets-gadget-title-bar"><span class="gadgets-gadget-title"><?=$vars['person']['first_name']?>'s friends (<?=count($vars['friends'])?>)</span></div>
	<? foreach ($vars['friends'] as $friend) {
		echo "<div class=\"friend\">
				<div class=\"thumb\"><center>image</center></div>
				<p class=\"uname\"><a href=\"/profile/{$friend['id']}\">{$friend['first_name']}</a></p>
		</div>";
	}
	?>
</div>