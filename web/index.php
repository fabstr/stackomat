<?php
require('../settings.php');
$msg = "";

function getInput() {
	$id = $_POST['id'];
	$name = $_POST['name'];
	$cost = $_POST['cost'];
	if (isset($_POST['action'])) $action = $_POST['action'];
	if (!preg_match('/^[0-9]+$/', $id)) die();
	if (!is_string($name)) die();
	if (!preg_match('/^[0-9]+$/', $cost)) die();
	if (!(!isset($action) || $action == 'Uppdatera' || $action == 'Ta bort')) die();
	return array(
		'id' => $id, 
		'name' => $name, 
		'cost' => $cost,
		'action' => $action
	);
}

try {
$db = new PDO('mysql:host=localhost;dbname=stackomat', 'stackomat', $password);

if ($db) {
	if (isset($_GET['add'])) {
		$input = getInput();
		$id = $input['id'];
		$name = $input['name'];
		$cost = $input['cost'];

		$s = $db -> prepare('INSERT INTO products (id, name, cost) VALUES (:id, :name, :cost);');
		$s -> bindParam(':id', $id);
		$s -> bindParam(':name', $name);
		$s -> bindParam(':cost', $cost);
		if ($s -> execute()) {
			$added = true;
		} else {
			$added = false;
		}
	} else if (isset($_GET['update'])) {
		$input = getInput();
		$id = $input['id'];
		$name = $input['name'];
		$cost = $input['cost'];
		$action = $input['action'];

		$result = false;
		if ($action == 'Uppdatera') {
			$s = $db -> prepare('UPDATE products SET name=?, cost=? WHERE id=?;');
			$result = $s -> execute(array($name, $cost, $id));
		} else if ($action == 'Ta bort') {
			$s = $db -> prepare('DELETE FROM products WHERE id=?;');
			$result = $s -> execute(array($id));
		}

		if ($result === true) {
			$changed = true;
		} else {
			$changed = false;
		}
	}

	$s = $db -> prepare('SELECT id,name,cost FROM products;');
	$data = "";
	$s -> execute();
	foreach ($s -> fetchAll() as $row) {
		$data .= '<tr><form method="POST" action="index.php?update=true"><input type="hidden" name="id" value="'.htmlentities($row['id']).'">';
		$data .= '<td><input type="text" name="id" value="'.htmlentities($row['id']).'"></td>';
		$data .= '<td><input type="text" name="name" value="'.htmlentities($row['name']).'"></td>';
		$data .= '<td><input type="text" name="cost" value="'.htmlentities($row['cost']).'"></td>';
		$data .= '<td><input type="submit" name="action" value="Uppdatera"></td>';
		$data .= '<td><input type="submit" name="action" value="Ta bort"></td>';
		$data .= '</form></tr>';
	}
}

} catch (PDOException $e) {
	$msg = $e -> getMessage();
	echo $msg;
}

?>


<!DOCTYPE html>
<html>
<head>
	<title>Stackomat</title>
	<meta charset='utf-8'>
</head>
<body>
	<?php 
if (isset($added)) {
if ($added) echo 'Produkten lades till.<br/>';
else echo "Produkten kunde inte läggas till.<br/>";
} 
if (isset($msg)) echo $msg;
?>

	<form action="index.php?add=true" method="post">
	<table>
		<tr>
			<td>Produktens namn</td>
			<td><input name="name" type="text" placholder="Namn..."></td>
		</tr>
		<tr>
			<td>Produktens kostnad</td>
			<td><input type="text" name="cost"></td>
		</tr>
		<tr>
			<td>Produktens streckckod</td>
			<td><input type="text" name="id" placholder="Streckkod..."></td>
		</tr>
		<tr>
			<td colspan="2">
				<input type="submit" value="Lägg till">
			</td>
		</tr>
	</table>

	</form>

	<?php if (!isset($data)) echo 'Kunde inte hämta data från databasen.';?>

	<table><?php echo $data; ?></table>
</body>
</html>
