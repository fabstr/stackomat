<?php

class User {
	private $db;
	private $id;

	public function __construct($db, $id) {
		$this -> db = $db;
		$this -> id = $id;
	}

	public static function addUser($db, $id, $name, $balance)  {
		$s = $db -> prepare('
			INSERT INTO users (id, name, balance) 
			VALUES (:id, :name, :balance)');
		$s -> bindParam(':id', $id);
		$s -> bindParam(':name', $name);
		$s -> bindParam(':balance', $balance);
		$s -> execute();
	}

	public function getName() {
		$s = $this -> db -> prepare('SELECT name FROM users WHERE id=:id');
		$s -> bindParam(':id', $id);
		$row = $s -> execute() -> fetchArray(SQLITE3_ASSOC);
		return $row['name'];
	}

	public function pay($amount) {
		$this -> db -> exec('BEGIN TRANSACTION;');

		$s = $this -> db -> prepare('
			UPDATE users 
			SET balance = balance - :amount 
			WHERE id = :id');
		$s -> bindParam(':amount', $amount);
		$s -> bindParam(':id', $this -> id);

		$p = $this -> db -> prepare('INSERT OR REPLACE INTO '
			.'lastPurchase  (id, amount) VALUES (:id, :amount)');
		$p -> bindParam(':id', $this -> id);
		$p -> bindParam(':amount', $amount);

		if ($s -> execute() && $p -> execute()) {
			$this -> db -> exec('COMMIT TRANSACTION;');
			return true;
		} else {
			$this -> db -> exec('ROLLBACK TRANSACTION;');
			throw new Exception('Köpet kunde inte genomföras. '
			       . 'Saldot har inte belastats.');
		}
	}

	public function addBalance($amount) {
		$s = $this -> db -> prepare('
			UPDATE users 
			SET balance = balance + :amount 
			WHERE id = :id');
		$s -> bindParam(':amount', $amount);
		$s -> bindParam(':id', $this -> id);
		return $s -> execute();
	}

	public function getBalance() {
		$s = $this -> db -> prepare('
			SELECT balance 
			FROM users 
			WHERE id=:id');
		$s -> bindParam(':id', $this -> id);
		$result = $s -> execute() -> fetchArray();
		return $result['balance'];
	}

	public function undoLatest() {

		$s = $this -> db -> prepare('
			SELECT amount
			FROM lastPurchase
			WHERE id=:id');
		$s -> bindParam(':id', $this -> id);
		$row = $s -> execute() -> fetchArray();
		$amount = $row['amount'];
		if ($amount) {
			$this -> db -> exec('BEGIN TRANSACTION');

			$p = $this -> db -> prepare('
				UPDATE users
				SET balance = balance + :amount
				WHERE id=:id;');
			$p -> bindParam(':amount', $amount);
			$p -> bindParam(':id', $this -> id);

			$q = $this -> db -> prepare('
				DELETE FROM lastPurchase
				WHERE id=:id;');
			$q -> bindParam(':id', $this -> id);

			if ($p -> execute() && $q -> execute()) {
				$this -> db -> exec('COMMIT TRANSACTION');
				return true;
			} else {
				$this -> db -> exec('ROLLBACK TRANSACTION');
				return false;
			}
		}

		throw new Exception('Det finns inget köp att ångra. Köp går '
			.'endast att ångra en gång.');
	}
}

?>
