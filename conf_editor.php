<?php

/*
 * Pheditor
 * PHP file editor
 * Hamid Samak
 * https://github.com/hamidsamak/pheditor
 * Release under MIT license
 */

define('PASSWORD', 'c7ad44cbad762a5da0a452f9e854fdc1e0e7a52a38015f23f3eab1d80b931dd472634dfac71cd34ebc35d16ab7fb8a90c81f975113d6c7538dc69dd8de9077ec');
define('EDITABLE_FORMATS', 'txt,php,htm,html,js,css,tpl,xml,md,conf');
define('LOG_FILE', __DIR__ . DIRECTORY_SEPARATOR . '.phedlog');
define('SHOW_HIDDEN_FILES', false);

if (file_exists(LOG_FILE)) {
	$log = unserialize(file_get_contents(LOG_FILE));

	if (isset($log[$_SERVER['REMOTE_ADDR']]) && $log[$_SERVER['REMOTE_ADDR']]['num'] > 3 && time() - $log[$_SERVER['REMOTE_ADDR']]['time'] < 86400)
		die('This IP address is blocked due to unsuccessful login attempts.');

	foreach ($log as $key => $value)
		if (time() - $value['time'] > 86400) {
			unset($log[$key]);

			$log_updated = true;
		}

	if (isset($log_updated))
		file_put_contents(LOG_FILE, serialize($log));
}

session_start();

if (isset($_SESSION['pheditor_admin']) === false || $_SESSION['pheditor_admin'] !== true) {
	if (isset($_POST['pheditor_password']) && empty($_POST['pheditor_password']) === false)
		if (hash('sha512', $_POST['pheditor_password']) === PASSWORD) {
			$_SESSION['pheditor_admin'] = true;

			redirect();
		} else {
			$error = 'The entry password is not correct.';

			$log = file_exists(LOG_FILE) ? unserialize(file_get_contents(LOG_FILE)) : array();

			if (isset($log[$_SERVER['REMOTE_ADDR']]) === false)
				$log[$_SERVER['REMOTE_ADDR']] = array('num' => 0, 'time' => 0);

			$log[$_SERVER['REMOTE_ADDR']]['num'] += 1;
			$log[$_SERVER['REMOTE_ADDR']]['time'] = time();

			file_put_contents(LOG_FILE, serialize($log));
		}

	die('<title>Router Simple Config</title><form method="post"><div style="text-align:center"><h1><a href="/" target="_blank" title="Router Simple Config" style="color:#444;text-decoration:none" tabindex="3">Pheditor</a></h1>' . (isset($error) ? '<p style="color:#dd0000">' . $error . '</p>' : null) . '<input id="pheditor_password" name="pheditor_password" type="password" value="" placeholder="Password&hellip;" tabindex="1"><br><br><input type="submit" value="Login" tabindex="2"></div></form><script type="text/javascript">document.getElementById("pheditor_password").focus();</script>');
}

if (isset($_GET['logout'])) {
	unset($_SESSION['pheditor_admin']);

	redirect();
}

if (isset($_POST['action'])) {
	switch ($_POST['action']) {
		case 'open':
			if (isset($_POST['file']) && file_exists(__DIR__ . DIRECTORY_SEPARATOR . $_POST['file']))
				echo br2nl(highlight_string(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . $_POST['file']), true));
			break;

		case 'save':
			$file = __DIR__ . DIRECTORY_SEPARATOR . $_POST['file'];

			if (isset($_POST['file']) && isset($_POST['data']) && is_writable($file)) {
				file_put_contents($file, $_POST['data']);
				echo br2nl(highlight_string(file_get_contents($file), true));
			}
			break;

		case 'reload':
			echo files(__DIR__);
			break;

		case 'password':
			if (isset($_POST['password']) && empty($_POST['password']) === false) {
				$contents = file(__FILE__);

				foreach ($contents as $key => $line)
					if (strpos($line, 'define(\'PASSWORD\'') !== false) {
						$contents[$key] = "define('PASSWORD', '" . hash('sha512', $_POST['password']) . "');\n";

						break;
					}

				file_put_contents(__FILE__, implode($contents));

				echo 'Password changed successfully.';
			}
			break;

		case 'delete':
			if (isset($_POST['file']) && file_exists(__DIR__ . DIRECTORY_SEPARATOR . $_POST['file']))
				unlink(__DIR__ . DIRECTORY_SEPARATOR . $_POST['file']);
			break;
	}

	exit;
}

function files($dir, $display = 'block') {
	$formats = explode(',', EDITABLE_FORMATS);

	$data = '<ul class="files" style="display:' . $display . '">';
	$files = array_slice(scandir($dir), 2);

	asort($files);

	foreach ($files as $key => $file) {
		if ($dir . DIRECTORY_SEPARATOR . $file == __FILE__ || (SHOW_HIDDEN_FILES === false && substr($file, 0, 1) === '.'))
			continue;

		$writable = is_writable($dir . DIRECTORY_SEPARATOR . $file) ? 'writable' : 'non-writable';

		if (is_dir($dir . DIRECTORY_SEPARATOR . $file))
			$data .= '<li class="dir ' . $writable . '"><a href="javascript:void(0);" onclick="return expandDir(this);" data-dir="' . str_replace(__DIR__ . '/', '', $dir . DIRECTORY_SEPARATOR . $file) . '">' . $file . '</a>' . files($dir . DIRECTORY_SEPARATOR . $file, 'none') . '</li>';
		else {
			$is_editable = strpos($file, '.') === false || in_array(substr($file, strrpos($file, '.') + 1), $formats);

			$data .= '<li class="file ' . $writable . ' ' . ($is_editable ? 'editable' : null) . '">';

			if ($is_editable === true)
				$data .= '<a href="#' . $file . '" onclick="return openFile(this);" data-file="' . str_replace(__DIR__ . '/', '', $dir . DIRECTORY_SEPARATOR . $file) . '">';

			$data .= $file;

			if ($is_editable)
				$data .= '</a>';

			if ($writable === 'writable')
				$data .= ' <a href="javascript:void(0);" class="text-red visible-on-hover" onclick="return deleteFile(this);">[Delete]</a>';

			$data .= '</li>';
		}
	}
	
	$data .= '</ul>';

	return $data;
}

function br2nl($string) {
	$string = str_replace(array("\r\n", "\r", "\n"), '', $string);
	$string = str_replace('<br />', "\n", $string);

	return $string;
}

function redirect($address = null) {
	if (empty($address))
		$address = $_SERVER['PHP_SELF'];

	header('Location: ' . $address);
	exit;
}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Router Simple Conf</title>
<style type="text/css">
body {
	margin: 0;
	padding: 0;
	color: #444;
}

a, a:visited, a:focus {
	color: #444;
	text-decoration: none;
}

a:hover {
	color: #000;
}

h1 {
	padding: 0;
	margin: 10px;
	display: inline-block;
}

h1 a {
	color: #444;
}

#top {
	border-bottom: 1px dotted #ccc;
}

header {
	width: 20%;
	float: left;
}

nav {
	width: 80%;
	float: right;
}

#status {
	float: left;
	margin-top: 15px;
}

#sidebar {
	width: 19%;
	float: left;
	overflow-y: auto;
}

#editor {
	width: 79%;
	float: right;
	padding: 10px;
	overflow-y: auto;
	white-space: pre-wrap;
	border-left: 1px dotted #ccc;
}

ul.menu {
	margin: 0;
	padding: 0;
}

ul.menu li {
	margin: 0;
	float: right;
	list-style-type: none;
	padding: 10px 10px 0 0;
}

ul.files {
	padding: 0;
	margin: 10px 30px 0 30px;
}

ul.files li {
	padding-bottom: 5px;
	list-style-type: none;
}

ul.files li.dir:before { content: "+"; margin-right: 5px; }
ul.files li.file { cursor: default; margin-left: 15px; }
ul.files li.file.editable { list-style-type: disc; margin-left: 15px; }
ul.files li.non-writable, ul.files li.non-writable a { color: #990000; }

.text-red, .text-red:hover {
	color: #dd0000;
}

.visible-on-hover {
	visibility: hidden;
}

li.file:hover .visible-on-hover {
	visibility: visible;
}

@media screen and (max-width: 1000px) {
	#status {
		margin-left: 10px;
	}

	#sidebar {
		width: auto;
		float: none;
	}

	#editor {
		width: auto;
		float: none;
		border-left: 0;
		border-top: 1px dotted #ccc;
	}
}
</style>
<script type="text/javascript">
var expandedDirs = [];

function id(id) {
	return document.getElementById(id);
}

function expandDir(element) {
	var ul = element.nextSibling;
	var dir = element.getAttribute("data-dir");
	
	if (ul.style.display == "none") {
		ul.style.display = "block";

		expandedDirs.push(dir);
	} else {
		ul.style.display = "none";

		for (var i in expandedDirs)
			if (expandedDirs[i] == dir)
				expandedDirs.splice(i, 1);
	}

	document.cookie = "phedExpDirs=" + expandedDirs.join("|");
}

function openFile(element) {
	var editor = id("editor");
	var file = element.getAttribute("data-file");

	editor.setAttribute("contenteditable", "false");

	var xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if (xhttp.readyState == 4 && xhttp.status == 200) {
			editor.innerHTML = xhttp.responseText;
			editor.setAttribute("data-file", file);
			editor.setAttribute("contenteditable", element.parentNode.className.indexOf("non-writable") < 0);

			id("save").setAttribute("disabled", "");
			id("close").removeAttribute("disabled");

			id("status").innerHTML = file;
		}
	}
	xhttp.open("POST", "<?=$_SERVER['PHP_SELF']?>", true);
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send("action=open&file=" + encodeURIComponent(file));
}

function saveFile() {
	var newFile;
	var editor = id("editor");
	var file = editor.getAttribute("data-file");

	editor.setAttribute("contenteditable", "false");
	editor.innerHTML = editor.innerHTML.replace(/<br(\s*)\/*>/ig, "\n");

	if (file.length < 1) {
		newFile = true;
		file = prompt("Please enter file name with full path", "new-file.php");
	} else
		newFile = false;

	if (file != null && file.length > 0) {
		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
			if (xhttp.readyState == 4 && xhttp.status == 200) {
				var save = id("save");

				editor.setAttribute("contenteditable", "true");
				save.focus();
				editor.focus();

				editor.innerHTML = xhttp.responseText;
				editor.focus();

				save.setAttribute("disabled", "");
				reloadFiles();

				if (newFile == true) {
					id("status").innerHTML = file;
					editor.setAttribute("data-file", file);
				}
			}
		}
		xhttp.open("POST", "<?=$_SERVER['PHP_SELF']?>", true);
		xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhttp.send("action=save&file=" + encodeURIComponent(file) + "&data=" + encodeURIComponent(editor.textContent));
	} else {
		editor.setAttribute("contenteditable", "true");
		editor.focus();
	}
}

function reloadFiles() {
	var xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if (xhttp.readyState == 4 && xhttp.status == 200) {
			id("sidebar").innerHTML = xhttp.responseText;

			var dirs = id("sidebar").getElementsByTagName("a");

			for (var i = 0; i < dirs.length; i++)
				if (dirs[i].hasAttribute("data-dir") && dirs[i].getAttribute("data-dir"))
					for (var j in expandedDirs)
						if (dirs[i].getAttribute("data-dir") == expandedDirs[j]) {
							dirs[i].click();

							break;
						}
		}
	}
	xhttp.open("POST", "<?=$_SERVER['PHP_SELF']?>", true);
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send("action=reload");
}

function closeFile() {
	var save = id("save");
	var editor = id("editor");

	if (save.hasAttribute("disabled") == false && confirm("Discard changes?") == false)
		return false;

	editor.innerHTML = "";
	editor.setAttribute("data-file", "");
	editor.setAttribute("contenteditable", "true");

	save.setAttribute("disabled", "");
	id("close").setAttribute("disabled", "");

	id("status").innerHTML = "";
	window.location.hash = "";
}

function editorChange(event) {
	if (event.ctrlKey == false)
		id("save").removeAttribute("disabled");
}

function editorFocus(event) {
	var editor = id("editor");

	editor.innerHTML = escapeHtml(editor.textContent);
}

function changePassword() {
	var password = prompt("Please enter new password:");

	if (password != null && password.length > 0) {
		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
			if (xhttp.readyState == 4 && xhttp.status == 200)
				alert(xhttp.responseText);
		}
		xhttp.open("POST", "<?=$_SERVER['PHP_SELF']?>", true);
		xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhttp.send("action=password&password=" + password);
	}
}

function deleteFile(element) {
	if (confirm("Are you sure to delete this file?") != true)
		return false;

	var file = element.previousSibling.previousSibling.getAttribute("data-file");

	if (file != null && file.length > 0) {
		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
			if (xhttp.readyState == 4 && xhttp.status == 200) {
				reloadFiles();
			}
		}
		xhttp.open("POST", "<?=$_SERVER['PHP_SELF']?>", true);
		xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhttp.send("action=delete&file=" + encodeURIComponent(file));
	}
}

function escapeHtml(string) {
	var map = {"&": "&amp;", "<": "&lt;", ">": "&gt;", "\"": "&quot;", "'": "&#039;" };

	return string.replace(/[&<>""]/g, function(index) { return map[index]; });
}

window.onload = function() {
	window.onresize = function() {
		if (window.innerWidth <= 1000) {
			id("sidebar").style.height = "";
			id("editor").style.height = "";
			id("editor").style.minHeight = "100px";
		} else {
			id("sidebar").style.height = (window.innerHeight - id("top").clientHeight - 5) + "px";
			id("editor").style.height = (window.innerHeight - 25 - id("top").clientHeight) + "px";
		}
	}

	window.onresize();

	id("save").setAttribute("disabled", "");
	id("close").setAttribute("disabled", "");

	var dirs = id("sidebar").getElementsByTagName("a");
	var cookie = document.cookie.split(";");
	for (var i in cookie)
		if (cookie[i].indexOf("phedExpDirs=") > -1) {
			expandedDirs = cookie[i].substring(cookie[i].indexOf("=") + 1).split("|");

			break;
		}

	for (var i = 0; i < dirs.length; i++)
		if (dirs[i].hasAttribute("data-dir"))
			for (var j in expandedDirs)
				if (dirs[i].getAttribute("data-dir") == expandedDirs[j])
					dirs[i].nextSibling.style.display = "block";

	if (window.location.hash.length > 1) {
		var hash = window.location.hash;
		var files = id("sidebar").getElementsByTagName("a");

		for (i in files)
			if (files[i].hasAttribute("data-file") && files[i].getAttribute("data-file") == hash.substring(1)) {
				files[i].click();

				break;
			}
	}
}

document.onkeydown = function(event) {
	if (event.ctrlKey == true)
		if (event.keyCode == 83) {
			event.preventDefault();

			id("save").click();
		} else if (event.keyCode == 87) {
			event.preventDefault();

			id("close").click();
		}
}
</script>
</head>
<body>

<div id="top">
	<header>
		<h1><a href="/" target="_blank" title="Router Simple Config">Router Simple Config</a></h1>
                <span><a href="javascript:void(0);" onclick="return changePassword();">[Password]</a> &nbsp; 
                <a href="<?=$_SERVER['PHP_SELF']?>?logout=1">[Logout]</a></span>
                <a href="reboot.php">[Reboot Router]</a></span>
	</header>

	<nav>
		<div id="status"></div>

		<ul class="menu">
			<li><button id="close" onclick="return closeFile();" disabled>Close</button></li>
			<li><button id="save" onclick="return saveFile();" disabled>Save</button></li>
		</ul>
	</nav>

	<div style="clear:both"></div>
</div>

<div>
	<div id="sidebar"><?=files(__DIR__)?></div>
	<div id="editor" data-file="" contenteditable="true" onkeydown="return editorChange(event);" onfocus="return editorFocus(event);"></div>
</div>

</body>
</html>
