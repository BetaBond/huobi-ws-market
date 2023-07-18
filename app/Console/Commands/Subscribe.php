<?php

namespace App\Console\Commands;

use App\WebSocket\Server;
use Illuminate\Console\Command;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Symfony\Component\Console\Command\Command as CommandAlias;

/**
 * 订阅代币
 *
 * @author beta
 */
class Subscribe extends Command
{
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websocket';
    
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
        $server = IoServer::factory(
            new HttpServer(new WsServer(
                new Server()
            )),
            8090
        );
        
        $server->run();
        
        return CommandAlias::SUCCESS;
    }
    
}