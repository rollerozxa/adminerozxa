<?php
$status = isset($_GET["status"]);
page_header($status ? 'Status' : 'Variables');

$variables = ($status ? show_status() : show_variables());
if (!$variables) {
	echo "<p class='message'>No rows.\n";
} else {
	echo "<table cellspacing='0'>\n";
	foreach ($variables as $key => $val) {
		echo "<tr>";
		echo "<th><code class='jush-" . $jush . ($status ? "status" : "set") . "'>" . h($key) . "</code>";
		echo "<td>" . h($val);
	}
	echo "</table>\n";
}
