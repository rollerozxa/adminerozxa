<?php
$TABLE = $_GET["indexes"];
$index_types = ["PRIMARY", "UNIQUE", "INDEX"];
$table_status = table_status($TABLE, true);
if (preg_match('~MyISAM|M?aria' . (min_version(5.6, '10.0.5') ? '|InnoDB' : '') . '~i', $table_status["Engine"])) {
	$index_types[] = "FULLTEXT";
}
if (preg_match('~MyISAM|M?aria' . (min_version(5.7, '10.2.2') ? '|InnoDB' : '') . '~i', $table_status["Engine"])) {
	$index_types[] = "SPATIAL";
}
$indexes = indexes($TABLE);
$primary = [];

$row = $_POST;

if ($_POST && !$error && !$_POST["add"] && !$_POST["drop_col"]) {
	$alter = [];
	foreach ($row["indexes"] as $index) {
		$name = $index["name"];
		if (in_array($index["type"], $index_types)) {
			$columns = [];
			$lengths = [];
			$descs = [];
			$set = [];
			ksort($index["columns"]);
			foreach ($index["columns"] as $key => $column) {
				if ($column != "") {
					$length = $index["lengths"][$key];
					$desc = $index["descs"][$key];
					$set[] = idf_escape($column) . ($length ? "(" . (+$length) . ")" : "") . ($desc ? " DESC" : "");
					$columns[] = $column;
					$lengths[] = ($length ? $length : null);
					$descs[] = $desc;
				}
			}

			if ($columns) {
				$existing = $indexes[$name];
				if ($existing) {
					ksort($existing["columns"]);
					ksort($existing["lengths"]);
					ksort($existing["descs"]);
					if ($index["type"] == $existing["type"]
						&& array_values($existing["columns"]) === $columns
						&& (!$existing["lengths"] || array_values($existing["lengths"]) === $lengths)
						&& array_values($existing["descs"]) === $descs
					) {
						// skip existing index
						unset($indexes[$name]);
						continue;
					}
				}
				$alter[] = [$index["type"], $name, $set];
			}
		}
	}

	// drop removed indexes
	foreach ($indexes as $name => $existing) {
		$alter[] = [$existing["type"], $name, "DROP"];
	}
	if (!$alter) {
		redirect(ME . "table=" . urlencode($TABLE));
	}
	queries_redirect(ME . "table=" . urlencode($TABLE), 'Indexes have been altered.', alter_indexes($TABLE, $alter));
}

page_header('Indexes', $error, ["table" => $TABLE], h($TABLE));

$fields = array_keys(fields($TABLE));
if ($_POST["add"]) {
	foreach ($row["indexes"] as $key => $index) {
		if ($index["columns"][count($index["columns"])] != "") {
			$row["indexes"][$key]["columns"][] = "";
		}
	}
	$index = end($row["indexes"]);
	if ($index["type"] || array_filter($index["columns"], 'strlen')) {
		$row["indexes"][] = ["columns" => [1 => ""]];
	}
}
if (!$row) {
	foreach ($indexes as $key => $index) {
		$indexes[$key]["name"] = $key;
		$indexes[$key]["columns"][] = "";
	}
	$indexes[] = ["columns" => [1 => ""]];
	$row["indexes"] = $indexes;
}
?>

<form action="" method="post">
<div class="scrollable">
<table cellspacing="0" class="nowrap">
<thead><tr>
<th id="label-type">Index Type
<th>Column (length)
<th id="label-name">Name
<th><noscript><?="<input type='image' class='icon' name='add[0]' src='../adminer/static/plus.gif' alt='+' title='Add next'>"; ?></noscript>
</thead>
<?php
if ($primary) {
	echo "<tr><td>PRIMARY<td>";
	foreach ($primary["columns"] as $key => $column) {
		echo select_input(" disabled", $fields, $column);
		echo "<label><input disabled type='checkbox'>descending</label> ";
	}
	echo "<td><td>\n";
}
$j = 1;
foreach ($row["indexes"] as $index) {
	if (!$_POST["drop_col"] || $j != key($_POST["drop_col"])) {
		echo "<tr><td>" . html_select("indexes[$j][type]", [-1 => ""] + $index_types, $index["type"], ($j == count($row["indexes"]) ? "indexesAddRow.call(this);" : 1), "label-type");

		echo "<td>";
		ksort($index["columns"]);
		$i = 1;
		foreach ($index["columns"] as $key => $column) {
			echo "<span>" . select_input(
				" name='indexes[$j][columns][$i]' title='Column'",
				($fields ? array_combine($fields, $fields) : $fields),
				$column,
				"partial(" . ($i == count($index["columns"]) ? "indexesAddColumn" : "indexesChangeColumn") . ", '" . js_escape($jush == "sql" ? "" : $_GET["indexes"] . "_") . "')"
			);
			echo ($jush == "sql" || $jush == "mssql" ? "<input type='number' name='indexes[$j][lengths][$i]' class='size' value='" . h($index["lengths"][$key]) . "' title='Length'>" : "");
			echo (support("descidx") ? checkbox("indexes[$j][descs][$i]", 1, $index["descs"][$key], 'descending') : "");
			echo " </span>";
			$i++;
		}

		echo "<td><input name='indexes[$j][name]' value='" . h($index["name"]) . "' autocapitalize='off' aria-labelledby='label-name'>\n";
		echo "<td><input type='image' class='icon' name='drop_col[$j]' src='../adminer/static/cross.gif' alt='x' title='Remove'>" . script("qsl('input').onclick = partial(editingRemoveRow, 'indexes\$1[type]');");
	}
	$j++;
}
?>
</table>
</div>
<p>
<input type="submit" value="Save">
<input type="hidden" name="token" value="<?=$token; ?>">
</form>
