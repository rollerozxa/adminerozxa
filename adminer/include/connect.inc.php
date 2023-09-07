<?php
function connect_error() {
	global $adminer, $connection, $token, $error, $drivers;
	if (DB != "") {
		header("HTTP/1.1 404 Not Found");
		page_header('Database: ' . h(DB), 'Invalid database.', true);
	} else {
		if ($_POST["db"] && !$error) {
			queries_redirect(substr(ME, 0, -1), 'Databases have been dropped.', drop_databases($_POST["db"]));
		}

		page_header('Select database', $error, false);
		$actions = [
			'database' => 'Create database',
			'privileges' => 'Privileges',
			'processlist' => 'Process list',
			'variables' => 'Variables',
			'status' => 'Status',
		];
		$links = [];
		foreach ($actions as $key => $val) {
			if (support($key)) {
				$links[] = "<a href='" . h(ME) . "$key='>$val</a>";
			}
		}
		echo generate_linksbar($links);
		echo "<p>" . sprintf('%s version: %s through PHP extension %s', $drivers[DRIVER], "<b>" . h($connection->server_info) . "</b>", "<b>$connection->extension</b>") . "\n";
		echo "<p>" . sprintf('Logged as: %s', "<b>" . h(logged_user()) . "</b>") . "\n";
		$databases = $adminer->databases();
		if ($databases) {
			$scheme = support("scheme");
			$collations = collations();
			echo "<form action='' method='post'>\n";
			echo "<table cellspacing='0' class='checkable'>\n";
			echo script("mixin(qsl('table'), {onclick: tableClick, ondblclick: partialArg(tableClick, true)});");
			echo "<thead><tr>"
				. (support("database") ? "<td>" : "")
				. "<th>Database - <a href='" . h(ME) . "refresh=1'>Refresh</a>"
				. "<td>Collation"
				. "<td>Tables"
				. "<td>Size - <a href='".h(ME)."dbsize=1'>Compute</a>" . script("qsl('a').onclick = partial(ajaxSetHtml, '" . js_escape(ME) . "script=connect');", "")
				. "</thead>\n"
			;

			$databases = ($_GET["dbsize"] ? count_tables($databases) : array_flip($databases));

			foreach ($databases as $db => $tables) {
				$root = h(ME) . "db=" . urlencode($db);
				$id = h("Db-" . $db);
				echo "<tr" . odd() . ">" . (support("database") ? "<td>" . checkbox("db[]", $db, in_array($db, (array) $_POST["db"]), "", "", "", $id) : "");
				echo "<th><a href='$root' id='$id'>" . h($db) . "</a>";
				$collation = h(db_collation($db, $collations));
				echo "<td>" . (support("database") ? "<a href='$root" . ($scheme ? "&amp;ns=" : "") . "&amp;database=' title='Alter database'>$collation</a>" : $collation);
				echo "<td align='right'><a href='$root&amp;schema=' id='tables-" . h($db) . "' title='Database schema'>" . ($_GET["dbsize"] ? $tables : "?") . "</a>";
				echo "<td align='right' id='size-" . h($db) . "'>" . ($_GET["dbsize"] ? db_size($db) : "?");
				echo "\n";
			}

			echo "</table>\n";
			echo (support("database")
				? "<div class='footer'><div>\n"
					. "<fieldset><legend>Selected <span id='selected'></span></legend><div>\n"
					. "<input type='hidden' name='all' value=''>" . script("qsl('input').onclick = function () { selectCount('selected', formChecked(this, /^db/)); };") // used by trCheck()
					. "<input type='submit' name='drop' value='Drop'>" . confirm() . "\n"
					. "</div></fieldset>\n"
					. "</div></div>\n"
				: ""
			);
			echo "<input type='hidden' name='token' value='$token'>\n";
			echo "</form>\n";
			echo script("tableCheck();");
		}
	}

	page_footer("db");
}

if (isset($_GET["status"])) {
	$_GET["variables"] = $_GET["status"];
}
if (isset($_GET["import"])) {
	$_GET["sql"] = $_GET["import"];
}

if (!(DB != "" ? $connection->select_db(DB) : isset($_GET["sql"]) || isset($_GET["dump"]) || isset($_GET["database"]) || isset($_GET["processlist"]) || isset($_GET["privileges"]) || isset($_GET["user"]) || isset($_GET["variables"]) || $_GET["script"] == "connect" || $_GET["script"] == "kill")) {
	if (DB != "" || $_GET["refresh"]) {
		restart_session();
		set_session("dbs", null);
	}
	connect_error(); // separate function to catch SQLite error
	exit;
}

if (support("scheme")) {
	if (DB != "" && $_GET["ns"] !== "") {
		if (!isset($_GET["ns"])) {
			redirect(preg_replace('~ns=[^&]*&~', '', ME) . "ns=" . get_schema());
		}
		if (!set_schema($_GET["ns"])) {
			header("HTTP/1.1 404 Not Found");
			page_header('Schema: ' . h($_GET["ns"]), 'Invalid schema.', true);
			page_footer("ns");
			exit;
		}
	}
}
