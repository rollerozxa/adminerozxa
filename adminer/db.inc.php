<?php
$tables_views = array_merge((array) $_POST["tables"], (array) $_POST["views"]);

if ($tables_views && !$error && !$_POST["search"]) {
	$result = true;
	$message = "";
	if ($jush == "sql" && $_POST["tables"] && count($_POST["tables"]) > 1 && ($_POST["drop"] || $_POST["truncate"] || $_POST["copy"])) {
		queries("SET foreign_key_checks = 0"); // allows to truncate or drop several tables at once
	}

	if ($_POST["truncate"]) {
		if ($_POST["tables"]) {
			$result = truncate_tables($_POST["tables"]);
		}
		$message = 'Tables have been truncated.';
	} elseif ($_POST["move"]) {
		$result = move_tables((array) $_POST["tables"], (array) $_POST["views"], $_POST["target"]);
		$message = 'Tables have been moved.';
	} elseif ($_POST["copy"]) {
		$result = copy_tables((array) $_POST["tables"], (array) $_POST["views"], $_POST["target"]);
		$message = 'Tables have been copied.';
	} elseif ($_POST["drop"]) {
		if ($_POST["views"]) {
			$result = drop_views($_POST["views"]);
		}
		if ($result && $_POST["tables"]) {
			$result = drop_tables($_POST["tables"]);
		}
		$message = 'Tables have been dropped.';
	} elseif ($jush != "sql") {
		$result = apply_queries("VACUUM" . ($_POST["optimize"] ? "" : " ANALYZE"), $_POST["tables"]);
		$message = 'Tables have been optimized.';
	} elseif (!$_POST["tables"]) {
		$message = 'No tables.';
	} elseif ($result = queries(($_POST["optimize"] ? "OPTIMIZE" : ($_POST["check"] ? "CHECK" : ($_POST["repair"] ? "REPAIR" : "ANALYZE"))) . " TABLE " . implode(", ", array_map('idf_escape', $_POST["tables"])))) {
		while ($row = $result->fetch_assoc()) {
			$message .= "<b>" . h($row["Table"]) . "</b>: " . h($row["Msg_text"]) . "<br>";
		}
	}

	queries_redirect(substr(ME, 0, -1), $message, $result);
}

page_header(($_GET["ns"] == "" ? "Database: " . h(DB) : "Schema: " . h($_GET["ns"])), $error, true);

if ($adminer->homepage()) {
	if ($_GET["ns"] !== "") {
		echo "<h3 id='tables-views'>Tables and views</h3>\n";
		$tables_list = tables_list();
		if (!$tables_list) {
			echo "<p class='message'>No tables.\n";
		} else {
			echo "<form action='' method='post'>\n";
			if (support("table")) {
				echo "<fieldset><legend>Search data in tables <span id='selected2'></span></legend><div>";
				echo "<input type='search' name='query' value='" . h($_POST["query"]) . "'>";
				echo script("qsl('input').onkeydown = partialArg(bodyKeydown, 'search');", "");
				echo " <input type='submit' name='search' value='Search'>\n";
				if ($adminer->operator_regexp !== null) {
					echo "<p><label><input type='checkbox' name='regexp' value='1'" . (empty($_POST['regexp']) ? '' : ' checked') . '>as a regular expression</label>';
					echo doc_link(['sql' => 'regexp.html', 'pgsql' => 'functions-matching.html#FUNCTIONS-POSIX-REGEXP']) . "</p>\n";
				}
				echo "</div></fieldset>\n";
				if ($_POST["search"] && $_POST["query"] != "") {
					$_GET["where"][0]["op"] = $adminer->operator_regexp === null || empty($_POST['regexp']) ? "LIKE %%" : $adminer->operator_regexp;
					search_tables();
				}
			}
			echo "<div class='scrollable'>\n";
			echo "<table cellspacing='0' class='nowrap checkable'>\n";
			echo script("mixin(qsl('table'), {onclick: tableClick, ondblclick: partialArg(tableClick, true)});");
			echo '<thead><tr class="wrap">';
			echo '<td><input id="check-all" type="checkbox" class="jsonly">' . script("qs('#check-all').onclick = partial(formCheck, /^(tables|views)\[/);", "");
			echo '<th>Table';
			echo '<td>Engine' . doc_link(['sql' => 'storage-engines.html']);
			echo '<td>Collation' . doc_link(['sql' => 'charset-charsets.html', 'mariadb' => 'supported-character-sets-and-collations/']);
			echo '<td>Data Length' . doc_link(['sql' => 'show-table-status.html', 'pgsql' => 'functions-admin.html#FUNCTIONS-ADMIN-DBOBJECT', 'oracle' => 'REFRN20286']);
			echo '<td>Index Length' . doc_link(['sql' => 'show-table-status.html', 'pgsql' => 'functions-admin.html#FUNCTIONS-ADMIN-DBOBJECT']);
			echo '<td>Data Free' . doc_link(['sql' => 'show-table-status.html']);
			echo '<td>Auto Increment' . doc_link(['sql' => 'example-auto-increment.html', 'mariadb' => 'auto_increment/']);
			echo '<td>Rows' . doc_link(['sql' => 'show-table-status.html', 'pgsql' => 'catalog-pg-class.html#CATALOG-PG-CLASS', 'oracle' => 'REFRN20286']);
			echo (support("comment") ? '<td>Comment' . doc_link(['sql' => 'show-table-status.html']) : '');
			echo "</thead>\n";

			$tables = 0;
			foreach ($tables_list as $name => $type) {
				$view = ($type !== null && !preg_match('~table|sequence~i', $type));
				$id = h("Table-" . $name);
				echo '<tr' . odd() . '><td>' . checkbox(($view ? "views[]" : "tables[]"), $name, in_array($name, $tables_views, true), "", "", "", $id);
				echo '<th>' . (support("table") || support("indexes") ? "<a href='" . h(ME) . "table=" . urlencode($name) . "' title='Show structure' id='$id'>" . h($name) . '</a>' : h($name));
				if ($view) {
					echo '<td colspan="6"><a href="' . h(ME) . "view=" . urlencode($name) . '" title="Alter view">' . (preg_match('~materialized~i', $type) ? 'Materialized view' : 'View') . '</a>';
					echo '<td align="right"><a href="' . h(ME) . "select=" . urlencode($name) . '" title="Select data">?</a>';
				} else {
					foreach ([
						"Engine" => [],
						"Collation" => [],
						"Data_length" => ["create", 'Alter table'],
						"Index_length" => ["indexes", 'Alter indexes'],
						"Data_free" => ["edit", 'New item'],
						"Auto_increment" => ["auto_increment=1&create", 'Alter table'],
						"Rows" => ["select", 'Select data'],
					] as $key => $link) {
						$id = " id='$key-" . h($name) . "'";
						echo ($link ? "<td align='right'>" . (support("table") || $key == "Rows" || (support("indexes") && $key != "Data_length")
							? "<a href='" . h(ME . "$link[0]=") . urlencode($name) . "'$id title='$link[1]'>?</a>"
							: "<span$id>?</span>"
						) : "<td id='$key-" . h($name) . "'>");
					}
					$tables++;
				}
				echo (support("comment") ? "<td id='Comment-" . h($name) . "'>" : "");
			}

			echo "<tr><td><th>" . sprintf('%d in total', count($tables_list));
			echo "<td>" . h($jush == "sql" ? $connection->result("SELECT @@default_storage_engine") : "");
			echo "<td>" . h(db_collation(DB, collations()));
			foreach (["Data_length", "Index_length", "Data_free"] as $key) {
				echo "<td align='right' id='sum-$key'>";
			}

			echo "</table>\n";
			echo "</div>\n";
			if (!information_schema(DB)) {
				echo "<div class='footer'><div>\n";
				$vacuum = "<input type='submit' value='Vacuum'> " . on_help("'VACUUM'");
				$optimize = "<input type='submit' name='optimize' value='Optimize'> " . on_help($jush == "sql" ? "'OPTIMIZE TABLE'" : "'VACUUM OPTIMIZE'");
				echo "<fieldset><legend>Selected <span id='selected'></span></legend><div>"
				. ($jush == "sql" ? "<input type='submit' value='Analyze'> " . on_help("'ANALYZE TABLE'") . $optimize
					. "<input type='submit' name='check' value='Check'> " . on_help("'CHECK TABLE'")
					. "<input type='submit' name='repair' value='Repair'> " . on_help("'REPAIR TABLE'")
				: "")
				. "<input type='submit' name='truncate' value='Truncate'> " . on_help($jush == "sqlite" ? "'DELETE'" : "'TRUNCATE" . ($jush == "pgsql" ? "'" : " TABLE'")) . confirm()
				. "<input type='submit' name='drop' value='Drop'>" . on_help("'DROP TABLE'") . confirm() . "\n";
				$databases = (support("scheme") ? $adminer->schemas() : $adminer->databases());
				if (count($databases) != 1 && $jush != "sqlite") {
					$db = (isset($_POST["target"]) ? $_POST["target"] : (support("scheme") ? $_GET["ns"] : DB));
					echo "<p>Move to other database: ";
					echo ($databases ? html_select("target", $databases, $db) : '<input name="target" value="' . h($db) . '" autocapitalize="off">');
					echo " <input type='submit' name='move' value='Move'>";
					echo (support("copy") ? " <input type='submit' name='copy' value='Copy'> " . checkbox("overwrite", 1, $_POST["overwrite"], 'overwrite') : "");
					echo "\n";
				}
				echo "<input type='hidden' name='all' value=''>"; // used by trCheck()
				echo script("qsl('input').onclick = function () { selectCount('selected', formChecked(this, /^(tables|views)\[/));" . (support("table") ? " selectCount('selected2', formChecked(this, /^tables\[/) || $tables);" : "") . " }");
				echo "<input type='hidden' name='token' value='$token'>\n";
				echo "</div></fieldset>\n";
				echo "</div></div>\n";
			}
			echo "</form>\n";
			echo script("tableCheck();");
		}
		$links = [];
		$links[] = "<a href='" . h(ME) . "create='>Create table</a>";
		if (support("view")) {
			$links[] = "<a href='" . h(ME) . "view='>Create view</a>";
		}
		echo generate_linksbar($links);

		if (support("routine")) {
			echo "<h3 id='routines'>Routines</h3>\n";
			$routines = routines();
			if ($routines) {
				echo "<table cellspacing='0'>\n";
				echo "<thead><tr><th>Name<td>Type<td>Return type<td></thead>\n";
				odd('');
				foreach ($routines as $row) {
					$name = ($row["SPECIFIC_NAME"] == $row["ROUTINE_NAME"] ? "" : "&name=" . urlencode($row["ROUTINE_NAME"])); // not computed on the pages to be able to print the header first
					echo '<tr' . odd() . '>';
					echo '<th><a href="' . h(ME . ($row["ROUTINE_TYPE"] != "PROCEDURE" ? 'callf=' : 'call=') . urlencode($row["SPECIFIC_NAME"]) . $name) . '">' . h($row["ROUTINE_NAME"]) . '</a>';
					echo '<td>' . h($row["ROUTINE_TYPE"]);
					echo '<td>' . h($row["DTD_IDENTIFIER"]);
					echo '<td><a href="' . h(ME . ($row["ROUTINE_TYPE"] != "PROCEDURE" ? 'function=' : 'procedure=') . urlencode($row["SPECIFIC_NAME"]) . $name) . '">Alter</a>';
				}
				echo "</table>\n";
			}
			$links = [];
			if (support('procedure')) {
				$links[] = "<a href='" . h(ME) . "procedure='>Create procedure</a>";
			}
			$links[] = "<a href='" . h(ME) . "function='>Create function</a>";
			echo generate_linksbar($links);
		}

		if (support("sequence")) {
			echo "<h3 id='sequences'>Sequences</h3>\n";
			$sequences = get_vals("SELECT sequence_name FROM information_schema.sequences WHERE sequence_schema = current_schema() ORDER BY sequence_name");
			if ($sequences) {
				echo "<table cellspacing='0'>\n";
				echo "<thead><tr><th>Name</thead>\n";
				odd('');
				foreach ($sequences as $val) {
					echo "<tr" . odd() . "><th><a href='" . h(ME) . "sequence=" . urlencode($val) . "'>" . h($val) . "</a>\n";
				}
				echo "</table>\n";
			}
			echo generate_linksbar(["<a href='" . h(ME) . "sequence='>Create sequence</a>"]);
		}

		if (support("type")) {
			echo "<h3 id='user-types'>User types</h3>\n";
			$user_types = types();
			if ($user_types) {
				echo "<table cellspacing='0'>\n";
				echo "<thead><tr><th>Name</thead>\n";
				odd('');
				foreach ($user_types as $val) {
					echo "<tr" . odd() . "><th><a href='" . h(ME) . "type=" . urlencode($val) . "'>" . h($val) . "</a>\n";
				}
				echo "</table>\n";
			}
			echo generate_linksbar(["<a href='" . h(ME) . "type='>Create type</a>"]);
		}

		if (support("event")) {
			echo "<h3 id='events'>Events</h3>\n";
			$rows = get_rows("SHOW EVENTS");
			if ($rows) {
				echo "<table cellspacing='0'>\n";
				echo "<thead><tr><th>Name<td>Schedule<td>Start<td>End<td></thead>\n";
				foreach ($rows as $row) {
					echo "<tr>";
					echo "<th>" . h($row["Name"]);
					echo "<td>" . ($row["Execute at"] ? "At given time<td>" . $row["Execute at"] : "Every " . $row["Interval value"] . " " . $row["Interval field"] . "<td>$row[Starts]");
					echo "<td>$row[Ends]";
					echo '<td><a href="' . h(ME) . 'event=' . urlencode($row["Name"]) . '">Alter</a>';
				}
				echo "</table>\n";
				$event_scheduler = $connection->result("SELECT @@event_scheduler");
				if ($event_scheduler && $event_scheduler != "ON") {
					echo "<p class='error'><code class='jush-sqlset'>event_scheduler</code>: " . h($event_scheduler) . "\n";
				}
			}
			echo generate_linksbar(["<a href='" . h(ME) . "event='>Create event</a>"]);
		}

		if ($tables_list) {
			echo script("ajaxSetHtml('" . js_escape(ME) . "script=db');");
		}
	}
}
