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
class Sub extends Command
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
        $token = $this->ask('您想要订阅的代币(多个以 `,` 分割)?');
        $tokenSub = explode(',', $token);

        $tokens = Cache::get('subscribe.tokens', []);

        foreach ($tokenSub as $value) {
            if (in_array($value, $tokens)) {
                $this->warn('代币已经被订阅!');
                return CommandAlias::FAILURE;
            }

            $tokens[] = $value;
        }

        $cache = Cache::forever('subscribe.tokens', $tokens);

        if (!$cache) {
            $this->warn('订阅失败');
            return CommandAlias::FAILURE;
        }

        $this->info('订阅成功');

        return CommandAlias::SUCCESS;
    }

}
