<?php
$row = $_POST;

if ($_POST && !$error) {
	$link = preg_replace('~ns=[^&]*&~', '', ME) . "ns=";
	if ($_POST["drop"]) {
		query_redirect("DROP SCHEMA " . idf_escape($_GET["ns"]), $link, 'Schema has been dropped.');
	} else {
		$name = trim($row["name"]);
		$link .= urlencode($name);
		if ($_GET["ns"] == "") {
			query_redirect("CREATE SCHEMA " . idf_escape($name), $link, 'Schema has been created.');
		} elseif ($_GET["ns"] != $name) {
			query_redirect("ALTER SCHEMA " . idf_escape($_GET["ns"]) . " RENAME TO " . idf_escape($name), $link, 'Schema has been altered.'); //! sp_rename in MS SQL
		} else {
			redirect($link);
		}
	}
}

page_header($_GET["ns"] != "" ? 'Alter schema' : 'Create schema', $error);

if (!$row) {
	$row["name"] = $_GET["ns"];
}
?>

<form action="" method="post">
<p><input name="name" id="name" value="<?=h($row["name"]) ?>" autocapitalize="off">
<?=script("focus(qs('#name'));") ?>
<input type="submit" value="Save">
<?php
if ($_GET["ns"] != "") {
	echo "<input type='submit' name='drop' value='Drop'>" . confirm(sprintf('Drop %s?', $_GET["ns"])) . "\n";
}
?>
<input type="hidden" name="token" value="<?=$token; ?>">
</form>
