<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link href="/css/container.css" rel="stylesheet" type="text/css">
	<!--  compressed with java -jar ~/yuicompressor-2.3.5.jar -o {$file}-min.js {$file}.js -->
	<script type="text/javascript" src="/js/rpc-min.js"></script>
	<script type="text/javascript" src="/js/prototype-1.6.0.2-min.js"></script>
	<script type="text/javascript" src="/js/container.js"></script>  
</head>
<body>
<div id="headerDiv">
	<div id="userMenuDiv">
	<? if (isset($_SESSION['username'])) { ?>
	<div id="searchMenuDiv"><form method="get" action="/search"> | <label for="search_q">search</label> <input type="text" id="search_q" name="q"> <input type="submit" value="Go" /></form></div>
<?			echo "		<a href=\"/profile/{$_SESSION['id']}\">{$_SESSION['email']}</a> | <a href=\"/logout\">logout.</a>\n";
		} else {
			echo "		<form method=\"post\" action=\"{$_SERVER['REQUEST_URI']}\"><a href=\"/register\" >register</a>, or login with <label for=\"email\">e-mail</label> <input type=\"text\" name=\"email\" id=\"email\" /> and <label for=\"password\">password</label> <input type=\"password\" name=\"password\" id=\"password\" /> <input class=\"button\" type=\"submit\" value=\"Go\" /></form>&nbsp;\n";
		}
	?>
	</div>
	<span id="menuDiv"><a href="/home">Home</a><?=isset($_SESSION['username']) ? " | <a href=\"/profile/{$_SESSION['id']}\">Profile</a> | <a href=\"/profile/myapps\">Applications</a>" : '' ?></span>
</div>
<div id="contentDiv">