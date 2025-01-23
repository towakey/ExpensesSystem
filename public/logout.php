<?php
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Auth.php';

use ExpensesSystem\Auth;

$auth = new Auth();
$auth->logout();

header('Location: login.php');
exit;
