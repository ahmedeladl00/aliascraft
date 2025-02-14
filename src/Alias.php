<?php
namespace AliasCraft;

class Alias
{
    /**
     * Registered aliases.
     * Each alias is stored as an array:
     *   - action: callable
     *   - group: string|null
     *   - args: an array of expected argument names (optional)
     */
    protected static $aliases = [];

    /**
     * Register a new alias.
     *
     * @param string   $name    The alias name.
     * @param callable $action  The callable to execute.
     * @param array    $options Optional. Supports keys:
     *                          - group (string|null)
     *                          - args (array of argument names)
     */
    public static function register(string $name, callable $action, array $options = []): void
    {
        self::$aliases[$name] = [
            'action' => $action,
            'group'  => $options['group'] ?? null,
            'args'   => $options['args'] ?? [],
        ];
    }

    /**
     * Pre-execution hooks.
     * Each hook receives: alias name and reference to the arguments array.
     */
    protected static $preHooks = [];

    /**
     * Execute an alias.
     *
     * @param string $name The alias name.
     * @param mixed  ...$args The arguments to pass.
     *
     * @return mixed
     * @throws \Exception if alias is not defined or if argument count is insufficient.
     */
    public static function run(string $name, ...$args): mixed
    {
        if (! isset(self::$aliases[$name])) {
            throw new \Exception("Alias '{$name}' not defined.");
        }

        $alias  = self::$aliases[$name];
        $action = $alias['action'];

        // Execute pre-hooks
        self::executePreHooks($name, $args);

        // Validate arguments if a signature is provided.
        if (! empty($alias['args'])) {
            $expected = count($alias['args']);
            if (count($args) < $expected) {
                throw new \InvalidArgumentException("Alias '{$name}' expects at least {$expected} arguments.");
            }
        }

        // Run the alias callable.
        $result = call_user_func_array($action, $args);

        return $result;
    }

    /**
     * Register a pre-execution hook.
     *
     * The hook is a callable that receives (string $alias, array &$args).
     *
     * @param callable $hook
     */
    public static function addPreHook(callable $hook): void
    {
        self::$preHooks[] = $hook;
    }

    /**
     * Execute all pre-hooks.
     */
    protected static function executePreHooks(string $name, array &$args): void
    {
        foreach (self::$preHooks as $hook) {
            call_user_func($hook, $name, $args);
        }
    }
}
