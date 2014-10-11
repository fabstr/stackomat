<?php

require_once('ChecksumValidator.php');

$cv = new ChecksumValidator();

for (;;) {
	$line = trim(fgets(STDIN));

	printf("Luhn: %d\nEAN:  %d\n\n", $cv -> makeLuhn($line), 
	$cv -> makeEAN($line));
}

?>
