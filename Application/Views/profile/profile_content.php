<?
/*
$skip = array('password', 'email', 'id', 'first_name', 'last_name', 'profile_url', 'thumbnail_url');
foreach($vars['person'] as $key => $val) {
	if (!in_array($key,$skip) && !empty($val)) {
		echo "<b>$key</b> $val<br />";
	}
}
*/
$width = 488;
$view = 'profile';
foreach ($vars['applications'] as $gadget) {
	$this->template('/gadget/gadget.php', array('width' => $width, 'gadget' => $gadget, 'person' => $vars['person'], 'view' => $view));
}
?><br />