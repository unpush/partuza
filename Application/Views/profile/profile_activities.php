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
		echo "<div class=\"activity$add\">\n".
		     "{$activity['title']}<br />\n{$activity['body']}\n".
		     "</div>\n";
	}
}