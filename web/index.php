<?php
require('../settings.php');

try {
$db = new PDO('mysql:host=localhost;dbname=stackomat', 'stackomat', $password);

if ($db) {
	if (isset($_GET['update'])) {
		$id = $_POST['id'];
		$name = $_POST['name'];
		$cost = $_POST['cost'];
		$s = $db -> prepare('INSERT INTO products (id, name, cost) VALUES (:id, :name, :cost);');
		$s -> bindParam(':id', $id);
		$s -> bindParam(':name', $name);
		$s -> bindParam(':cost', $cost);
		if ($s -> execute()) {
			$added = true;
		} else {
			$added = false;
			$msg = $db -> lastErrorMsg();
		}
	}

	$s = $db -> prepare('SELECT id,name,cost FROM products;');
	$data = "";
	$s -> execute();
	foreach ($s -> fetchAll() as $row) {
		$data .= '<tr>';
		$data .= '<td> <input type="text" name="id" value="'.$row['id'].'"></td>';
		$data .= '<td> <input type="text" name="name" value="'.$row['name'].'"></td>';
		$data .= '<td> <input type="text" name="cost" value="'.$row['cost'].'"></td>';
		$data .= '</tr>';
	}
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
	<?php 
if (isset($added)) {
if ($added) echo 'Produkten lades till.<br/>';
else echo "Produkten kunde inte läggas till.<br/>";
} 
echo $msg;
?>

	<form action="index.php?update=true" method="post">
	<table>
		<tr>
			<td>Produktens streckckod</td>
			<td><input type="text" name="id" placholder="Streckkod..."></td>
		</tr>
		<tr>
			<td>Produktens namn</td>
			<td><input name="name" type="text" placholder="Namn..."></td>
		</tr>
		<tr>
			<td>Produktens kostnad</td>
			<td><input type="text" name="cost"></td>
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
