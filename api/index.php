<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 99.06
//-----------------------------
require "../vendor/autoload.php";

$app = new Slim\App();

// Register routes
require __DIR__ . '/routes.php';

// Run app
$app->run();
