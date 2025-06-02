<?php
require_once __DIR__."/vendor/autoload.php";

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

define("HTTP", isset($_SERVER["REQUEST_METHOD"]));
define("PRODUCTION", $_ENV["APP_ENV"] === "production");
define("DATABASE_SOCKET", $_ENV["DATABASE_SOCKET"] ?? null);
define("DATABASE_HOST", $_ENV["DATABASE_HOST"] ?? "localhost");
define("DATABASE_PORT", $_ENV["DATABASE_PORT"] ?? 3306);
define("DATABASE_NAME", $_ENV["DATABASE_NAME"] ?? null);
define("DATABASE_USERNAME", $_ENV["DATABASE_USERNAME"] ?? "root");
define("DATABASE_PASSWORD", $_ENV["DATABASE_PASSWORD"] ?? null);
