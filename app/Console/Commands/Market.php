<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;
use function Ratchet\Client\connect;

/**
 * 获取火币行情数据
 *
 * @author beta
 */
class Market extends Command
{
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'market:sync';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $tokens = ['ethusdt'];
        $periods = ['1min', '5min', '15min', '30min', '60min', '4hour', '1day'];
        
        $message = function ($conn, $msg) {
            $data = gzdecode($msg);
            $data = json_decode($data, true);
            
            // 处理请求
            if (isset($data['ping'])) {
                $conn->send(json_encode([
                    "pong" => $data['ping']
                ]));
                
                return;
            }
            
            $this->info(json_encode($data));
        };
        
        // 订阅消息
        $subscribe = function ($conn) use ($tokens, $periods) {
            
            foreach ($tokens as $token) {
                foreach ($periods as $period) {
                    
                    // 订阅K线数据
                    $conn->send(json_encode([
                        "sub" => "market.$token.kline.$period",
                        "id"  => "sub.market.$token.kline.$period"
                    ]));
                    
                    // 一次性拉取订阅
                    $conn->send(json_encode([
                        'req' => "market.$token.kline.$period",
                        "id"  => "req.market.$token.kline.$period"
                    ]));
                    
                }
            }
            
        };
        
        // 异步 websocket 客户端
        connect(
            'wss://api.huobi.pro/ws'
        )->then(function ($conn) use (
            $subscribe,
            $message
        ) {
            
            // 接收消息
            $conn->on('message', function (mixed $msg) use ($conn, $message) {
                $message($conn, $msg);
            });
            
            // 关闭消息
            $conn->on('close', function ($code = null, $reason = null) {
                $this->warn("CLOSE: 连接已被关闭 ($code - $reason)");
            });
            
            // 订阅消息
            $subscribe($conn);
            
        }, function ($e) {
            $this->error('ERROR: 连接失败 ('.$e->getMessage().')');
        });
        
        return CommandAlias::SUCCESS;
    }
    
}
