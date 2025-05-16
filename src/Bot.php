<?php

namespace App;

class Bot
{
    private $token;
    private $offset = 0;
    private $states = [];
    private $saveDir;

    public function __construct(string $token, string $saveDir)
    {
        $this->token = $token;
        $this->saveDir = $saveDir;

        if (!file_exists($this->saveDir)) {
            mkdir($this->saveDir, 0777, true);
        }
    }

    public function run()
    {
        while (true) {
            $response = file_get_contents("https://api.telegram.org/bot{$this->token}/getUpdates?offset={$this->offset}");
            $response = json_decode($response, true);

            if (!empty($response['result'])) {
                foreach ($response['result'] as $update) {
                    $this->handleUpdate($update);
                }
            }

            sleep(2);
        }
    }

    public function handleUpdate(array $update)
    {
        $update_id = $update['update_id'];
        $message = $update['message'] ?? null;
        if (!$message) return;

        $chat_id = $message['chat']['id'];

        if (isset($message['text'])) {
            $this->processTextMessage($chat_id, $message['text']);
        }

        if (isset($message['document'])) {
            $this->processDocument($chat_id, $message['document']);
        }

        $this->offset = $update_id + 1;
    }

    private function processTextMessage(int $chat_id, string $text)
    {
        if ($text === "/start") {
            $this->sendWelcomeMessage($chat_id);
            $this->sendMenu($chat_id);
        } elseif ($text === "1") {
            $this->states[$chat_id] = "encode";
            $this->sendMessage($chat_id, "Пожалуйста, отправьте файл для кодирования.");
        } elseif ($text === "2") {
            $this->states[$chat_id] = "decode";
            $this->sendMessage($chat_id, "Пожалуйста, отправьте файл для декодирования.");
        } else {
            $this->sendMessage($chat_id, "Вы выбрали: " . $text);
        }
    }

    private function processDocument(int $chat_id, array $document)
    {
        if (!isset($this->states[$chat_id])) {
            $this->sendMessage($chat_id, "❗ Не выбрано действие. Сначала выберите, что сделать: кодировать или декодировать.");
            return;
        }

        $file_id = $document['file_id'];
        $original_name = $document['file_name'];
        $file_path = $this->getFilePath($file_id);
        $file_url = "https://api.telegram.org/file/bot{$this->token}/{$file_path}";

        $prefix = ($this->states[$chat_id] === "encode") ? "encoded" : "decoded";
        $ext = pathinfo($original_name, PATHINFO_EXTENSION);

        $i = 1;
        do {
            $new_file_name = "{$prefix}_{$i}.{$ext}";
            $file_path_on_disk = "{$this->saveDir}/{$new_file_name}";
            $i++;
        } while (file_exists($file_path_on_disk));

        $data = file_get_contents($file_url);
        file_put_contents($file_path_on_disk, $data);

        $apiUrl = "http://127.0.0.1:8000/api/v1/blowfish";
        $queryParams = http_build_query([
            'user_id' => $chat_id,
            'path' => realpath($file_path_on_disk),
            'action' => $this->states[$chat_id] === "encode" ? "encrypt" : "decrypt"
        ]);

        $response = @file_get_contents($apiUrl . '?' . $queryParams);
        $result = json_decode($response, true);

        if (isset($result['encryptedFilePath']) || isset($result['FilePath'])) {
            $finalPath = $result['encryptedFilePath'] ?? $result['FilePath'];
            $this->sendDocument($chat_id, $finalPath, basename($finalPath));
        } else {
            $this->sendMessage($chat_id, "❌ Ошибка при обработке файла. Сервер вернул пустой или неверный ответ.");
        }

        unset($this->states[$chat_id]);
    }

    private function sendMessage(int $chat_id, string $message)
    {
        $url = "https://api.telegram.org/bot{$this->token}/sendMessage?" .
            http_build_query(['chat_id' => $chat_id, 'text' => $message]);
        file_get_contents($url);
    }

    private function sendWelcomeMessage(int $chat_id)
    {
        $message = "Привет! Я ваш Telegram-бот. Вот что я умею:\n\n" .
                   "- 1: Кодировать файл\n" .
                   "- 2: Декодировать файл\n";
        $this->sendMessage($chat_id, $message);
    }

    private function sendMenu(int $chat_id)
    {
        $keyboard = [
            'keyboard' => [[['text' => "1"], ['text' => "2"]]],
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ];

        $url = "https://api.telegram.org/bot{$this->token}/sendMessage?" .
            http_build_query([
                'chat_id' => $chat_id,
                'text' => "Выберите действие:",
                'reply_markup' => json_encode($keyboard)
            ]);
        file_get_contents($url);
    }

    private function getFilePath(string $file_id): string
    {
        $url = "https://api.telegram.org/bot{$this->token}/getFile?file_id={$file_id}";
        $response = file_get_contents($url);
        $response = json_decode($response, true);
        return $response['result']['file_path'] ?? '';
    }

    private function sendDocument(int $chat_id, string $file_path, string $filename)
    {
        if (!file_exists($file_path)) {
            $this->sendMessage($chat_id, "❌ Обработанный файл не найден: $filename");
            return;
        }

        $url = "https://api.telegram.org/bot{$this->token}/sendDocument";

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
}
