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
    protected $description = '订阅代币';
    
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $tokens = Cache::get('subscribe.tokens', '[]');
        $tokens = json_decode($tokens, true);
        
        if (is_array($tokens)) {
            $this->warn('存储异常');
            return CommandAlias::FAILURE;
        }
        
        foreach ($tokens as $token) {
            $this->info($token);
        }
        
        return CommandAlias::SUCCESS;
    }
    
}