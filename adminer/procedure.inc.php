<?php
$PROCEDURE = ($_GET["name"] ? $_GET["name"] : $_GET["procedure"]);
$routine = (isset($_GET["function"]) ? "FUNCTION" : "PROCEDURE");
$row = $_POST;
$row["fields"] = (array) $row["fields"];

if ($_POST && !process_fields($row["fields"]) && !$error) {
	$orig = routine($_GET["procedure"], $routine);
	$temp_name = "$row[name]_adminer_" . uniqid();
	drop_create(
		"DROP $routine " . routine_id($PROCEDURE, $orig),
		create_routine($routine, $row),
		"DROP $routine " . routine_id($row["name"], $row),
		create_routine($routine, ["name" => $temp_name] + $row),
		"DROP $routine " . routine_id($temp_name, $row),
		substr(ME, 0, -1),
		'Routine has been dropped.',
		'Routine has been altered.',
		'Routine has been created.',
		$PROCEDURE,
		$row["name"]
	);
}

page_header(($PROCEDURE != "" ? (isset($_GET["function"]) ? 'Alter function' : 'Alter procedure') . ": " . h($PROCEDURE) : (isset($_GET["function"]) ? 'Create function' : 'Create procedure')), $error);

if (!$_POST && $PROCEDURE != "") {
	$row = routine($_GET["procedure"], $routine);
	$row["name"] = $PROCEDURE;
}

$collations = get_vals("SHOW CHARACTER SET");
sort($collations);
$routine_languages = routine_languages();
?>

<form action="" method="post" id="form">
<p>Name: <input name="name" value="<?=h($row["name"]) ?>" data-maxlength="64" autocapitalize="off">
<?=($routine_languages ? 'Language' . ": " . html_select("language", $routine_languages, $row["language"]) . "\n" : ""); ?>
<input type="submit" value="Save">
<div class="scrollable">
<table cellspacing="0" class="nowrap">
<?php
edit_fields($row["fields"], $collations, $routine);
if (isset($_GET["function"])) {
	echo "<tr><td>Return type";
	edit_type("returns", $row["returns"], $collations, [], ($jush == "pgsql" ? ["void", "trigger"] : []));
}
?>
</table>
<?=script("editFields();"); ?>
</div>
<p><?php textarea("definition", $row["definition"]); ?>
<p>
<input type="submit" value="Save">
<?php if ($PROCEDURE != "") { ?><input type="submit" name="drop" value="Drop"><?=confirm(sprintf('Drop %s?', $PROCEDURE)); ?><?php } ?>
<input type="hidden" name="token" value="<?=$token; ?>">
</form>
