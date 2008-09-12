<?php
if (! count($vars['activities'])) {
	echo "No activities yet.";
} else {
	$first = true;
	foreach ($vars['activities'] as $activity) {
		$add = $first ? ' first' : '';
		$first = false;
		echo "<div class=\"activity$add\">\n";
		echo "<a href=\"/profile/{$activity['person_id']}\">{$activity['person_name']}</a> ";
		echo $activity['title'] . "<br />\n";
		foreach ($activity['media_items'] as $mediaItem) {
			if ($mediaItem['media_type'] == 'IMAGE') {
				echo "<img src=\"" . $mediaItem['url'] . "\" width=\"50\"></img>";
			}
		}
		echo "{$activity['body']}\n";
		echo "</div>";
		echo "<div style=\"clear:both\"></div>\n";
	}
}
