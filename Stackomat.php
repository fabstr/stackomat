#!/usr/bin/php
<?php

require_once('User.php');
require_once('Product.php');
require_once('ChecksumValidator.php');

class Stackomat {
	private $db;
	private $checksumValidator;

	public function __construct($db) {
		$this -> db = $db;
		$this -> checksumValidator = new ChecksumValidator();
	}

	private function printLine() {
		echo "\n\n\n---------------------------------------------------"
			."-----------------------------\n";
	}

	private function printRed($str) { 
		echo "\033[1;31m" . $str; 
		$this -> resetPrintColor();
	}

	private function printGreen($str) {
		echo "\033[1;32m" . $str; 
		$this -> resetPrintColor();
	}

	private function resetPrintColor() { 
		echo "\033[1;0m"; 
	}

	private function readInput($exceptionForCancel=true) {
		$input = fgets(STDIN);
		$input = trim($input);
		if ($input == 0) {
			if ($exceptionForCancel == true) {
				throw new Exception('Avbryter');
			}

			return $input;
		} else if ($this -> checksumValidator -> validate($input)) {
			return $input;
		} else {

		throw new Exception('Kunde inte scanna: '
			.'Kontrollsumman gick inte att validera eller var '
			.'felaktig.');
		}
	}

	private function printPrompt() {
		echo " >> ";
	}

	private function printPromptInner() {
		echo " -> ";
	}

	// until stopWhen(collector()) return false, add the result from 
	// collector() to the initial array
	// return array(
	// 		an array of collected values,
	// 		the last value from collector
	// )
	// - collector is a function that gets input from the user
	// - stopWhen is a function that returns false when the collecting 
	//   should  stop. It takes one argument that has been returned from 
	//   collector.
	private function collectUntil($collector, $stopWhen, $initial) {
		$input = $collector();
		while ($stopWhen($input)) {
			array_push($initial, $input);
			$input = $collector();
		}

		return array($initial, $input);
	}
	private function isProduct($id) {
		$s = $this -> db -> prepare('SELECT COUNT(*) AS cnt FROM products WHERE id=:id');
		$s -> bindParam(':id', $id);
		$res = $s -> execute() -> fetchArray();
		return $res['cnt'] > 0;
	}

	private function isUser($id) {
		$s = $this -> db -> prepare('SELECT *, COUNT(*) AS cnt FROM users WHERE id=:id');
		$s -> bindParam(':id', $id);
		$res = $s -> execute() -> fetchArray();
		return $res['cnt'] > 0;
	}

	private function isAddBalance($str) {
		switch ($str) {
		case '13370028':
		case '13370036':
		case '13370044':
		case '13370051':
		case '13370069':
			return true;
		default:
			return false;
		}
	}

	private function isAddUser($str) {
		return $str == '13370010';
	}

	private function isShowBalance($str) {
		return $str == '13370077';
	}

	private function isUndo($str) {
		return $str == '13370085';
	}

	private function isCommand($str) {
		if ($this -> isAddBalance($str)) {
			return true;
		} else if ($this -> isShowBalance($str)) {
			return true;
		} else if ($this -> isAddBalance($str)) {
			return true;
		}
		return false;
	}

	private function handlePurchase($firstProduct) {
		echo "Scanna ytterligare 0 eller fler varor. Scanna sedan ditt id för att betala.\n";

		$p = Product::fromId($this -> db, $firstProduct);
		echo $p -> getName() . ': ' . $p -> getCost() . "\n";

		$products = $this -> collectUntil(
			function() {$this -> printPromptInner(); return $this -> readInput();}, 
			function($e) {
				$res = $this -> isProduct($e);
				if ($res) {
					$p = Product::fromId($this -> db, $e);
					echo $p -> getName() . ': ' . $p -> getCost() . "\n";
				}
				return $res;
			},
			array($firstProduct)
		);

		$id = $products[1];
		$products = $products[0];

		echo 'Läste id: ' . $id . "\n";
		if (!$this -> isUser($id)) {
			throw new Exception('Ditt id kunde inte hittas i '
				.'databasen.');
		}

		$products = array_map(
			function($e) {return Product::fromId($this -> db, $e);},
			$products
		);
		
		$totalCost = array_reduce(
			$products, 
			function($sum, $e) {$sum += $e -> getCost(); return $sum;},
			0
		);

		$user = new User($this -> db, $id);
		if ($user -> getBalance() < $totalCost) {
			$balance = $user -> getBalance();
			throw new Exception('Du har inte råd. Saldo: ' 
				. $balance . ', kostnad: ' . $totalCost);
		} 

		if (!$user -> pay($totalCost)) {
			throw new Exception('Kunde inte utföra betalningen.');
		}

		$this -> printGreen('Du har betalat ' . $totalCost . ".\n"
			. 'Nytt saldo: ' . $user->getBalance() . "\n");
	}

	private function sumFromBalanceCode($code) {
		if ($code == '13370028') {
			return 5;
		} else if ($code == '13370036') {
		       	return 10;
		} else if ($code == '13370044') {
		       	return 20;
		} else if ($code == '13370051') {
		       	return 50;
		} else if ($code == '13370069') {
		       	return 100;
		} else {
			return 0;
		}
	}

	private function handleAddBalance($firstBalance) {
		echo "Scanna 0 eller fler ladda-koder. Scanna sedan ditt id för att slutföra \nladdningen.\n";
		echo 'Laddar ' . $this -> sumFromBalanceCode($firstBalance) . "\n";

		$balances = $this -> collectUntil(
			function() {$this -> printPromptInner(); return $this -> readInput();},
			function($e) {
				$res = $this -> isAddBalance($e);
				if ($res) {
					echo 'Laddar ytterligare ' . $this -> sumFromBalanceCode($e) . "\n";
				}
				return $res;

			},
			array($firstBalance)
		);

		$id = $balances[1];
		$balances = $balances[0];

		echo 'Läste id: ' . $id . "\n";
		if (!$this -> isUser($id)) {
			throw new Exception('Ditt id kunde inte hittas i '
				.'databasen.');
		}
		$sumToAdd = array_reduce(
			$balances,
			function($sum, $e) {$sum += $this -> sumFromBalanceCode($e); return $sum;}
		);

		$user = new User($this -> db, $id);
		if (!$user -> addBalance($sumToAdd)) {
			throw new Exception('Kunde inte ladda.');
		}

		$this -> printGreen('Du har laddat.'."\n"
			. 'Nytt saldo: ' . $user -> getBalance() . "\n");
	}

	private function handleShowBalance() {
		echo "Scanna ditt id för att visa saldo:\n";
		$this -> printPromptInner();
		$id = $this -> readInput();
		echo 'Läste id: ' . $id . "\n";

		if (!$this -> isUser($id)) {
			throw new Exception('Ditt id kunde inte hittas i ' 
				.'databasen.');
		}

		$user = new User($this -> db, $id);
		$balance = $user -> getBalance();
		echo 'Saldo: ' . $balance . "\n";
	}

	private function handleAddUser() {
		echo "Scanna ditt id för att lägga till dig som användare:\n";
		$this -> printPromptInner();
		$id = $this -> readInput();
		echo 'Läste id: ' . $id . "\n";

		if ($this -> isCommand($id)) {
			$this -> printRed("id:t är ett kommando, kommandon får "
				."inte läggas till som användare.\n");

		} else if ($this -> isUser($id)) {
			$this -> printRed("Du kunde inte läggas till i "
				."databasen: id:t finns redan.\n");

		} else if ($this -> isProduct($id)) {
			$this -> printRed("id:t finns redan som produkt, "
				."produkter får inte läggas till som "
				."användare.\n");

		} else {
			if (!User::addUser($this -> db, $id, '', 0)) {
				throw new Exception('Kunde inte lägga till dig '
					.'i databasen.');
			}

			$this -> printGreen('Du lades till.');
		}
	}

	private function handleUndo() {
		echo "Scanna ditt id för att ångra det senaste köpet:\n";
		$this -> printPromptInner();
		$id = $this -> readInput();
		echo 'Läste id: ' . $id . "\n";
		if (!$this -> isUser($id)) {
			throw new Exception("Id:t finns inte i databasen.");
		}

		$user = new User($this -> db, $id);
		if ($user -> undoLatest()) {
			$this -> printGreen("Köpet ångrades. Nytt saldo: " 
				. $user -> getBalance() . "\n");
		} else {
			$this -> printRed("Kunde inte ångra köp. Saldo: " 
				. $user -> getBalance() . "\n");
		}
	}

	private function doRound() {
		$this -> printLine();
		$this -> printPrompt();
		$action = $this -> readInput(false);
		if ($action == 0) return;

		if ($this -> isProduct($action)) {
			$this -> handlePurchase($action);
		} else if ($this -> isAddBalance($action)) {
			$this -> handleAddBalance($action);
		} else if ($this -> isShowBalance($action)) {
			$this -> handleShowBalance();
		} else if ($this -> isUndo($action)) {
			$this -> handleUndo();
		} else if ($this -> isAddUser($action)) {
			$this -> handleAddUser();
		} else {
			throw new Exception('Okänt kommando.');
		}
	}

	public function run() {
		exec('/bin/stty -g', $stty);
		exec('/bin/stty -echo');
		//pcntl_signal(SIGINT, function ($signal) {
			//exec('/bin/stty -g ' . $stty[0]);
			//echo "bye!\n";
			//exit(0);
		//});
		for (;;) {
			try {
				$this -> doRound();
			} catch (Exception $e) {
				$this -> printRed($e -> getMessage());
			}
		}
		exec('/bin/stty -g ' . $stty[0]);
	}
}

//error_reporting(E_ERROR);
$stackomat = new Stackomat(new SQLite3('databas.sqlite'));
$stackomat -> run();

?>
