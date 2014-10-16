#!/usr/bin/php
<?php

require_once('User.php');
require_once('Product.php');
require_once('ChecksumValidator.php');
require_once('settings.php');
require_once('Exceptions.php');
require_once('StackomatPrinter.php');

class Stackomat {
	private $db;
	private $checksumValidator;
	private $stackomatPrinter;

	public function __construct($db) {
		$this -> db = $db;
		$this -> checksumValidator = new ChecksumValidator();
		$this -> stackomatPrinter = new StackomatPrinter();
	}

	/**
 	 * Read input from the user.
	 * If exceptionForCancel is true, throw an exception if ABORT was 
	 * entered by the user, else return zero.
	 *
	 * @throw Exception if (ABORT was scanned and exceptionForCancel is 
	 *                  true) or the input could not be validated with a
	 *                  checksum.
	 *
	 * @param exceptionForCancel If true, throw an exception when ABORT is
	 *                           entered.
	 *
	 * @param return The input.
	 */
	private function readInput($exceptionForCancel=true) {
		$input = fgets(STDIN);
		$input = trim($input);
		if ($input == 0) {
			if ($exceptionForCancel == true) {
				throw new AbortException('Avbryter');
			}

			return $input;
		} else if ($this -> checksumValidator -> validate($input)) {
			return $input;
		} else {

		throw new InvalidChecksumException('Kunde inte scanna: '
			.'Kontrollsumman gick inte att validera eller var '
			.'felaktig.');
		}
	}

	/**
	 * Collect input from collector until stopWhen returns false.
	 * until stopWhen(collector()) return false, add the result from 
	 * collector() to the initial array
	 * return array(
	 * 		an array of collected values,
	 * 		the last value from collector
	 * )
	 * - collector is a function that gets input from the user
	 * - stopWhen is a function that returns false when the collecting 
	 *   should  stop. It takes one argument that has been returned from 
	 *   collector.
	 * @param collector A callable that returns input from the user.
	 * @param stopWhen A callable that given data from collector returns 
	 *                 false when to stop collecting.
	 * @param initial An array of initial values. 
	 * @return An array of the initial values and the data from collector as
	 *         the first element and the last input from collector as the 
	 *         second element.
	 */
	private function collectUntil($collector, $stopWhen, $initial) {
		$input = $collector();
		while ($stopWhen($input)) {
			array_push($initial, $input);
			$input = $collector();
		}

		return array($initial, $input);
	}

	/**
         * Return whether str was a command to add balance.
	 * @param str The command to check.
	 * @return true if str is a command to add balance, else false.
	 */
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

	/**
         * Return whether str was a command to add a user.
	 * @param str The command to check.
	 * @return true if str is a command to add a user, else false.
	 */
	private function isAddUser($str) {
		return $str == '13370010';
	}

	/**
         * Return whether str was a command to show balance.
	 * @param str The command to check.
	 * @return true if str is a command to show balance, else false.
	 */
	private function isShowBalance($str) {
		return $str == '13370077';
	}

	/**
         * Return whether str was a command to undo a purchase.
	 * @param str The command to check.
	 * @return true if str is a command to undo a purchase, else false.
	 */
	private function isUndo($str) {
		return $str == '13370085';
	}

	/**
         * Return whether str was a command.
	 * @param str The string to check.
	 * @return true if str is a command, else false.
	 */
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

	/**
 	 * Handle the purchase of a product.
	 * 1. Tell the user how this works.
	 * 2. Read input until the user is no longer entering products.
	 * 3. The last (non-product) input should be the id. Check it exists.
	 * 4. Transform the entered product codes to an array of Product using
	 *    map.
	 * 5. Get the total cost of the products using reduce.
	 * 6. Get the user's balance and check the cost can be afforded. Throw
	 *    an exception if it cannot.
	 * 7. Update the database, throw an exception if it cannot be done.
	 * 8. Print a success message and the new balance.
	 *
	 * @param firstProduct The id of the first product (which was entered
	 *                     before the calling of this method).
	 */
	private function handlePurchase($firstProduct) {
		echo "Scanna ytterligare 0 eller fler varor. Scanna sedan ditt id för att betala.\n";

		$p = Product::fromId($this -> db, $firstProduct);
		$this -> stackomatPrinter -> printPromptInner();
		echo $p -> getName() . ': ' . $p -> getCost() . "\n";

		$products = $this -> collectUntil(
			function() {$this -> stackomatPrinter -> printPromptInner(); return $this -> readInput();}, 
			function($e) {
				$res = Product::isProduct($this -> db, $e);
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
		if (!User::isUser($this -> db, $id)) {
			throw new UserNotFoundException('Ditt id kunde inte hittas i '
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
			throw new CannotAffordException('Du har inte råd. Saldo: ' 
				. $balance . ', kostnad: ' . $totalCost);
		} 

		if (!$user -> pay($totalCost)) {
			throw new DatabaseException('Kunde inte utföra betalningen.');
		}

		$this -> stackomatPrinter -> printGreen('Du har betalat ' . $totalCost . ".\n"
			. 'Nytt saldo: ' . $user->getBalance() . "\n");
	}

	/**
 	 * Get the sum that the balance-adding-codes represent.
	 * @param code The code to check
	 * @return 0 if the code was invalid, else 5, 10, 20, 50 or 100 
	 *         (depending on the code).
	 */
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

	/**
 	 * Handle the adding of balance.
	 * 1. Tell the user how this works.
	 * 2. Read input until the user is no longer entering add-balance-codes.
	 * 3. The last (non-balance-code) input should be the id. Check it 
	 *    exists.
	 * 5. Get the total sum of the balance-adding-codes using reduce and 
	 *    sumFromBalanceCode.
	 * 7. Update the database, throw an exception if it cannot be done.
	 * 8. Print a success message and the new balance.
	 *
	 * @param firstBalance The first balance-adding-code (which was entered 
	 *                     before the calling of this method).
	 */
	private function handleAddBalance($firstBalance) {
		echo "Scanna 0 eller fler ladda-koder. Scanna sedan ditt id för att slutföra \nladdningen.\n";
		$this -> stackomatPrinter -> printPromptInner();
		echo 'Laddar ' . $this -> sumFromBalanceCode($firstBalance) . "\n";

		$balances = $this -> collectUntil(
			function() {$this -> stackomatPrinter -> printPromptInner(); return $this -> readInput();},
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
		if (!User::isUser($this -> db, $id)) {
			throw new UserNotFoundException('Ditt id kunde inte hittas i '
				.'databasen.');
		}
		$sumToAdd = array_reduce(
			$balances,
			function($sum, $e) {$sum += $this -> sumFromBalanceCode($e); return $sum;}
		);

		$user = new User($this -> db, $id);
		if (!$user -> addBalance($sumToAdd)) {
			throw new DatabaseException('Kunde inte ladda.');
		}

		$this -> stackomatPrinter -> printGreen('Du har laddat.'."\n"
			. 'Nytt saldo: ' . $user -> getBalance() . "\n");
	}

	/**
	 * Handle the showing of balance.
	 * Prompt the user for the id, check it exists, get the balance and 
	 * print it.
	 */
	private function handleShowBalance() {
		echo "Scanna ditt id för att visa saldo:\n";
		$this -> stackomatPrinter -> printPromptInner();
		$id = $this -> readInput();
		echo 'Läste id: ' . $id . "\n";

		if (!User::isUser($this -> db, $id)) {
			throw new UserNotFoundException('Ditt id kunde inte hittas i ' 
				.'databasen.');
		}

		$user = new User($this -> db, $id);
		$balance = $user -> getBalance();
		echo 'Saldo: ' . $balance . "\n";
	}

	/**
 	 * Handle the adding of a user.
	 * 1. Prompt the user for an id.
	 * 2. Check the id is not a command, an existing user or a product.
	 * 3. Add the user to the database.
	 * 4. Print a success message.
	 */
	private function handleAddUser() {
		echo "Scanna ditt id för att lägga till dig som användare:\n";
		$this -> stackomatPrinter -> printPromptInner();
		$id = $this -> readInput();
		echo 'Läste id: ' . $id . "\n";

		if ($this -> isCommand($id)) {
			$this -> stackomatPrinter -> printRed("id:t är ett kommando, kommandon får "
				."inte läggas till som användare.\n");

		} else if (User::isUser($this -> db, $id)) {
			$this -> stackomatPrinter -> printRed("Du kunde inte läggas till i "
				."databasen: id:t finns redan.\n");

		} else if (Product::isProduct($this -> db, $id)) {
			$this -> stackomatPrinter -> printRed("id:t finns redan som produkt, "
				."produkter får inte läggas till som "
				."användare.\n");

		} else {
			if (!User::addUser($this -> db, $id, '', 0)) {
				throw new DatabaseException('Kunde inte lägga till dig '
					.'i databasen.');
			}

			$this -> stackomatPrinter -> printGreen('Du lades till.');
		}
	}

	/**
  	 * Handle undo of a purchase.
	 * First get the id and check it exists, then call undo on the User 
	 * object. If the undo was successfull, print a success message, else
	 * throw an exception.
	 */
	private function handleUndo() {
		echo "Scanna ditt id för att ångra det senaste köpet:\n";
		$this -> stackomatPrinter -> printPromptInner();
		$id = $this -> readInput();
		echo 'Läste id: ' . $id . "\n";
		if (!User::isUser($this -> db, $id)) {
			throw new UserNotFoundException("Id:t finns inte i databasen.");
		}

		$user = new User($this -> db, $id);
		if ($user -> undoLatest()) {
			$this -> stackomatPrinter -> printGreen("Köpet ångrades. Nytt saldo: " 
				. $user -> getBalance() . "\n");
		} else {
			throw new DatabaseException('Kunde inte ånga köp. Saldo: ' 
				. $user -> getBalance());
		}
	}

	/**
  	 * Do a round.
	 * 1. Print the prompt and read input.
	 * 2. Check if it is a product/add balance/show balance/undo/add user.
	 * 3. If so, call the correct handle-function.
	 * 4. Else, throw an exception.
	 */
	private function doRound() {
		$this -> stackomatPrinter -> printLine();
		$this -> stackomatPrinter -> printPrompt();
		$action = $this -> readInput(false);
		if ($action == 0) return;

		if (Product::isProduct($this -> db, $action)) {
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
			throw new UnknownCommandException('Okänt kommando.');
		}
	}

	/**
 	 * Run an eternal loop of doRound's.
	 * Before starting the loop, turn off echoing in the terminal, reset
	 * this upon exiting the loop (ie never).
	 */
	public function run() {
		exec('/bin/stty -g', $stty);
		exec('/bin/stty -echo');

		for (;;) {
			try {
				$this -> doRound();
			} catch (AbortException $e) {
				$this -> stackomatPrinter -> printRed($e -> getMessage());
			} catch (InvalidChecksumException $e) {
				$this -> stackomatPrinter -> printRed($e -> getMessage());
			} catch (UserNotFoundException $e) {
				$this -> stackomatPrinter -> printRed($e -> getMessage());
			} catch (CannotAffordException $e) {
				$this -> stackomatPrinter -> printRed($e -> getMessage());
			} catch (DatabaseException $e) {
				$this -> stackomatPrinter -> printRed($e -> getMessage());
			} catch (NoUndoException $e) {
				$this -> stackomatPrinter -> printRed($e -> getMessage());
			} catch (UnknownCommandException $e) {
				$this -> stackomatPrinter -> printRed($e -> getMessage());
			}
		}

		exec('/bin/stty -g ' . $stty[0]);
	}
}

for (;;) {
	// try to create a stackomat instance, if not successfull print the error 
	// message and exit.
	try {
		$pdo = new PDO('mysql:host=localhost;dbname=stackomat', 
			'stackomat', $password);
		$stackomat = new Stackomat($pdo);
	} catch (PDOException $e) {
		echo 'Kunde inte starta stackomaten: ';
		echo $e -> getMessage();
		echo "\n";
		exit(1);
	}

	// run the stackomat, if we get any pdo exception, catch it and restart
	// the stackomat the next round in the for-loop.
	try {
		$stackomat -> run();
	} catch (PDOException $e) {
		echo 'Fick pdo-exception: ' . $e -> getMessage() . "\n";
		echo 'Startar om stackomaten...' . "\n";
	}
}

?>
