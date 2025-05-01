<?php

namespace Coolsam\Transactify\Commands;

use Illuminate\Console\Command;

class TransactifyCommand extends Command
{
    public $signature = 'transactify';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
