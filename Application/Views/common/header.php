<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link href="/css/common.css" rel="stylesheet" type="text/css">
	<link rel="stylesheet" href="/css//gadgets.css" type="text/css">
	<script type="text/javascript" src="<?=Config::get('gadget_server')?>/gadgets/js/rpc.js?c=1"></script>
	<script type="text/javascript" src="<?=Config::get('gadget_server')?>/gadgets/files/container/cookies.js"></script>
	<script type="text/javascript" src="<?=Config::get('gadget_server')?>/gadgets/files/container/util.js"></script>  
	<script type="text/javascript" src="<?=Config::get('gadget_server')?>/gadgets/files/container/gadgets.js"></script>
	<script type="text/javascript" src="<?=Config::get('gadget_server')?>/gadgets/files/container/cookiebaseduserprefstore.js"></script>
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
	<span id="menuDiv"><a href="/home">Home</a><?=isset($_SESSION['username']) ? " | <a href=\"/profile/{$_SESSION['id']}\">Profile</a>" : '' ?></span>
</div>
<div id="contentDiv">