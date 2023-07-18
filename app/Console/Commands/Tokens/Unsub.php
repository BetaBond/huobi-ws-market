<?php

namespace App\Console\Commands\Tokens;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Console\Command\Command as CommandAlias;

/**
 * 订阅代币
 *
 * @author beta
 */
class Unsub extends Command
{
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:sub';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '订阅代币';
    
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $token = $this->ask('您想要取消订阅的代币?');
        
        if (!Cache::has($token)) {
            $this->warn('代币不存在, 可能从未订阅!');
            return CommandAlias::FAILURE;
        }
        
        $cache = Cache::forget('subscribe.tokens', $token);
        
        if (!$cache) {
            $this->warn('订阅失败');
            return CommandAlias::FAILURE;
        }
        
        $this->info('订阅成功');
        
        return CommandAlias::SUCCESS;
    }
    
}