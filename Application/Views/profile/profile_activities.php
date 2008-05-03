<?
if (count($vars['friend_requests'])) {
	//TODO style and link to a page where u can view / accept them
	echo "<div id=\"friendRequests\"><b>You have ".count($vars['friend_requests'])." pending friend requests.</b><br />
	<i>Don't be affraid to reject requests, they won't know that you did</i><br /><br />";
	foreach ($vars['friend_requests'] as $request) {
		echo "<div id=\"request\">
			<a href=\"/profile/{$request['id']}\">{$request['first_name']} {$request['last_name']}</a> requests to be your friend.<br />
			<a href=\"/profile/{$request['id']}\">View Profile</a> |
			<a href=\"/home/acceptfriend/{$request['id']}\">Accept Request</a> |
			<a href=\"/home/rejectfriend/{$request['id']}\">Reject Request</a>
			</div><br />";
	}
	echo "</div>";
}
?>
Profile activity stream