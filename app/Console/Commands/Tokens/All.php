<?php

namespace App\Console\Commands\Tokens;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Console\Command\Command as CommandAlias;

/**
 * 获取所有代币
 *
 * @author beta
 */
class All extends Command
{
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:all';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '获取所有已经订阅的代币';
    
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $tokens = Cache::get('subscribe.tokens', []);
        
        foreach ($tokens as $token) {
            $this->info($token);
        }
        
        return CommandAlias::SUCCESS;
    }
    
}