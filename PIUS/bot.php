<?php  
echo "Бот запускается...\n";

define('TOKEN', '7464255272:AAET0M7A6ZEDb2p7-_qas8pJLo8awtnvqw0');
$offset = 0;
$states = [];

$saveDir = __DIR__ . "/files";
if (!file_exists($saveDir)) {
    mkdir($saveDir, 0777, true);
}

while (true) {
    $response = file_get_contents("https://api.telegram.org/bot" . TOKEN . "/getUpdates?offset=" . $offset);
    $response = json_decode($response, true);

    if (!empty($response['result'])) {
        foreach ($response['result'] as $update) {
            $update_id = $update['update_id'];
            $chat_id = $update['message']['chat']['id'];
            $message = $update['message'];

            if (isset($message['text'])) {
                $text = $message['text'];

                if ($text == "/start") {
                    sendWelcomeMessage($chat_id);
                    sendMenu($chat_id);
                } elseif ($text == "1") {
                    $states[$chat_id] = "encode";
                    sendMessage($chat_id, "Пожалуйста, отправьте файл для кодирования.");
                } elseif ($text == "2") {
                    $states[$chat_id] = "decode";
                    sendMessage($chat_id, "Пожалуйста, отправьте файл для декодирования.");
                } else {
                    sendMessage($chat_id, "Вы выбрали: " . $text);
                }
            }

            // Обработка файла
            if (isset($message['document'])) {
                if (!isset($states[$chat_id])) {
                    sendMessage($chat_id, "❗ Не выбрано действие. Сначала выберите, что сделать: кодировать или декодировать.");
                } else {
                    $file_id = $message['document']['file_id'];
                    $original_name = $message['document']['file_name'];
                    $file_path = getFilePath($file_id);
                    $file_url = "https://api.telegram.org/file/bot" . TOKEN . "/" . $file_path;

                    $prefix = ($states[$chat_id] == "encode") ? "encoded" : "decoded";
                    $ext = pathinfo($original_name, PATHINFO_EXTENSION);

                    $i = 1;
                    do {
                        $new_file_name = "{$prefix}_{$i}." . $ext;
                        $file_path_on_disk = "$saveDir/$new_file_name";
                        $i++;
                    } while (file_exists($file_path_on_disk));

                    // Сохраняем файл
                    $data = file_get_contents($file_url);
                    file_put_contents($file_path_on_disk, $data);

                    // Отправляем путь на API-сервер
                    $apiUrl = "http://127.0.0.1:8000/api/v1/blowfish";
                    $queryParams = http_build_query([
                        'user_id' => $chat_id,
                        'path' => realpath($file_path_on_disk),
                        'action' => $states[$chat_id] == "encode" ? "encrypt" : "decrypt"
                    ]);

                    $response = @file_get_contents($apiUrl . '?' . $queryParams);
                    $result = json_decode($response, true);

                    // Проверка результата
                    sendMessage($chat_id, "Ответ API:\n" . $response);
                    if (isset($result['encryptedFilePath'])) {
                        $finalPath = $result['encryptedFilePath'];
                        sendDocument($chat_id, $finalPath, basename($finalPath));
                    } else {
                        sendMessage($chat_id, "❌ Ошибка при обработке файла. Сервер вернул пустой или неверный ответ.");
                    }

                    unset($states[$chat_id]); // сброс состояния
                }
            }

            $offset = $update_id + 1;
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
               "- 1: Кодировать файл\n" .
               "- 2: Декодировать файл\n";
    sendMessage($chat_id, $message);
}

function sendMenu($chat_id) {
    $keyboard = [
        'keyboard' => [[['text' => "1"], ['text' => "2"]]],
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

function getFilePath($file_id) {
    $url = "https://api.telegram.org/bot" . TOKEN . "/getFile?file_id=" . $file_id;
    $response = file_get_contents($url);
    $response = json_decode($response, true);
    return $response['result']['file_path'] ?? '';
}

function sendDocument($chat_id, $file_path, $filename) {
    if (!file_exists($file_path)) {
        sendMessage($chat_id, "❌ Обработанный файл не найден: $filename");
        return;
    }

    $url = "https://api.telegram.org/bot" . TOKEN . "/sendDocument";
    $post_fields = [
        'chat_id' => $chat_id,
        'document' => new CURLFile($file_path, '', $filename)
    ];

    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type:multipart/form-data"]);
    curl_setopt($ch, CURLOPT_URL, $url); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields); 
    curl_exec($ch);
    curl_close($ch);
}
?>
