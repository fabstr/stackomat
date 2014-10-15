<?php

class ChecksumValidator {
	/**
 	 * Validate the string of integers.
	 * The string is valid if it can be validated with a correct checksum
	 * according to the Luhn algorithm, EAN13 or EAN8.
	 * @param string The string (of integers) to validate.
	 * @return true if the string is valid, else false.
	 */
	public function validate($string) {
		if ($this -> validateLuhn($string)) {
			return true;
		} else if ($this -> validateEAN13($string)) {
			return true;
		} else if ($this -> validateEAN8($string)) {
			return true;
		}

		return false;
	}

	/**
 	 * Check if a string is a number.
	 * @param string The string to check.
	 * @return true if the string is a number, else false.
	 */
	private function is_number($string) {
		if (preg_match('/^[0-9]+$/', $string)) return true;
		return false;
	}

	/**
 	 * Compute the Luhn checksum for the number in string.
	 * @param string A string representing a single non-negative integer.
	 * @return The Luhn checksum.
	 */
	private function computeLuhn($string) {
		$arr = str_split($string);
		$i = 2;
		$sum = 0;
		foreach ($arr as $num) {
			$mult = 1;
			if ($i % 2 == 0) $mult = 2;
			$i++;
			$num *= $mult;
			if ($num >= 10) {
				$sum += 1 + $num - 10;
			} else {
				$sum += $num;
			}
		}

		$ental = $sum % 10;
		$hogreTiotal = $sum - $ental + 10;
		return ($hogreTiotal - $sum) % 10;
	}

	/**
 	 * Compute the EAN13 checksum for the number in string.
	 * @param string A string representing a single non-negative integer.
	 * @return The EAN13 checksum.
	 */
	private function computeEAN13($string) {

		$arr = str_split($string);
		$i = 1;
		$sum = 0;
		foreach ($arr as $num) {
			$mult = 1;
			if ($i % 2 == 0) $mult = 3;
			$sum += $num * $mult;
			$i++;
		}

		$ental = $sum % 0;
		$higher = $sum - $ental + 10;
		return ($higher - $sum) % 10;
	}

	/**
 	 * Compute the EAN8 checksum for the number in string.
	 * @param string A string representing a single non-negative integer.
	 * @return The EAN8 checksum.
	 */
	private function computeEAN8($string) {

		$arr = str_split($string);
		$i = 1;
		$sum = 0;
		foreach ($arr as $num) {
			$mult = 3;
			if ($i % 2 == 0) $mult = 1;
			$sum += $num * $mult;
			$i++;
		}

		$ental = $sum % 10;
		$higher = $sum - $ental + 10;
		return ($higher - $sum) % 10;
	}

	/**
	 * Check whether string can be validated with a Luhn checksum.
	 * @param string The string to validate.
	 * @return true if the checksum is correct, else false.
	 */
	public function validateLuhn($string) {
		if (!$this -> is_number($string)) return false;

		$numberToComputeFor = substr($string, 0, strlen($string)-1);
		$correctChecksum = substr($string, -1);

		$checksum = $this -> computeLuhn($numberToComputeFor);

		return $checksum == $correctChecksum;
	}

	/**
	 * Check whether string can be validated with a EAN13 checksum.
	 * @param string The string to validate.
	 * @return true if the checksum is correct, else false.
	 */
	public function validateEAN13($string) {
		if (!$this -> is_number($string)) return false;

		$numberToComputeFor = substr($string, 0, strlen($string)-1);
		$correctChecksum = substr($string, -1);
		$checksum = $this -> computeEAN13($numberToComputeFor);
		return $correctChecksum == $checksum;
	}

	/**
	 * Check whether string can be validated with a EAN8 checksum.
	 * @param string The string to validate.
	 * @return true if the checksum is correct, else false.
	 */
	public function validateEAN8($string) {
		if (!$this -> is_number($string)) return false;

		$numberToComputeFor = substr($string, 0, strlen($string)-1);
		$correctChecksum = substr($string, -1);
		$checksum = $this -> computeEAN8($numberToComputeFor);
		return $correctChecksum == $checksum;
	}

	/**
 	 * Compute the Luhn checksum for string and return the concatenation of
	 * string and the checksum.
	 * @param string The string to compute the checksum for.
	 * @return The concatenation of the string and its checksum.
	 */
	public function makeLuhn($string) {
		return $string . $this -> computeLuhn($string);
	}

	/**
 	 * Compute the EAN13 checksum for string and return the concatenation of
	 * string and the checksum.
	 * @param string The string to compute the checksum for.
	 * @return The concatenation of the string and its checksum.
	 */
	public function makeEAN13($string) {
		return $string . $this -> computeEAN13($string);
	}


	public function test() {
		echo "Testing checksums...\n";

		assert_options(ASSERT_ACTIVE, 1);
		$luhn = array(
			'640823323' => '4',
			'811218987' => '6'
		);
		$ean = array(
			'400638133393' => '1',
			'308854050827' => '6',
			'731086507269' => '6'
		);
		foreach ($luhn as $number => $sum) {
			assert ($sum == $this -> computeLuhn($number), 'luhn: ' . $number);
		}
		foreach ($ean as $number => $sum) {
			assert ($sum == $this -> computeEAN13($number), 'ean: ' . $number);
		}
		echo "Done!\n";
	}
}
?>
