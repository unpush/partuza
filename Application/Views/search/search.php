<?
$this->template('/common/header.php');
echo "<b>Search Results</b><br /><br />";
if ($vars['error']) {
	echo "<b>{$vars['error']}</b>";
} else {
	foreach ($vars['results'] as $result) {
		echo "
		<div class=\"searchResult\">
			<div class=\"menu\">";
		if ($_SESSION['id'] == $result['id']) {
			echo "<a href=\"/profile/{$result['id']}\">This is you</a><br />";
		} elseif (in_array($result['id'], $vars['friends'])) {
			echo "<a href=\"/home/removefriend/{$result['id']}\">Remove friend</a><br />";
		} else {
			echo "<a href=\"/home/addfriend/{$result['id']}\">Add as friend</a><br />";
					
		}
		echo "		<a href=\"/profile/{$result['id']}\">View Profile</a>
				</div>	
			<a href=\"/profile/{$result['id']}\">{$result['first_name']} {$result['last_name']}</a>
		</div>";
	}
} 
$this->template('/common/footer.php');
?>