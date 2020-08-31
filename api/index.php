<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 99.06
//-----------------------------
ini_set("display_errors", "On");
require "../vendor/autoload.php";

$app = new Slim\App();

// Register routes
require __DIR__ . '/routes.php';

// Run app
$app->run();
