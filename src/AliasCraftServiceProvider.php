<?php
namespace AliasCraft;

use Illuminate\Support\ServiceProvider;

class AliasCraftServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/aliascraft.php' => config_path('aliascraft.php'),
        ], 'config');

        $configPath = config_path('aliascraft.php');
        if (file_exists($configPath)) {
            $config = include $configPath;
            if (isset($config['aliases']) && is_array($config['aliases'])) {
                foreach ($config['aliases'] as $name => $definition) {
                    if (isset($definition['action']) && is_callable($definition['action'])) {
                        Alias::register($name, $definition['action'], $definition['options'] ?? []);
                    }
                }
            }
        }

    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/aliascraft.php', 'aliascraft');
    }
}
