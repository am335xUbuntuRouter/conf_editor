<?php
$output = shell_exec('/root/router_scripts/apply_settings.sh');
shell_exec('sudo reboot');
echo "<pre>$output</pre>";
?>
