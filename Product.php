<?php

class Product {
	// the pdo handle
	private $db;

	// the id of this product
	private $id;

	// the name of this product
	private $name;

	// the cost of this product
	private $cost;

	public function __construct($db, $id, $name, $cost) {
		$this -> db = $db;
		$this -> id = $id;
		$this -> name = $name;
		$this -> cost = $cost;
	}

	/**
	 * Create a new instance of Product with data from the database.
	 * @param db The pdo handle.
	 * @param id The id of the product to instansiate.
	 * @return The product.
	 */
	public static function fromId($db, $id) {
		$product = new self($db, $id, "", 0);
		$s = $db -> prepare('SELECT name, cost FROM products WHERE id=:id');
		$s -> bindParam(':id', $id);
		$s -> execute();
		$row = $s -> fetch();
		$product = new self($db, $id, $row['name'], $row['cost']);
		return $product;
	}

	/**
 	 * Check whether a product with the id exists in the database.
	 * @param db The pdo handle.
	 * @param id The id to check.
	 * @return True if such a product exists, else false.
	 */
	public static function isProduct($db, $id) {
		$s = $db -> prepare('SELECT COUNT(*) AS cnt FROM products WHERE id=:id');
		$s -> bindParam(':id', $id);
		$s -> execute();
	        $res = $s -> fetch();
		return $res['cnt'] > 0;
	}

	/**
 	 * Create and add a product to the database.
	 * @param id The id of the product to add.
	 * @param name The name of the product.
	 * @param cost The cost of the product.
	 * @return True if the query was successfull, else false.
	 */
	public static function addToDatabase($id, $name, $cost) {
		$s = $db -> prepare(
			'INSERT INTO products (id, name, cost)
			VALUES (:id, :name, :cost)');
		$s -> bindParam(':id', $id);
		$s -> bindParam(':name', $name);
		$s -> bindParam(':cost', $cost);
		return $s -> execute();
	}


	/**
 	 * Get the id of this product.
	 * @return The id.
	 */
	public function getId() {
		return $this -> id;
	}

	/**
 	 * Get the name of this product.
	 * @return The name.
	 */
	public function getName() {
		return $this -> name;
	}

	/**
 	 * Get the cost of this product.
	 * @return The cost.
	 */
	public function getCost() {
		return $this -> cost;
	}

	/**
 	 * Change the name of the product.
	 * @param newName The new name of this product.
	 * @return true if the change was successfull, else false.
	 */
	public function changeName($newName) {
		$s = $this -> db -> prepare(
			'UPDATE products SET name=:name WHERE id=:id');
		$s -> bindParam(':name', $newName);
		$s -> bindParam(':id', $this -> id);
		return $s -> execute();
	}

	/**
 	 * Change the cost of the product.
	 * @param newCost The new cost of this product.
	 * @return true if the change was successfull, else false.
	 */
	public function changeCost($newCost) {
		$s = $this -> db -> prepare(
			'UPDATE products SET cost=:cost WHERE id=:id');
		$s -> bindParam(':cost', $newCost);
		$s -> bindParam(':id', $this -> id);
		return $s -> execute();
	}
}

?>
