<?
if (! count($vars['activities'])) {
	echo "No activities yet.";
} else {
	$first = true;
	foreach ($vars['activities'] as $activity) {
		$add = $first ? ' first' : '';
		$first = false;
		echo "<div class=\"activity$add\">\n" . "<a href=\"/profile/{$activity['person_id']}\">{$activity['person_name']}</a> " . $activity['title'] . "<br />\n{$activity['body']}\n" . "</div>
		     <div style=\"clear:both\"></div>
		     \n";
	}
}