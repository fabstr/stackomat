<?php

class ChecksumValidator {
	public function validate($string) {
		if ($this -> validateLuhn($string)) {
			return true;
		} else if ($this -> validateEAN($string)) {
			return true;
		} 

		return false;
	}

	private function is_number($string) {
		if (preg_match('/^[0-9]+$/', $string)) return true;
		return false;
	}

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

	public function validateLuhn($string) {
		if (!$this -> is_number($string)) return false;

		$numberToComputeFor = substr($string, 0, strlen($string)-1);
		$correctChecksum = substr($string, -1);

		$checksum = $this -> computeLuhn($numberToComputeFor);

		return $checksum == $correctChecksum;
	}

	private function computeEAN($string) {
		$arr = str_split($string);
		$i = 1;
		$sum = 0;
		foreach ($arr as $num) {
			$mult = 1;
			if ($i % 2 == 0) $mult = 3;
			$sum += $num * $mult;
			$i++;
		}

		$ental = $sum % 10;
		$higher = $sum - $ental + 10;
		return ($higher - $sum) % 10;
	}

	public function validateEAN($string) {
		if ($this -> is_number($string)) return false;

		$numberToComputeFor = substr($string, 0, strlen($string)-1);
		$correctChecksum = substr($string, -1);
		$checksum = $this -> computeEAN($numberToComputeFor);
		return $correctChecksum == $checksum;
	}

	public function makeLuhn($string) {
		return $string . $this -> computeLuhn($string);
	}

	public function makeEAN($string) {
		return $string . $this -> computeEAN($string);
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
			assert ($sum == $this -> computeEAN($number), 'ean: ' . $number);
		}
		echo "Done!\n";
	}
}

?>
