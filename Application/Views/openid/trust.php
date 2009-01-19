<?php
$this->template('/common/header.php');
?>
<?php
$info = unserialize($GLOBALS['render']['info']);
$a = 1;
?>
<h1>Trust This Site?</h1>
<div class="form">
<form method="post" action="/openid/trust">
<p>Do you wish to confirm your identity (<code><a
	href="<?php
echo $info->identity?>"><?php
echo $info->identity?></a></code>)
with <code><?php
echo $info->trust_root?></code>?</p>
<input type="submit" name="trust" value="Confirm" /> <input
	type="submit" value="Do not confirm" /></form>
</div>
<?php
$this->template('/common/footer.php');
?>