<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<title>Partuza!</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link href="/css/container.css?v=3" rel="stylesheet" type="text/css">
	<!--  compressed with java -jar {$path}/yuicompressor-2.3.5.jar -o {$file}-min.js {$file}.js -->
	<script type="text/javascript" src="/js/rpc-min.js"></script>
	<script type="text/javascript" src="/js/prototype-1.6.0.2-min.js"></script>
	<script type="text/javascript" src="/js/tabs-min.js"></script>
	<script type="text/javascript" src="/js/container.js"></script>  
</head>
<body>
<div id="headerDiv">
	<? if (isset($_SESSION['username'])) { ?>
	<div id="searchDiv">
		<form method="get" action="/search"> | <label for="search_q">search</label> <input type="text" id="search_q" name="q"> <input class="button" type="submit" value="Go" /></form>
	</div>
	<? } ?>
	<div id="userMenuDiv" <?=!isset($_SESSION['username'])? ' style="margin-right:12px"' : ''?>>
		<? if (isset($_SESSION['username'])) {
			echo "<a href=\"/home\">home</a> | <a href=\"/profile/{$_SESSION['id']}\">profile</a> | <a href=\"/profile/myapps\">applications</a> | <a href=\"/logout\">logout</a>&nbsp;";
		} else {
			echo "<form method=\"post\" action=\"{$_SERVER['REQUEST_URI']}\"><a href=\"/register\" >register</a>, or login with <label for=\"email\">e-mail</label> <input type=\"text\" name=\"email\" id=\"email\" /> and <label for=\"password\">password</label> <input type=\"password\" name=\"password\" id=\"password\" /> <input class=\"button\" type=\"submit\" value=\"Go\" /></form>&nbsp;\n";
		}
		?>	
	</div>
	<span id="headerLogo">
		<a href="/home">Partuza!</a>
	</span>
</div>
<div id="contentDiv">