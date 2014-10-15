<?php

class Product {
	private $db;
	private $id;
	private $name;
	private $cost;

	public function __construct($db, $id, $name, $cost) {
		$this -> db = $db;
		$this -> id = $id;
		$this -> name = $name;
		$this -> cost = $cost;
	}

	public static function fromId($db, $id) {
		$product = new self($db, $id, "", 0);
		$s = $db -> prepare('SELECT name, cost FROM products WHERE id=:id');
		$s -> bindParam(':id', $id);
		$row = $s -> execute() -> fetchArray(SQLITE3_ASSOC);
		$product -> id = $id;
		$product -> setName($row['name']);
		$product -> setCost($row['cost']);
		return $product;
	}

	private static function isProduct($id) {
		$s = $this -> db -> prepare('SELECT COUNT(*) AS cnt FROM products WHERE id=:id');
		$s -> bindParam(':id', $id);
		$res = $s -> execute() -> fetchArray();
		return $res['cnt'] > 0;
	}


	public function getId() {
		return $this -> id;
	}

	public function getName() {
		return $this -> name;
	}

	public function getCost() {
		return $this -> cost;
	}

	public static function addToDatabase($id, $name, $cost) {
		$s = $db -> prepare(
			'INSERT INTO products (id, name, cost)
			VALUES (:id, :name, :cost)');
		$s -> bindParam(':id', $id);
		$s -> bindParam(':name', $name);
		$s -> bindParam(':cost', $cost);
		return $s -> execute();
	}

	public function changeName($newName) {
		$s = $this -> db -> prepare(
			'UPDATE products SET name=:name WHERE id=:id');
		$s -> bindParam(':name', $newName);
		$s -> bindParam(':id', $this -> id);
		return $s -> execute();
	}

	public function changeCost($newCost) {
		$s = $this -> db -> prepare(
			'UPDATE products SET cost=:cost WHERE id=:id');
		$s -> bindParam(':cost', $newCost);
		$s -> bindParam(':id', $this -> id);
		return $s -> execute();
	}

	public function setName($name) {
		$this -> name = $name;
	} 

	public function setCost($cost) {
		$this -> cost = $cost;
	}
}

?>
