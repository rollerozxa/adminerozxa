<?php
$TABLE = $_GET["trigger"];
$name = $_GET["name"];
$trigger_options = trigger_options();
$row = (array) trigger($name, $TABLE) + array("Trigger" => $TABLE . "_bi");

if ($_POST) {
	if (!$error && in_array($_POST["Timing"], $trigger_options["Timing"]) && in_array($_POST["Event"], $trigger_options["Event"]) && in_array($_POST["Type"], $trigger_options["Type"])) {
		// don't use drop_create() because there may not be more triggers for the same action
		$on = " ON " . table($TABLE);
		$drop = "DROP TRIGGER " . idf_escape($name) . ($jush == "pgsql" ? $on : "");
		$location = ME . "table=" . urlencode($TABLE);
		if ($_POST["drop"]) {
			query_redirect($drop, $location, lang('Trigger has been dropped.'));
		} else {
			if ($name != "") {
				queries($drop);
			}
			queries_redirect(
				$location,
				($name != "" ? lang('Trigger has been altered.') : lang('Trigger has been created.')),
				queries(create_trigger($on, $_POST))
			);
			if ($name != "") {
				queries(create_trigger($on, $row + array("Type" => reset($trigger_options["Type"]))));
			}
		}
	}
	$row = $_POST;
}

page_header(($name != "" ? lang('Alter trigger') . ": " . h($name) : lang('Create trigger')), $error, array("table" => $TABLE));
?>
<form action="" method="post" id="form">
<table cellspacing="0" class="layout">
<tr><th><?=lang('Time'); ?><td><?=html_select("Timing", $trigger_options["Timing"], $row["Timing"], "triggerChange(/^" . preg_quote($TABLE, "/") . "_[ba][iud]$/, '" . js_escape($TABLE) . "', this.form);") ?>
<tr><th><?=lang('Event'); ?><td><?=html_select("Event", $trigger_options["Event"], $row["Event"], "this.form['Timing'].onchange();") ?>
<?=(in_array("UPDATE OF", $trigger_options["Event"]) ? " <input name='Of' value='" . h($row["Of"]) . "' class='hidden'>": "") ?>
<tr><th><?=lang('Type'); ?><td><?=html_select("Type", $trigger_options["Type"], $row["Type"]) ?>
</table>
<p><?=lang('Name'); ?>: <input name="Trigger" value="<?=h($row["Trigger"]) ?>" data-maxlength="64" autocapitalize="off">
<?=script("qs('#form')['Timing'].onchange();") ?>
<p><?php textarea("Statement", $row["Statement"]); ?>
<p>
<input type="submit" value="<?=lang('Save') ?>">
<?php if ($name != "") { ?><input type="submit" name="drop" value="<?=lang('Drop'); ?>"><?=confirm(lang('Drop %s?', $name)) ?><?php } ?>
<input type="hidden" name="token" value="<?=$token ?>">
</form>
