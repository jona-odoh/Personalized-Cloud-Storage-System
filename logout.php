<?php
// logout.php
require_once 'core/Auth.php';

$auth->logout();
header('Location: index.php');
exit;
