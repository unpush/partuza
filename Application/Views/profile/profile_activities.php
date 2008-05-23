<?
if (!count($vars['activities'])) {
	echo "No activities yet.";
} else {
	$first = true;
	foreach ($vars['activities'] as $activity) {
		if ($first) {
			$add = ' first';
			$first = false;
		} else {
			$add = '';
		}
		echo "<!--
		person id: ".$activity['person_id']."
		person name: ".$activity['person_name']."
		title: ".$activity['title']."
		body: ".$activity['body']."
		-->
		";
		echo "<div class=\"activity$add\" style=\'clear:both\">\n".
			"<a href=\"/profile/{$activity['person_id']}\">{$activity['person_name']}</a> ".
		     strip_tags($activity['title'])."<br />\n{$activity['body']}\n".
		     "</div>\n";
	}
}