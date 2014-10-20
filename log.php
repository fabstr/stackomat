<?php

define('LOGGING', true);

function l($str) {
	if (LOGGING) {
		$f = fopen('/var/log/stackomat.log', 'a+b');
		$strtowrite = date('ymd-h:i:s') . ' ' . $str . "\n";
		fwrite($f, $strtowrite);
		fclose($f);
	}
}

?>
