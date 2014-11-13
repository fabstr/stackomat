<?php

require_once('log.php');

class Product {
	// the pdo handle
	private $db;

	// the id of this product
	private $id;

	// the name of this product
	private $name;

	// the cost of this product
	private $cost;

	// the calories in this product
	private $calories;

	public function __construct($db, $id, $name, $cost, $calories) {
		$this -> db = $db;
		$this -> id = $id;
		$this -> name = $name;
		$this -> cost = $cost;
		$calories -> calories = $calories;
	}

	/**
	 * Create a new instance of Product with data from the database.
	 * @param db The pdo handle.
	 * @param id The id of the product to instansiate.
	 * @return The product.
	 */
	public static function fromId($db, $id) {
		l('getting user from id');
		$product = new self($db, $id, "", 0);
		$s = $db -> prepare('SELECT name, cost, calories FROM products WHERE id=:id');
		$s -> bindParam(':id', $id);
		$s -> execute();
		$row = $s -> fetch();
		$product = new self($db, $id, $row['name'], $row['cost'], $row['calories']);
		return $product;
	}

	/**
 	 * Check whether a product with the id exists in the database.
	 * @param db The pdo handle.
	 * @param id The id to check.
	 * @return True if such a product exists, else false.
	 */
	public static function isProduct($db, $id) {
		l('is product? ' . $id);
		$s = $db -> prepare('SELECT COUNT(*) AS cnt FROM products WHERE id=:id');
		$s -> bindParam(':id', $id);
		$s -> execute();
	        $res = $s -> fetch();
		$result = $res['cnt'] > 0;
		if ($result) l('is product? yes');
		else l('is product? no');
		return $result;
	}

	/**
 	 * Create and add a product to the database.
	 * @param id The id of the product to add.
	 * @param name The name of the product.
	 * @param cost The cost of the product.
	 * @return True if the query was successfull, else false.
	 */
	public static function addToDatabase($id, $name, $cost, $calories) {
		$s = $db -> prepare(
			'INSERT INTO products (id, name, cost)
			VALUES (:id, :name, :cost)');
		$s -> bindParam(':id', $id);
		$s -> bindParam(':name', $name);
		$s -> bindParam(':cost', $cost);
		$s -> bindParam(':calories', $calories);
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
 	 * Get the amount of calories in this product.
	 * @return The calories.
	 */
	public function getCalories() {
		return $this -> calories;
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

	/**
	 * Change the amout of calories in this product.
	 * @param newCalories The new amount of calories in this product.
	 * @return true if the cange was successfull, else false.
	 */
	public function changeCalories($newCalories) {
		$s = $this -> db -> prepare(
			'UPDATE products SET calories=:calories WHERE id=:id');
		$s -> bindParam(':calories', $newCalories);
		$s -> bindParam(':id', $this -> id);
		return $s -> execute();
	}
}

?>
