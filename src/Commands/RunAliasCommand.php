<?php
namespace AliasCraft\Commands;

use AliasCraft\Alias;
use Illuminate\Console\Command;

class RunAliasCommand extends Command
{
    protected $signature   = 'aliascraft:run {alias : The alias to execute} {args?* : Arguments for the alias}';
    protected $description = 'Run a registered alias with optional arguments';

    public function handle(): int
    {
        $alias = $this->argument('alias');
        $args  = $this->argument('args');

        try {
            $result = Alias::run($alias, ...$args);
            $this->info("Alias '{$alias}' executed successfully.");
            if (! is_null($result)) {
                $this->line("Result: " . print_r($result, true));
            }
        } catch (\Exception $ex) {
            $this->error($ex->getMessage());
            return 1;
        }
        return 0;
    }
}
