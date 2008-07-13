<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<title>Partuza!</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link href="/css/container.css?v=4" rel="stylesheet" type="text/css">
	<!--  compressed with java -jar {$path}/yuicompressor-2.3.5.jar -o {$file}-min.js {$file}.js -->
	<script type="text/javascript" src="<?=Config::get('gadget_server')?>/gadgets/js/rpc.js?c=1"></script>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/prototype/1.6.0.2/prototype.js"></script>
	<script type="text/javascript" src="/js/tabs-min.js"></script>
	<script type="text/javascript" src="/js/container.js"></script>
	<meta http-equiv="X-XRDS-Location" content="http://<?php echo $_SERVER['HTTP_HOST']; ?>/xrds"/> 
</head>
<body>
<div id="headerDiv">
	<? if (isset($_SESSION['username'])) { ?>
	<div id="searchDiv">
		<form method="get" action="<?=Config::get('web_prefix')?>/search"> | <label for="search_q">search</label> <input type="text" id="search_q" name="q"> <input class="button" type="submit" value="Go" /></form>
	</div>
	<? } ?>
	<div id="userMenuDiv" <?=!isset($_SESSION['username'])? ' style="margin-right:12px"' : ''?>>
		<? if (isset($_SESSION['username'])) {
			echo "<a href=\"".Config::get('web_prefix') ."/home\">home</a> | <a href=\"".Config::get('web_prefix') ."/profile/{$_SESSION['id']}\">profile</a> | <a href=\"".Config::get('web_prefix') ."/logout\">logout</a>&nbsp;";
		} else {
			echo "<form method=\"post\" action=\"{$_SERVER['REQUEST_URI']}\"><a href=\"".Config::get('web_prefix') ."/register\" >register</a>, or login with <label for=\"email\">e-mail</label> <input type=\"text\" name=\"email\" id=\"email\" /> and <label for=\"password\">password</label> <input type=\"password\" name=\"password\" id=\"password\" /> <input class=\"button\" type=\"submit\" value=\"Go\" /></form>&nbsp;\n";
		}
		?>	
	</div>
	<span id="headerLogo">
		<a href="/home">Partuza!</a>
	</span>
</div>
<div id="contentDiv">
