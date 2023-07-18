<?php

namespace App\WebSocket;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
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
     * 订阅集合
     *
     * @var array
     */
    private array $subs = [];
    
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
        $conn->send(json_encode(['ping' => time()]));
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
        
        $data = json_decode($msg, true);
        
        // 请求必须为 JSON
        if (!is_array($data)) {
            $this->command->info("onMessage: NO JSON");
            return;
        }
        
        // 必须参数
        if (!isset($data['id']) && !isset($data['pong'])) {
            $this->command->info("onMessage: NO HAVE ID");
            return;
        }
        
        // 处理一次性拉取K线请求
        if (isset($data['req'])) {
            $req = Cache::get('kline.rep.'.$data['req'], []);
            $from->send(json_encode($req, JSON_UNESCAPED_UNICODE));
            return;
        }
        
        // 处理订阅请求
        if (isset($data['sub'])) {
            $ch = Cache::get($data['sub'], []);
            $from->send(json_encode($ch, JSON_UNESCAPED_UNICODE));
            // 订阅
            /** @noinspection PhpUndefinedFieldInspection */
            $this->subs[$from->resourceId][$data['sub']] = $from;
        }
        
        // 处理 PONG
        if (isset($data['pong'])) {
            $from->send(json_encode(['ping' => time()]));
            
            /** @noinspection PhpUndefinedFieldInspection */
//            foreach (array_keys($this->subs[$from->resourceId]) as $sub) {
//                $ch = Cache::get($sub, []);
//                $from->send(json_encode($ch, JSON_UNESCAPED_UNICODE));
//            }
//
//
//            sleep(10);
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
        // 注销订阅
        /** @noinspection PhpUndefinedFieldInspection */
        unset($this->subs[$conn->resourceId]);
        
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