<div class="header"><?=$vars['person']['first_name']?>'s friends (<?=count($vars['friends'])?>)</div>
<? foreach ($vars['friends'] as $friend) {
	echo "<div class=\"friend\">
			<div class=\"thumb\"><center>image</center></div>
			<p class=\"uname\"><a href=\"/profile/{$friend['id']}\">{$friend['first_name']}</a></p>
	</div>";
}