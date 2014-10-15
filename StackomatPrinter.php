<?php

class StackomatPrinter {
	/**
 	 * Print 80 dashes.
	 */
	public function printLine() {
		echo "\n\n\n---------------------------------------------------"
			."-----------------------------\n";
	}

	/**
 	 * Print str in red text.
	 * @str The string to print in red.
	 */
	public function printRed($str) { 
		echo "\033[1;31m" . $str; 
		$this -> resetPrintColor();
	}

	/**
 	 * Print str in green text.
	 * @str The string to print in green.
	 */
	public function printGreen($str) {
		echo "\033[1;32m" . $str; 
		$this -> resetPrintColor();
	}

	/**
 	 * Reset the print color to normal.
	 */
	private function resetPrintColor() { 
		echo "\033[1;0m"; 
	}

	/**
 	 * Print the main prompt.
	 */
	public function printPrompt() {
		echo " >> ";
	}

	/**
 	 * Print the additional prompt.
	 */
	public function printPromptInner() {
		echo " -> ";
	}
}

?>
