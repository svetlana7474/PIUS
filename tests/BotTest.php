<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../vendor/autoload.php';

use App\Bot;

class BotTest extends TestCase
{
    private $bot;
    private $token = 'test_token';
    private $saveDir;

    protected function setUp(): void
    {
        $this->saveDir = __DIR__ . '/files_test';
        if (!file_exists($this->saveDir)) {
            mkdir($this->saveDir, 0777, true);
        }

        
        $this->bot = new Bot($this->token, $this->saveDir);
    }

    protected function tearDown(): void
    {
        
        array_map('unlink', glob("$this->saveDir/*"));
        rmdir($this->saveDir);
    }

    public function testSendMessage()
    {
        
        $mock = $this->getMockBuilder(Bot::class)
            ->setConstructorArgs([$this->token, $this->saveDir])
            ->onlyMethods(['sendMessage'])
            ->getMock();

        $mock->expects($this->once())
             ->method('sendMessage')
             ->with($this->equalTo(123), $this->equalTo('Тестовое сообщение'));

        
        $mock->sendMessage(123, 'Тестовое сообщение');
    }

    public function testProcessTextMessageStart()
    {
        $chat_id = 123;

        
        $mock = $this->getMockBuilder(Bot::class)
            ->setConstructorArgs([$this->token, $this->saveDir])
            ->onlyMethods(['sendWelcomeMessage', 'sendMenu'])
            ->getMock();

        $mock->expects($this->once())->method('sendWelcomeMessage')->with($chat_id);
        $mock->expects($this->once())->method('sendMenu')->with($chat_id);

        $reflection = new ReflectionClass(Bot::class);
        $method = $reflection->getMethod('processTextMessage');
        $method->setAccessible(true);

        $method->invoke($mock, $chat_id, '/start');
    }

    public function testProcessTextMessageUnknown()
    {
        $chat_id = 123;
        $text = "Привет";

        $mock = $this->getMockBuilder(Bot::class)
            ->setConstructorArgs([$this->token, $this->saveDir])
            ->onlyMethods(['sendMessage'])
            ->getMock();

        $mock->expects($this->once())
            ->method('sendMessage')
            ->with($chat_id, "Вы выбрали: " . $text);

        $reflection = new ReflectionClass(Bot::class);
        $method = $reflection->getMethod('processTextMessage');
        $method->setAccessible(true);

        $method->invoke($mock, $chat_id, $text);
    }

    
}
