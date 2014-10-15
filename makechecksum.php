<?php

require_once('ChecksumValidator.php');

$cv = new ChecksumValidator();

// read a line on stdin, print the luhn and ean13 checksums
for (;;) {
	$line = trim(fgets(STDIN));

	echo 'Luhn: ' . $cv -> makeLuhn($line) . "\n";
	echo 'EAN13: ' . $cv -> makeEAN13($line) . "\n";
	echo "\n";
}

?>
