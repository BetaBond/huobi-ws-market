<?php

namespace App\WebSocket;

use Exception;
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
     * 构造服务
     *
     * @return void
     */
    public function __construct()
    {
        $this->clients = new SplObjectStorage;
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
        foreach ($this->clients as $client) {
            if ($from != $client) {
                $data = json_decode($msg, true);
                
                // 请求必须为 JSON
                if (!is_array($data)) {
                    continue;
                }
                
                // 必须参数
                if (!isset($data['id'])) {
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
                    $req = Cache::get($data['ch'], []);
                    $client->send(json_encode($req, JSON_UNESCAPED_UNICODE));
                    return;
                }
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
    }
    
}