<?php
function adminer_object() {
	include_once "../plugins/plugin.php";
	include_once "../plugins/designs.php";
	$designs = [];
	foreach (glob("../designs/*", GLOB_ONLYDIR) as $filename) {
		$designs["$filename/adminer.css"] = basename($filename);
	}
	return new AdminerPlugin([
		new AdminerDesigns($designs),
	]);
}

include "./index.php";
