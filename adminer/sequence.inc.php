<?php
$SEQUENCE = $_GET["sequence"];
$row = $_POST;

if ($_POST && !$error) {
	$link = substr(ME, 0, -1);
	$name = trim($row["name"]);
	if ($_POST["drop"]) {
		query_redirect("DROP SEQUENCE ".idf_escape($SEQUENCE), $link, 'Sequence has been dropped.');
	} elseif ($SEQUENCE == "") {
		query_redirect("CREATE SEQUENCE ".idf_escape($name), $link, 'Sequence has been created.');
	} elseif ($SEQUENCE != $name) {
		query_redirect("ALTER SEQUENCE ".idf_escape($SEQUENCE)." RENAME TO ".idf_escape($name), $link, 'Sequence has been altered.');
	} else {
		redirect($link);
	}
}

page_header($SEQUENCE != "" ? "Alter sequence: " . h($SEQUENCE) : 'Create sequence', $error);

if (!$row) {
	$row["name"] = $SEQUENCE;
}
?>

<form action="" method="post">
<p><input name="name" value="<?=h($row["name"]) ?>" autocapitalize="off">
<input type="submit" value="Save">
<?php
if ($SEQUENCE != "") {
	echo "<input type='submit' name='drop' value='Drop'>" . confirm(sprintf('Drop %s?', $SEQUENCE)) . "\n";
}
?>
<input type="hidden" name="token" value="<?=$token; ?>">
</form>
