<?php
$TABLE = $_GET["trigger"];
$name = $_GET["name"];
$trigger_options = trigger_options();
$row = (array) trigger($name, $TABLE) + ["Trigger" => $TABLE . "_bi"];

if ($_POST) {
	if (!$error && in_array($_POST["Timing"], $trigger_options["Timing"]) && in_array($_POST["Event"], $trigger_options["Event"]) && in_array($_POST["Type"], $trigger_options["Type"])) {
		// don't use drop_create() because there may not be more triggers for the same action
		$on = " ON " . table($TABLE);
		$drop = "DROP TRIGGER " . idf_escape($name) . ($jush == "pgsql" ? $on : "");
		$location = ME . "table=" . urlencode($TABLE);
		if ($_POST["drop"]) {
			query_redirect($drop, $location, 'Trigger has been dropped.');
		} else {
			if ($name != "") {
				queries($drop);
			}
			queries_redirect(
				$location,
				($name != "" ? 'Trigger has been altered.' : 'Trigger has been created.'),
				queries(create_trigger($on, $_POST))
			);
			if ($name != "") {
				queries(create_trigger($on, $row + ["Type" => reset($trigger_options["Type"])]));
			}
		}
	}
	$row = $_POST;
}

page_header(($name != "" ? 'Alter trigger: ' . h($name) : 'Create trigger'), $error, ["table" => $TABLE]);
?>
<form action="" method="post" id="form">
<table cellspacing="0" class="layout">
<tr><th>Time<td><?=html_select("Timing", $trigger_options["Timing"], $row["Timing"], "triggerChange(/^" . preg_quote($TABLE, "/") . "_[ba][iud]$/, '" . js_escape($TABLE) . "', this.form);") ?>
<tr><th>Event<td><?=html_select("Event", $trigger_options["Event"], $row["Event"], "this.form['Timing'].onchange();") ?>
<?=(in_array("UPDATE OF", $trigger_options["Event"]) ? " <input name='Of' value='" . h($row["Of"]) . "' class='hidden'>": "") ?>
<tr><th>Type<td><?=html_select("Type", $trigger_options["Type"], $row["Type"]) ?>
</table>
<p>Name: <input name="Trigger" value="<?=h($row["Trigger"]) ?>" data-maxlength="64" autocapitalize="off">
<?=script("qs('#form')['Timing'].onchange();"); ?>
<p><?php textarea("Statement", $row["Statement"]); ?>
<p>
<input type="submit" value="Save">
<?php if ($name != "") { ?><input type="submit" name="drop" value="Drop"><?=confirm(sprintf('Drop %s?', $name)); ?><?php } ?>
<input type="hidden" name="token" value="<?=$token; ?>">
</form>
