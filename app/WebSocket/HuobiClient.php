<?php

namespace App\WebSocket;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use function Ratchet\Client\connect;

/**
 * 火币数据客户端
 *
 * @author beta
 */
class HuobiClient
{
    
    /**
     * 接口地址
     *
     * @var string
     */
    const API = 'wss://api.huobi.pro/ws';
    
    /**
     * WebSocket 连接
     *
     * @var mixed
     */
    private mixed $conn;
    
    /**
     * 指令类
     *
     * @var Command
     */
    private Command $command;
    
    /**
     * 构造火币客户端
     */
    public function __construct(Command $command)
    {
        $this->command = $command;
        
        connect(self::API)->then(function ($conn) {
            
            // 接收消息
            $conn->on('message', function (mixed $msg) use ($conn) {
                $this->message($msg);
            });
            
            // 连接关闭
            $conn->on('close', function ($code = null, $reason = null) {
                Log::warning("CLOSE: 连接已被关闭 ($code - $reason)");
                $this->command->info("CLOSE: 连接已被关闭 ($code - $reason)");
            });
            
            $this->conn = $conn;
            $this->subscribe();
            
        }, function ($e) {
            Log::error('ERROR: 连接失败 ('.$e->getMessage().')');
            $this->command->info('ERROR: 连接失败 ('.$e->getMessage().')');
        });
    }
    
    /**
     * 订阅处理
     *
     * @return void
     */
    public function subscribe(): void
    {
        $periods = [
            '1min', '5min', '15min',
            '30min', '60min', '4hour', '1day'
        ];
        
        // 从 Cache 中取出需要订阅的代币
        $tokens = Cache::get('subscribe.tokens', []);
        
        // 遍历订阅
        foreach ($tokens as $token) {
            foreach ($periods as $period) {
                
                $klineSubKey = "market.$token.kline.$period";
                $klineReqKey = "market.$token.kline.$period";
                
                // 先取消订阅以防止重复订阅
                $this->conn->send(json_encode([
                    "unsub" => $klineSubKey,
                    "id"    => "sub.$klineSubKey"
                ]));
                
                // 订阅K线数据
                $this->conn->send(json_encode([
                    "sub" => $klineSubKey,
                    "id"  => "sub.$klineSubKey"
                ]));
                
                Log::info("SUBSCRIBE: 订阅K线 ($klineSubKey)");
                $this->command->info("SUBSCRIBE: 订阅K线 ($klineSubKey)");
                
                // 一次性拉取订阅K线数据
                $this->conn->send(json_encode([
                    'req' => $klineReqKey,
                    "id"  => "rep.$klineReqKey"
                ]));
                
                Log::info("SUBSCRIBE: 一次性订阅K线 ($klineReqKey)");
                $this->command->info("SUBSCRIBE: 一次性订阅K线 ($klineReqKey)");
                
            }
        }
        
        Log::info("SUBSCRIBE: 订阅完成");
        $this->command->info("SUBSCRIBE: 订阅完成");
    }
    
    /**
     * 消息处理
     *
     * @param  mixed  $msg
     *
     * @return void
     */
    public function message(mixed $msg): void
    {
        $data = gzdecode($msg);
        $data = json_decode($data, true);
        
        // PING
        if (isset($data['ping'])) {
            $this->conn->send(json_encode([
                "pong" => $data['ping']
            ]));
            
            return;
        }
        
        // 一次性拉取订阅K线数据
        if (isset($data['rep'])) {
            if (!isset($data['status']) || $data['status'] !== 'ok') {
                return;
            }
            
            if (!isset($data['data']) || !is_array($data['data'])) {
                return;
            }
            
            if (!isset($data['id']) || empty($data['data'])) {
                return;
            }
            
            $cache = Cache::put($data['id'], $data['data']);
            
            if (!$cache) {
                Log::error('MESSAGE: REP缓存失败 ('.$data['id'].')');
                $this->command->info('MESSAGE: REP缓存失败 ('.$data['id'].')');
            }
            
            return;
        }
        
        
    }
    
}