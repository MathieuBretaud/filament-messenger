<?php

namespace MathieuBretaud\FilamentMessenger\Commands;

use Illuminate\Console\Command;

class FilamentMessengerCommand extends Command
{
    public $signature = 'filament-messenger';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
