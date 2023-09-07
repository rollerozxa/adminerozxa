<?php
$TYPE = $_GET["type"];
$row = $_POST;

if ($_POST && !$error) {
	$link = substr(ME, 0, -1);
	if ($_POST["drop"]) {
		query_redirect("DROP TYPE " . idf_escape($TYPE), $link, 'Type has been dropped.');
	} else {
		query_redirect("CREATE TYPE " . idf_escape(trim($row["name"])) . " $row[as]", $link, 'Type has been created.');
	}
}

page_header($TYPE != "" ? 'Alter type: ' . h($TYPE) : 'Create type', $error);

if (!$row) {
	$row["as"] = "AS ";
}
?>

<form action="" method="post">
<p>
<?php
if ($TYPE != "") {
	echo "<input type='submit' name='drop' value='Drop'>" . confirm(sprintf('Drop %s?', $TYPE)) . "\n";
} else {
	echo "<input name='name' value='" . h($row['name']) . "' autocapitalize='off'>\n";
	textarea("as", $row["as"]);
	echo "<p><input type='submit' value='Save'>\n";
}
?>
<input type="hidden" name="token" value="<?=$token; ?>">
</form>
