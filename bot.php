<?php
echo "Бот запускается...\n";

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Bot.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$token = $_ENV['BOT_TOKEN'];
$saveDir = __DIR__ . "/files";

$bot = new Bot($token, $saveDir);
$bot->run();
