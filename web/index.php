<?php
require('../settings.php');

function getInput() {
	$id = $_POST['id'];
	$name = $_POST['name'];
	$cost = $_POST['cost'];
	$calories = $_POST['calories'];
	if (isset($_POST['action'])) $action = $_POST['action'];
	if (!preg_match('/^[0-9]+$/', $id)) die();
	if (!is_string($name)) die();
	if (!preg_match('/^[0-9]+$/', $cost)) die();
	if (!preg_match('/^[0-9]+$/', $calories)) die();
	if (!isset($action) || !($action == 'Uppdatera' || $action == 'Ta bort')) die();
	return array(
		'id' => $id, 
		'name' => $name, 
		'cost' => $cost,
		'action' => $action,
		'calories' => $calories
	);
}

function handleAdd($db) {
	$input = getInput();
	$id = $input['id'];
	$name = $input['name'];
	$cost = $input['cost'];
	$calories = $input['calories'];

	$s = $db -> prepare('INSERT INTO products (id, name, cost, calories) VALUES (:id, :name, :cost, :calories);');
	$s -> bindParam(':id', $id);
	$s -> bindParam(':name', $name);
	$s -> bindParam(':cost', $cost);
	$s -> bindParam(':calories', $calories);
	return  ($s -> execute());
}

function handleUpdate($db) {
	$input = getInput();
	$id = $input['id'];
	$name = $input['name'];
	$cost = $input['cost'];
	$action = $input['action'];
	$calories = $input['calories'];

	$result = false;
	if ($action == 'Uppdatera') {
		$s = $db -> prepare('UPDATE products SET name=?, cost=?, calories=? WHERE id=?;');
		$result = $s -> execute(array($name, $cost, $calories, $id));
	} else if ($action == 'Ta bort') {
		$s = $db -> prepare('DELETE FROM products WHERE id=?;');
		$result = $s -> execute(array($id));
	}

	return $result;
}

function listProducts($db) {
	$s = $db -> prepare('SELECT id,name,cost,calories FROM products ORDER BY name,cost;');
	$s -> execute();
	return $s -> fetchAll();
}

try {
	$db = new PDO('mysql:host=localhost;dbname=stackomat', 'stackomat', $password);

	if (isset($_GET['add'])) {
		$added = handleAdd($db);
	} else if (isset($_GET['update'])) {
		$changed = handleUpdate($db);
	}

	$data = "";
	foreach (listProducts($db) as $row) {
		$data .= '<tr>';
		$data .= '<form method="POST" action="index.php?update=true">';
		$data .= '<input type="hidden" name="id" value="'.htmlentities($row['id']).'">';
		$data .= '<td><input disabled="disabled" type="text" name="id" value="'.htmlentities($row['id']).'"></td>';
		$data .= '<td><input type="text" name="name" value="'.htmlentities($row['name']).'"></td>';
		$data .= '<td><input type="text" name="cost" value="'.htmlentities($row['cost']).'"></td>';
		$data .= '<td><input type="text" name="calories" value="'.htmlentities($row['calories']).'"></td>';
		$data .= '<td><input type="submit" name="action" value="Uppdatera"></td>';
		$data .= '<td><input type="submit" name="action" value="Ta bort"></td>';
		$data .= '</form>';
		$data .= '</tr>';
	}

	if (isset($added)) {
		if ($added) $msg = 'Produkten lades till.';
		else $msg = 'Produkten kunde inte läggas till.';
	} else if (isset($changed)) {
		if ($changed) $msg = 'Ändringen genomfördes.';
		else $msg = 'Ändringen kunde inte genomföras.';
	}

} catch (PDOException $e) {
	$msg = $e -> getMessage();
}

?>

<!DOCTYPE html>
<html>
<head>
	<title>Stackomat</title>
	<meta charset='utf-8'>
</head>
<body>
	<?php if (isset($msg)) echo $msg; ?>

	<p>Fyll i fälten och tryck på "Lägg till" för att lägga till en produkt i databasen.</p>

	<form action="index.php?add=true" method="post">
	<table>
		<tr> <td>Produktens streckckod</td> <td><input type="text" name="id" placholder="Streckkod..."></td> </tr>
		<tr> <td>Produktens namn</td> <td><input name="name" type="text" placholder="Namn..."></td> </tr>
		<tr> <td>Produktens kostnad</td> <td><input type="text" name="cost"></td> </tr>
		<tr> <td>Antal kalorier</td> <td><input type="text" name="calories" placholder="Kalorier..."></td> </tr>
		<tr> <td colspan="2"> <input type="submit" value="Lägg till"> </td> </tr>
	</table>

	</form>

	<p>För att ändra en produkt, ändra i fälten och tryck på uppdatera.</p>
	<?php if (!isset($data)) echo 'Kunde inte hämta data från databasen.';?>

	<table>
		<thead>
			<tr>
				<th>Streckkod</th>
				<th>Namn</th>
				<th>Kostnad</th>
				<th>Kalorier</th>
				<th>Åtgärder</th>
			</tr>
		</thead>
		<?php echo $data; ?>
	</table>
</body>
</html>
