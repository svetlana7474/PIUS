<?php 
echo "Бот запускается...\n";
define('TOKEN', '7464255272:AAET0M7A6ZEDb2p7-_qas8pJLo8awtnvqw0');

$offset = 0;

while (true) {
    $response = file_get_contents("https://api.telegram.org/bot" . TOKEN . "/getUpdates?offset=" . $offset);
    $response = json_decode($response, true);

    if (!empty($response['result'])) {
        foreach ($response['result'] as $update) {
            $chat_id = $update['message']['chat']['id'];
            $text = $update['message']['text'];

            if ($text == "/start") {
                sendWelcomeMessage($chat_id);
                sendMenu($chat_id);
            } else {
                sendMessage($chat_id, "Вы выбрали: " . $text);
            }

            $offset = $update['update_id'] + 1;
        }
    }
    
    sleep(2); 
}

function sendMessage($chat_id, $message) {
    $url = "https://api.telegram.org/bot" . TOKEN . "/sendMessage";
    file_get_contents($url . "?" . http_build_query(['chat_id' => $chat_id, 'text' => $message]));
}

function sendWelcomeMessage($chat_id) {
    $message = "Привет! Я ваш Telegram-бот. Вот что я умею:\n\n" .
               "- Шифровать файлы\n" .
               "- Расшифровывать файлы\n" ;
    sendMessage($chat_id, $message);
}

function sendMenu($chat_id) {
    $keyboard = [
        'keyboard' => [[['text' => "Зашифровать"], ['text' => "Расшифровать"]]],
        'resize_keyboard' => true,
        'one_time_keyboard' => false
    ];
    
    $url = "https://api.telegram.org/bot" . TOKEN . "/sendMessage";
    file_get_contents($url . "?" . http_build_query([
        'chat_id' => $chat_id,
        'text' => "Выберите действие:",
        'reply_markup' => json_encode($keyboard)
    ]));
} 
?>
