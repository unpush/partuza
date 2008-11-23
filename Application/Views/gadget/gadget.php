<?php
if (! empty($vars['gadget']['error'])) {
  echo "<b>{$vars['gadget']['error']}</b>";
} else {
  $width = $vars['width'];
  $gadget = $vars['gadget'];
  $view = $vars['view'];
  $user_prefs = $gadget['user_prefs'];
  
  $prefs = '';
  $settings = ! empty($gadget['settings']) ? unserialize($gadget['settings']) : array();
  foreach ($settings as $key => $setting) {
    if (! empty($key)) {
      $value = isset($user_prefs[$key]) ? $user_prefs[$key] : (isset($setting->default) ? $setting->default : null);
      if (isset($user_prefs[$key])) {
        unset($user_prefs[$key]);
      }
      $prefs .= '&up_' . urlencode($key) . '=' . urlencode($value);
    }
  }
  
  foreach ($user_prefs as $name => $value) {
    // if some keys _are_ set in the db, but not in the gadget metadata, we still parse them on the url
    // (the above loop unsets the entries that matched  
    if (! empty($value) && ! isset($appParams[$name])) {
      $prefs .= '&up_' . urlencode($name) . '=' . urlencode($value);
    }
  }
  
  $securityToken = BasicSecurityToken::createFromValues(isset($vars['person']['id']) ? $vars['person']['id'] : '0', // owner
(isset($_SESSION['id']) ? $_SESSION['id'] : '0'), // viewer
$gadget['id'], // app id
PartuzaConfig::get('container'), // domain key, shindig will check for php/config/<domain>.php for container specific configuration
urlencode($gadget['url']), // app url
$gadget['mod_id']);// mod id

  
  $gadget_url_params = array();
  parse_str(parse_url($gadget['url'], PHP_URL_QUERY), $gadget_url_params);
  
  $iframe_url = PartuzaConfig::get('gadget_server') . '/gadgets/ifr?' . "synd=" . PartuzaConfig::get('container') . "&container=" . PartuzaConfig::get('container') . "&viewer=" . (isset($_SESSION['id']) ? $_SESSION['id'] : '0') . "&owner=" . (isset($vars['person']['id']) ? $vars['person']['id'] : '0') . "&aid=" . $gadget['id'] . "&mid=" . $gadget['mod_id'] . ((isset($_GET['nocache']) && $_GET['nocache'] == '1') || (isset($gadget_url_params['nocache']) && intval($gadget_url_params['nocache']) == 1) || isset($_GET['bpc']) && $_GET['bpc'] == '1' ? "&nocache=1" : '') . "&country=US" . "&lang=en" . "&view=" . $view . "&parent=" . urlencode("http://" . $_SERVER['HTTP_HOST']) . $prefs . (isset($_GET['appParams']) ? '&view-params=' . urlencode($_GET['appParams']) : '') . "&st=" . urlencode(base64_encode($securityToken->toSerialForm())) . "&v=" . $gadget['version'] . "&url=" . urlencode($gadget['url']) . "#rpctoken=" . rand(0, getrandmax());
  
  ?><div class="gadgets-gadget-chrome" style="width:<?=$width?>px">
<div id="gadgets-gadget-title-bar-<?=$gadget['mod_id']?>"
	class="gadgets-gadget-title-bar">
<div class="gadgets-gadget-title-button-bar">
		<?
  if ($view != 'preview' && isset($_SESSION['id']) && $_SESSION['id'] == $vars['person']['id']) {
    if (is_object(unserialize($gadget['settings']))) {
      ?>
			<a
	href="<?=PartuzaConfig::get('web_prefix');?>/profile/appsettings/<?=$gadget['id']?>/<?=$gadget['mod_id']?>"
	class="gadgets-gadget-title-button">Settings</a>
		<?
    }
  } else {
    ?>
			<a
	href="<?=PartuzaConfig::get('web_prefix');?>/profile/addapp?appUrl=<?=urlencode($gadget['url'])?>"
	class="gadgets-gadget-title-button">Add application to your profile</a>
		<?
  }
  ?> 
		</div>
<span id="remote_iframe_<?=$gadget['mod_id']?>_title"
	class="gadgets-gadget-title"><?=! empty($gadget['directory_title']) ? $gadget['directory_title'] : $gadget['title']?></span>
</div>
<div class="gadgets-gadget-content"><iframe width="<?=($width - 6)?>"
	scrolling="<?=$gadget['scrolling'] || $gadget['scrolling'] == 'true' ? 'yes' : 'no'?>"
	height="<?=! empty($gadget['height']) ? $gadget['height'] : '200'?>"
	frameborder="no" src="<?=$iframe_url?>" class="gadgets-gadget"
	name="remote_iframe_<?=$gadget['mod_id']?>"
	id="remote_iframe_<?=$gadget['mod_id']?>"></iframe></div>
</div>
<?
}
?>
