<?
if (! empty($_SESSION['message'])) {
  echo "<b>{$_SESSION['message']}</b><br /><br />";
  unset($_SESSION['message']);
}
if ($vars['is_owner']) {
  $this->template('profile/profile_friendrequests.php', $vars);
}
$width = 488;
$view = 'profile';
foreach ($vars['applications'] as $gadget) {
  $this->template('/gadget/gadget.php', array('width' => $width, 'gadget' => $gadget, 
      'person' => $vars['person'], 'view' => $view));
}
?>
<br />