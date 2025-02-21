<?php
namespace AliasCraft\Commands;

use AliasCraft\Alias;
use Illuminate\Console\Command;

class ListAliasesCommand extends Command
{
    protected $signature   = 'aliascraft:list';
    protected $description = 'List all registered aliases';

    public function handle(): int
    {
        if (empty($aliases)) {
            $this->info("No aliases registered.");
        } else {
            Alias::list();
        }
        return 0;
    }
}
