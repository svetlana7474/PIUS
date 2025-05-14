<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class BlowfishController extends Controller
{
    public function process(Request $request)
    {

            
        
        return response()->json(['message' => 'API работает']);
        
        // Вытаскиваем параметры из запроса
        $userId = $request->input('user_id');
        $filePath = $request->input('path');
        $action = $request->input('action');

        // Проверяем, существует ли файл
        if (!file_exists($filePath)) {
            return response()->json(['error' => 'Файл не найден'], 400);
        }

        // Логика шифрования или дешифрования
        $resultPath = null;
        if ($action === 'encrypt') {
            // Шифрование файла
            $resultPath = $this->encryptFile($filePath);
        } elseif ($action === 'decrypt') {
            // Дешифрование файла
            $resultPath = $this->decryptFile($filePath);
        }

        if ($resultPath) {
            return response()->json(['encryptedFilePath' => $resultPath], 200);
        }

        return response()->json(['error' => 'Не удалось обработать файл'], 500);
    }

    private function encryptFile($filePath)
    {
        // Пример шифрования
        // Замените это на реальную логику шифрования Blowfish
        $newFilePath = $filePath . '.enc';
        copy($filePath, $newFilePath); // Просто копируем файл как пример
        return $newFilePath;
    }

    private function decryptFile($filePath)
    {
        // Пример дешифрования
        // Замените это на реальную логику дешифрования Blowfish
        $newFilePath = $filePath . '.dec';
        copy($filePath, $newFilePath); // Просто копируем файл как пример
        return $newFilePath;
    }
}
