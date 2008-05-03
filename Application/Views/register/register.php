<? $this->template('/common/header.php'); ?>
<div class="blue">
	<? if (!empty($vars['error'])) { ?>
		<div style="color:red"><b>Error : <?=$vars['error']?></b><br /></div>
	<? } ?>
	<form action="/register" method="post" id="register">
		<div><b>Account information</b></div>
		<div><div class="label"><label for="register_email">email</label></div><input type="text" value="<?=isset($_POST['register_email']) ? $_POST['register_email'] : ''?>" name="register_email" id="register_email" /></div>
		<div><div class="label"><label for="register_password">password</label></div><input type="password" value="<?=isset($_POST['register_password']) ? $_POST['register_password'] : ''?>" name="register_password" id="register_password" /></div>
		<br />
		<div><b>Name</b></div>
		<div><div class="label"><label for="register_first_name">given (first) name</label></div><input type="text" value="<?=isset($_POST['register_first_name']) ? $_POST['register_first_name'] : ''?>" name="register_first_name" id="register_first_name" /></div>
		<div><div class="label"><label for="register_last_name">family (late) name</label></div><input type="text" value="<?=isset($_POST['register_last_name']) ? $_POST['register_last_name'] : ''?>" name="register_last_name" id="register_last_name" /></div>
		<br />
		<div><input type="submit" value="Register" /></div>
	</form>
</div>
<? $this->template('/common/footer.php'); ?>