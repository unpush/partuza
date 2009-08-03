<?php
$this->template('/common/header.php');
?>
<div id="profileInfo" class="blue">
<?php
$this->template('profile/profile_info.php', $vars);
?>
</div>

<div id="profileContentWide">
  <div id="albumsTab" style="position:relative">
  	<div style="position: absolute; z-index: 1000; right: 9px; top: 6px;">
      <input id="Compose" class="button" type="button" style="border: 1px solid rgb(51, 102, 204);" value="Compose"/>
    </div>
    <ul>
    	<li><a href="#album_list">Photo List</a></li>
    	<li><a href="#media_list">Media List</a></li>
    </ul>
    <div id="album_list"></div>
    <div id="media_list"></div>
  </div>
</div>
<script type="text/javascript" src="<?php echo PartuzaConfig::get('web_prefix')?>/js/albums.js"></script>
<script type="text/javascript" src="<?php echo PartuzaConfig::get('web_prefix')?>/js/ajaxupload.3.5.js"></script>
<div style="clear: both"></div>
<?php
$this->template('/common/footer.php');
?>
