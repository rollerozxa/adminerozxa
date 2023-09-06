<?php
$TABLE = $_GET["view"];
$row = $_POST;
$orig_type = "VIEW";
if ($jush == "pgsql" && $TABLE != "") {
	$status = table_status($TABLE);
	$orig_type = strtoupper($status["Engine"]);
}

if ($_POST && !$error) {
	$name = trim($row["name"]);
	$as = " AS\n$row[select]";
	$location = ME . "table=" . urlencode($name);
	$message = lang('View has been altered.');

	$type = ($_POST["materialized"] ? "MATERIALIZED VIEW" : "VIEW");

	if (!$_POST["drop"] && $TABLE == $name && $jush != "sqlite" && $type == "VIEW" && $orig_type == "VIEW") {
		query_redirect(($jush == "mssql" ? "ALTER" : "CREATE OR REPLACE") . " VIEW " . table($name) . $as, $location, $message);
	} else {
		$temp_name = $name . "_adminer_" . uniqid();
		drop_create(
			"DROP $orig_type " . table($TABLE),
			"CREATE $type " . table($name) . $as,
			"DROP $type " . table($name),
			"CREATE $type " . table($temp_name) . $as,
			"DROP $type " . table($temp_name),
			($_POST["drop"] ? substr(ME, 0, -1) : $location),
			lang('View has been dropped.'),
			$message,
			lang('View has been created.'),
			$TABLE,
			$name
		);
	}
}

if (!$_POST && $TABLE != "") {
	$row = view($TABLE);
	$row["name"] = $TABLE;
	$row["materialized"] = ($orig_type != "VIEW");
	if (!$error) {
		$error = error();
	}
}

page_header(($TABLE != "" ? lang('Alter view') : lang('Create view')), $error, array("table" => $TABLE), h($TABLE));
?>

<form action="" method="post">
<p><?=lang('Name'); ?>: <input name="name" value="<?=h($row["name"]) ?>" data-maxlength="64" autocapitalize="off">
<?=(support("materializedview") ? " " . checkbox("materialized", 1, $row["materialized"], lang('Materialized view')) : "") ?>
<p><?php textarea("select", $row["select"]); ?>
<p>
<input type="submit" value="<?=lang('Save') ?>">
<?php if ($TABLE != "") { ?><input type="submit" name="drop" value="<?=lang('Drop'); ?>"><?=confirm(lang('Drop %s?', $TABLE)) ?><?php } ?>
<input type="hidden" name="token" value="<?=$token ?>">
</form>
