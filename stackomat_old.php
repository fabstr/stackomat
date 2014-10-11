#!/usr/bin/php
<?php

$db = new SQLite3('databas.sqlite');
define("DEBUG", false);

//$db->exec('
	//CREATE TABLE users (id text, name text, balance integer);
	//CREATE TABLE products (id text, name text, cost integer);
	//INSERT INTO products (id, name, cost) VALUES
		//(\'GODIS\', \'Godis\', 5),
		//(\'LASK\', \'loka\', 7)
	//;
//');



function red($str) { echo "\033[1;31m" . $str; normal();}
function green($str) { echo "\033[1;32m" . $str; normal();}
function normal() { echo "\033[1;0m"; }


function findProduct($id) {
	global $db;
	if (DEBUG) echo "findProduct: " . $id . "asdf\n";
	$s = $db -> prepare('select * from products where id = :id');
	$s -> bindParam(':id', $id, SQLITE3_TEXT);
	$row = $s -> execute() -> fetchArray();
	return $row;
}

function findId($idToFind) {
	global $db;
	$s = $db -> prepare('select * from users where id = :id');
	$s -> bindParam(':id', $idToFind, SQLITE3_TEXT);
	$row = $s -> execute() -> fetchArray();
	return $row;
}

function payFor($user, $product) {
	global $db;
	if ($user == null || $product == null) return;

	$s = $db -> prepare('update users set balance = balance - :cost where id = :id');
	$s -> bindParam(':cost', $product['cost']);
	$s -> bindParam(':id', $user['id']);
	return $s -> execute();
}

function getBalance($user) {
	global $db;
	$s = $db -> prepare('select balance from users where id = :id');
	$s -> bindParam(':id', $user['id']);
	$row =  $s -> execute() -> fetchArray();;
	return $row['balance'];
}

function buy($productid, $user) {
	if (DEBUG) echo "buy: " . $productid . " " . $user . "\n";
	$product = findProduct($productid);
	if (!$product) {
		red("Kunde inte hitta varan.\n");
		return;
	}

	if (DEBUG) echo "product: " . $product['name'];
	$balance = getBalance($user);
	if ($balance < $product['cost']) {
		$saldo = getBalance($user);
		red('Du har inte råd. Saldo: ' . $saldo . "\n");
		return;
	} else if (payFor($user, $product)) {
		$saldo = getBalance($user);
		green('Du har betalat. Nytt saldo: ' . $saldo . "\n");
	}
}

function showBalance($user) {
	$balance = getBalance($user);
	echo 'Saldo: ' . $balance . "\n";
}

function addBalance($amount, $user) {
	global $db;
	$s = $db -> prepare('update users set balance = balance + :amount where id = :id');
	$s -> bindParam(':amount', $amount);
	$s -> bindParam(':id', $user['id']);
	if ($s -> execute()) {
		green('Nytt saldo: ' . getBalance($user) . "\n");
	} else {
		red("Kunde inte ladda.\n");
	}
}

function checkSum($id) {
	$arr = str_split($id);
	$arr2 = array();
	$i = 2;
	foreach ($arr as $num) {
		$mult = 1;
		if ($i % 2 == 0) $mult = 2;
		$i++;
		$num *= $mult;
		if ($num >= 10) {
			array_push($arr2, 1);
			array_push($arr2, $num - 10);
		} else {
			array_push($arr2, $num);
		}
	}

	$sum = 0;
	foreach ($arr2 as $num) $sum += $num;
	$ental = $sum % 10;
	$hogreTiotal = $sum - $ental + 10;
	return ($hogreTiotal - $sum) % 10;
}

function validateChecksum($id) {
	if (!preg_match('/^[0-9]+$/', $id)) return false;

	$tovalidate = substr($id, 0, strlen($id)-1);
	$checksum = substr($id, -1);
	$cs = checkSum($tovalidate);
	if ($cs == $checksum) return true;
	return false;
}

for (;;) {
	echo "\n\n\n-----------------------------------------------------------"
		."---------------------\n";
	echo " >> ";
	$action = fgets(STDIN);
	$action = trim($action);

	if ($action == '' || $action == '00000000') { // avbryt
		continue;
	}

	if (!validateChecksum($action)) {
		red("Kunde inte scanna. Prova igen med en korrekt streckkod!\n");
		continue;
	}

	if ($action == "13370010") {//  useradd
		echo "Scanna id-streckkoden för den nya användaren (eller AVBRYT):\n -> ";

		$id = fgets(STDIN);
		$id = trim($id);

		if ($id == '00000000') {
			echo "Avbryter\n";
			continue;
		}

		if (!validateChecksum($id)) {
			red("Kunde inte läsa id. Vänligen börja om med en korrekt streckkod.\n");
			continue;
		}

		$s = $db -> prepare('INSERT INTO users (id, balance) VALUES (:id, 0);');
		$s -> bindParam(':id', $id);
		if ($s -> execute())  {
			green("Du lades till.\n");
		} else {
			red("Du kunde inte läggas till.\n");
		}
		continue;
	}

	echo "Scanna ditt id (eller AVBRYT):\n -> ";
	$id = fgets(STDIN);
	$id = trim($id);

	if ($id == '00000000') {
		echo "Avbryter\n";
		continue;
	}

	if (!validateChecksum($id)) {
		red("Kunde inte scanna. Prova igen med en korrekt streckkod!\n");
		continue;
	}

	$user = findId($id);
	if (!$user) {
		red("Kunde inte hitta id:t.\n");
		continue;
	}


	if ($action == '13370077') showBalance($user); // saldo
	else if ($action == '13370069') addBalance(100, $user); // ladda 100
	else if ($action == '13370051') addBalance(50, $user); // ladda 50 
	else if ($action == '13370044') addBalance(20, $user); // ladda 20 
	else if ($action == '13370036') addBalance(10, $user);// ladda 10
	else if ($action == '13370028') addBalance(5, $user); // ladda 5
	else buy($action, $user);
}

?>
