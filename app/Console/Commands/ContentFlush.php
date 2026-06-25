<?php

namespace App\Console\Commands;

use App\Content\ContentRepository;
use Illuminate\Console\Command;

class ContentFlush extends Command
{
    protected $signature = 'content:flush';

    protected $description = 'Clear the cached flat-file content (run on deploy after editing /content)';

    public function handle(ContentRepository $content): int
    {
        $content->flush();

        $this->info('Content cache flushed.');

        return self::SUCCESS;
    }
}
