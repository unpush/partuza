<?php
$this->template('/common/header.php');
?>
<div id="profileInfo" class="blue">
<?php
$this->template('profile/profile_info.php', $vars);
?>
</div>
<div id="profileContentWide">
	<p>
	Not yet, but maybe soon since this is now part of OpenSocial 0.9, see:<br />
	<a href="http://wiki.opensocial.org/index.php?title=Albums_API" target="_blank">http://wiki.opensocial.org/index.php?title=Albums_API</a>
	</p>
</div>
<?php
$this->template('/common/footer.php');
?>