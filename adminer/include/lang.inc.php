<?php
// not used in a single language version

$langs = [
	'en' => 'English', // Jakub Vrána - https://www.vrana.cz
];

/** Get current language
* @return string
*/
function get_lang() {
	return "en";
}

/** Translate string
* @param string
* @param int
* @return string
*/
function lang($idf, $number = null) {
	global $translations;
	$translation = ($translations[$idf] ? $translations[$idf] : $idf);
	if (is_array($translation)) {
		$pos = ($number == 1 ? 0 : 1);
		$translation = $translation[$pos];
	}
	$args = func_get_args();
	array_shift($args);
	$format = str_replace("%d", "%s", $translation);
	if ($format != $translation) {
		$args[0] = format_number($number);
	}
	return vsprintf($format, $args);
}

$LANG = "en";
