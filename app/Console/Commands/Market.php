<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;
use function Ratchet\Client\connect;

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
        connect('wss://api.huobi.pro/ws')->then(function ($conn) {
            $conn->on('message', function ($msg) use ($conn) {
                $this->info('接收');
                echo "Received: {$msg}\n";
                $conn->close();
            });
            
            $this->info('发送');
            $conn->send(json_encode([
                "sub" => "market.btcusdt.kline.1min",
                "id" => "id1"
            ]));
        }, function ($e) {
            $this->info('错误');
            echo "Could not connect: {$e->getMessage()}\n";
        });
        
        return CommandAlias::SUCCESS;
    }
    
}
