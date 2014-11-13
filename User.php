<?php
require_once('Exceptions.php');
require_once('log.php');

class User {
	// the pdo handle
	private $db;

	// the id of this user
	private $id;

	public function __construct($db, $id) {
		$this -> db = $db;
		$this -> id = hash('SHA256', $id);
	}

	/**
 	 * Add a user to the database.
	 * Hash the id with  sha256 before adding to the database.
	 * @param db The pdo handle.
	 * @param id The id of the user to add.
	 * @param name The name of the user to add (can be the empty string).
	 * @param balance The initial balance of the user to add.
	 * @param calories The initial amount of calories eaten by the user.
	 * @return true if the user was added successfully, else false.
	 */
	public static function addUser($db, $id, $name, $balance, $calories)  {
		l('add user');
		$s = $db -> prepare('
			INSERT INTO users (id, name, balance) 
			VALUES (:id, :name, :balance, :calories)');
		$id = hash('SHA256', $id);
		$s -> bindParam(':id', $id);
		$s -> bindParam(':name', $name);
		$s -> bindParam(':balance', $balance);
		$s -> bindParam(':calories', $calories);
		return $s -> execute();
	}

	/**
 	 * Check if a user with the given id exists in the database.
	 * The id is hashed before checking in the database.
	 * @param db The pdo handle.
	 * @param id The id to check.
	 * @return true if the user exists, else false.
	 */
	public static function isUser($db, $id) {
		l('isUser? ' . $id);
		$id = hash('SHA256', $id);
		$s = $db -> prepare('SELECT *, COUNT(*) AS cnt FROM users WHERE id=:id');
		$s -> bindParam(':id', $id);
		$s -> execute();
		$res = $s -> fetch();
		$result = $res['cnt'] > 0;
		if ($result) l('isUser? yes');
		else l('isUser? no');
		return $result;
	}

	/**
 	 * Get the name of this user by checking in the database.
	 * @return The name.
	 */
	public function getName() {
		$s = $this -> db -> prepare('
			SELECT name 
			FROM users 
			WHERE id=:id');
		$s -> bindParam(':id', $id);
		$s -> execute();
		$row = $s -> fetch();
		return $row['name'];
	}

	/**
 	 * Get the amount of calories consumed by this user.
	 * @return The amount.
	 */
	public function getCalories() {
		$s = $this -> db -> prepare('
			SELECT calories
			FROM users
			WHERE id=:id');
		$s -> bindParam(':id', $this -> id);
		$s -> execute();
		$row = $s -> fetch();
		return $row['calories'];
	}

	/**
 	 * Let the user pay.
	 * In a transaction, update the user's balance to balance - amount
	 * and update lastPurchase with amount. If both queries could be 
	 * executed, commit and return true. Else, rollback and throw an 
	 * exception.
	 *
	 * @param amount The amount to pay.
	 * @return true if the database could be updated.
	 */
	public function pay($amount) {
		$this -> db -> beginTransaction();

		$s = $this -> db -> prepare('
			UPDATE users 
			SET balance = balance - :amount 
			WHERE id = :id');
		$s -> bindParam(':amount', $amount);
		$s -> bindParam(':id', $this -> id);

		$p = $this -> db -> prepare('
			INSERT INTO lastPurchase (id, amount) 
			VALUES (:id1, :amount1) 
			ON DUPLICATE KEY UPDATE id=:id2, amount=:amount2');
		$p -> bindParam(':id1', $this -> id);
		$p -> bindParam(':amount1', $amount);
		$p -> bindParam(':id2', $this -> id);
		$p -> bindParam(':amount2', $amount);

		if ($s -> execute() && $p -> execute()) {
			$this -> db -> commit();
			return true;
		} else {
			$this -> db -> rollback();
			throw new DatabaseException('Köpet kunde inte genomföras. '
			       . 'Saldot har inte belastats.');
		}
	}

	/**
 	 * Add amount to the user's balance.
	 * @param amount The amount to add.
	 * @return true if the database could be updated, else false.
	 */
	public function addBalance($amount) {
		$s = $this -> db -> prepare('
			UPDATE users 
			SET balance = balance + :amount 
			WHERE id = :id');
		$s -> bindParam(':amount', $amount);
		$s -> bindParam(':id', $this -> id);
		return $s -> execute();
	}

	/**
 	 * Get the balance of this user by checking in the database.
	 * @return The balance.
	 */
	public function getBalance() {
		$s = $this -> db -> prepare('
			SELECT balance 
			FROM users 
			WHERE id=:id');
		$s -> bindParam(':id', $this -> id);
		$s -> execute();
		$result = $s -> fetch();
		return $result['balance'];
	}

	/**
 	 * Undo the user's last purchase.
	 * First get the amount from the last purchase. If this does not exist,
	 * throw an exception. If it does, update the user's balance and delete 
	 * the row in lastPurchase. Return true if the updates was successfull, 
	 * else false.
	 * @return true if the update was successfull, else false
	 */
	public function undoLatest() {
		// get the amount
		$s = $this -> db -> prepare('
			SELECT amount
			FROM lastPurchase
			WHERE id=:id');
		$s -> bindParam(':id', $this -> id);
		$s -> execute();
	       	$row = $s -> fetch();
		$amount = $row['amount'];

		if ($amount) {
			// the amount exists so there is a purchase to undo

			// begin the transaction
			$this -> db -> beginTransaction();

			// update the balance
			$p = $this -> db -> prepare('
				UPDATE users
				SET balance = balance + :amount
				WHERE id=:id;');
			$p -> bindParam(':amount', $amount);
			$p -> bindParam(':id', $this -> id);

			// delete the row from lastPurchase
			$q = $this -> db -> prepare('
				DELETE FROM lastPurchase
				WHERE id=:id;');
			$q -> bindParam(':id', $this -> id);

			// execute 
			if ($p -> execute() && $q -> execute()) {
				$this -> db -> commit();
				return true;
			} else {
				$this -> db -> rollback();
				return false;
			}
		}

		throw new NoUndoException('Det finns inget köp att ångra. Köp går '
			.'endast att ångra en gång.');
	}

	/**
 	 * Add (or remove) calories from this user.
	 * If amount is positive, add calories; if amount is negative, remove
	 * calories.
	 * @param amount The calories to add or remove.
	 * @return true if the change was successfull.
	 */
	public function addCalories($amount) {
		$s = $this -> db -> prepare('
			UPDATE users
			SET calories = calories + :amount
			WHERE id=:id');
		$s -> bindParam(':amount', $amount);
		$s -> bindParam(':id', $this -> id);
		return $s -> execute();
	}

	/**
 	 * Return true if the user counts calories.
	 * @return true if the user counts calories.
	 */
	public function countsCalories() {
		$s = $this -> db -> prepare('
			SELECT countCalories
			FROM users
			WHERE id=:id');
		$s -> bindParam(':id', $this -> id);
		$s -> execute();
		$row = $s -> fetch();
		if ($row['countCalories'] === true) {
			return true;
		}

		return false;
	}

	/**
 	 * Toggle the user's calorie counting.
	 * If the user is not counting, make the user count calories.
	 * If the user is counting, set the calories to zero and disable 
	 * counting.
	 * @return true if the change was succesfull.
	 */
	public function toggleCalories() {
		if ($this -> countsCalories()) {
			// disable counting
			$s = $this -> db -> prepare('
				UPDATE users
				SET calories = 0, countCalories = false
				WHERE id=:id');
			$s -> bindParam(':id', $this -> id);
			return $s -> execute();
		} else {
			// enable counting
			$s = $this -> db -> prepare('
				UPDATE users
				SET countCalories = true
				WHERE id=:id');
			$s -> bindParam(':id', $this -> id);
			return $s -> execute();
		}
	}
}

?>
