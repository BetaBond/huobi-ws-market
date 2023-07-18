<?php

namespace App\WebSocket;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use SplObjectStorage;

/**
 * Websocket 服务
 *
 * @author beta
 */
class Server implements MessageComponentInterface
{
    
    /**
     * 连接客户端
     *
     * @var SplObjectStorage
     */
    protected SplObjectStorage $clients;
    
    /**
     * 指令类
     *
     * @var Command
     */
    private Command $command;
    
    /**
     * 构造服务
     *
     * @return void
     */
    public function __construct(Command $command)
    {
        $this->clients = new SplObjectStorage;
        $this->command = $command;
    }
    
    /**
     * 建立连接
     *
     * @param  ConnectionInterface  $conn
     *
     * @return void
     */
    public function onOpen(ConnectionInterface $conn): void
    {
        $this->clients->attach($conn);
        $this->command->info('onOpen: 连接启动');
    }
    
    /**
     * 处理消息
     *
     * @param  ConnectionInterface  $from
     * @param  mixed  $msg
     *
     * @return void
     */
    public function onMessage(ConnectionInterface $from, mixed $msg): void
    {
        $this->command->info("onMessage: $msg");
        $from->send('111');
        foreach ($this->clients as $client) {
            $this->command->info($from != $client ? '1' : '0');
            if ($from != $client) {
                $data = json_decode($msg, true);
                
                
                
                // 请求必须为 JSON
                if (!is_array($data)) {
                    $this->command->info("75");
                    $client->send('64');
                    continue;
                }
                
                // 必须参数
                if (!isset($data['id'])) {
                    $this->command->info("82");
                    $client->send('70');
                    continue;
                }
                
                // 处理一次性拉取K线请求
                if (isset($data['req'])) {
                    $req = Cache::get($data['req'], []);
                    $client->send(json_encode($req, JSON_UNESCAPED_UNICODE));
                    return;
                }
                
                // 处理CH请求
                if (isset($data['ch'])) {
                    $ch = Cache::get($data['ch'], []);
                    $client->send(json_encode($ch, JSON_UNESCAPED_UNICODE));
                    return;
                }
                
                $client->send(json_encode($data, JSON_UNESCAPED_UNICODE));
            }
        }
    }
    
    /**
     * 连接关闭
     *
     * @param  ConnectionInterface  $conn
     *
     * @return void
     */
    public function onClose(ConnectionInterface $conn): void
    {
        $this->clients->detach($conn);
        $this->command->info('onClose: 连接关闭');
    }
    
    /**
     * 发送错误
     *
     * @param  ConnectionInterface  $conn
     * @param  Exception  $e
     *
     * @return void
     */
    public function onError(ConnectionInterface $conn, Exception $e): void
    {
        $conn->close();
        $this->command->info('onError: 发生错误 ('.$e->getMessage().')');
    }
    
}