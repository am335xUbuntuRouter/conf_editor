<h1>Router Status</h1>
<br>
<br>

<a href="/setup.php">Edit Settings</a>

<br>
<h3> Status </h3>
<?php
$output = shell_exec('date');
echo "<pre>$output</pre>";


$output = shell_exec('cat /etc/issue');
echo "<pre>Linux arm 3.2.0</pre>";
echo "<pre>$output</pre>";

$output = shell_exec('uptime');
echo "<pre>$output</pre>";

$output = shell_exec('free -m');
echo "<pre>$output</pre>";
echo "<pre> </pre>";

$output = shell_exec('sudo ifconfig');
echo "<pre>$output</pre>";
echo "<pre> </pre>";



?>

<h3> Processes </h3>
<?php
$output = shell_exec('ps aux');
echo "<pre>$output</pre>";
echo "<pre> </pre>";



?>



