<? $this->template('/common/header.php'); ?>
<img src="images/partuza-home.jpg" align="left" /> 
<div style="float:right; width: 500px;">
<p>Welcome to the example Social Network Site: Partuza!</p>
<p>Please login by entering your email and password in the top or <a href="<?php echo Config::get('web_prefix') ?>/register">register</a> to continue.</p>
</div>

<? $this->template('/common/footer.php'); ?>
