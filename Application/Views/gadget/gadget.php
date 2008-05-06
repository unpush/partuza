<?php
if (!empty($vars['gadget']['error'])) {
	echo "<b>{$vars['gadget']['error']}</b>";
} else {
	$width = $vars['width'];
	$gadget = $vars['gadget'];
	$view = $vars['view'];
	
	$prefs = '';
	foreach ($gadget['user_prefs'] as $name => $value) {
		if (!empty($value)) {
			$prefs .= '&up_'.urlencode($name).'='.urlencode($value);
		}
	}
	
	$securityToken = BasicGadgetToken::createFromValues(
		isset($vars['person']['id']) ? $vars['person']['id'] : '0',	// owner
		(isset($_SESSION['id']) ? $_SESSION['id'] : '0'),			// viewer
		$gadget['id'],												// app id
		$_SERVER['HTTP_HOST'],										// domain
		urlencode($gadget['url']),									// app url
		$gadget['mod_id']											// mod id
		);
	
	$iframe_url = 
		Config::get('gadget_server').'/gadgets/ifr?'.
		"synd=default".
		"&viewer=".(isset($_SESSION['id']) ? $_SESSION['id'] : '0').
		"&owner=".(isset($vars['person']['id']) ? $vars['person']['id'] : '0').
		"&aid=".$gadget['mod_id'].
		"&mid=".$gadget['mod_id'].
		((isset($_GET['nocache']) && $_GET['nocache'] == '1') || isset($_GET['bpc']) && $_GET['bpc'] == '1' ? "&nocache=1" : '').
		"&country=US".
		"&lang=EN".
		"&view=".$view.
		"&parent=".urlencode("http://".$_SERVER['HTTP_HOST']).
		$prefs.
		"&st=".$securityToken->toSerialForm().
		"&v=".$gadget['version'].
		"&url=".urlencode($gadget['url']).
		"#rpctoken=".rand(0,getrandmax());
			
?><div class="gadgets-gadget-chrome" style="width:<?=$width?>px">
	<div id="gadgets-gadget-title-bar-<?=$gadget['mod_id']?>" class="gadgets-gadget-title-bar">
		<div class="gadgets-gadget-title-button-bar">
		<? if (isset($_SESSION['id']) && $_SESSION['id'] == $vars['person']['id']) { if (is_object(unserialize($gadget['settings']))) { ?>
			<a href="/profile/appsettings/<?=$gadget['id']?>/<?=$gadget['mod_id']?>" class="gadgets-gadget-title-button">Settings</a>
		<? } } else { ?>
			<a href="/profile/addapp?appUrl=<?=urlencode($gadget['url'])?>" class="gadgets-gadget-title-button">Add application to your profile</a>
		<? } ?> 
		</div>
		<span id="remote_iframe_<?=$gadget['mod_id']?>_title" class="gadgets-gadget-title"><?=!empty($gadget['directory_title']) ? $gadget['directory_title'] : $gadget['title']?></span>
	</div>
	<div class="gadgets-gadget-content">
		<iframe width="<?=($width - 6)?>" scrolling="<?=$gadget['scrolling'] ? 'yes' : 'no'?>" height="<?=!empty($gadget['height'])?$gadget['height']:'200'?>" frameborder="no" src="<?=$iframe_url?>" class="gadgets-gadget" name="remote_iframe_<?=$gadget['mod_id']?>" id="remote_iframe_<?=$gadget['mod_id']?>"></iframe>
	</div>
</div>
<? 
}
?>