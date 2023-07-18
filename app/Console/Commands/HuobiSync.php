<?php

namespace App\Console\Commands;

use App\WebSocket\HuobiClient;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

/**
 * 火币数据同步
 *
 * @author beta
 */
class HuobiSync extends Command
{
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'huobi:sync';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '火币数据同步';
    
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        new HuobiClient($this);
        
        return CommandAlias::SUCCESS;
    }
    
}