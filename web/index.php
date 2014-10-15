<?php

$db = new Sqlite3('../databas.sqlite');
if ($db) {
	if (isset($update)) {
	} else {
		$s = $db -> prepare('SELECT id,name,cost FROM products;');
		$data = "";
		while (($row = $s -> fetchArray())) {
			$data .= '<tr>';
			$data .= '<td> <input type="text" name="id" value="'.$row['id'].'"></td>';
			$data .= '<td> <input type="text" name="name" value="'.$row['name'].'"></td>';
			$data .= '<td> <input type="text" name="cost" value="'.$row['cost'].'"></td>';
			$data .= '</tr>';
		}
	}
}

?>


<!DOCTYPE html>
<html>
<head>
	<title>Stackomat</title>
	<meta charset='utf-8'>
</head>
<body>
	<form action="index.php" method="post">
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
			<td>Lösenord</td>
			<td><input type="password" name="password"></td>
		</tr>
	</table>

	</form>


	<table><?php echo $data; ?></table>
</body>
</html>
