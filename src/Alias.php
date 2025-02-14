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
     * Pre-execution hooks.
     * Each hook receives: alias name and reference to the arguments array.
     */
    protected static $preHooks = [];

    /**
     * Post-execution hooks.
     * Each hook receives: alias name, arguments array, and the result.
     */
    protected static $postHooks = [];

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
        if (isset($alias['args']) && is_array($alias['args'])) {
            $expected = count($alias['args']);
            if (count($args) < $expected) {
                throw new \InvalidArgumentException("Alias '{$name}' expects at least {$expected} arguments.");
            }
        }

        // Run the alias callable.
        $result = call_user_func_array($action, $args);

        // Execute post-hooks.
        self::executePostHooks($name, $args, $result);

        return $result;
    }

    /**
     * Register a pre-execution hook.
     *
     * The hook is a callable that receives (string $alias, array &$args).
     *
     * @param callable $hook
     */
    public static function registerPreHook(callable $hook): void
    {
        self::$preHooks[] = $hook;
    }

    /**
     * Register a post-execution hook.
     *
     * The hook is a callable that receives (string $alias, array $args, $result).
     *
     * @param callable $hook
     */
    public static function registerPostHook(callable $hook): void
    {
        self::$postHooks[] = $hook;
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

    /**
     * Execute all post-hooks.
     */
    protected static function executePostHooks(string $name, array $args, $result): void
    {
        foreach (self::$postHooks as $hook) {
            call_user_func($hook, $name, $args, $result);
        }
    }

    /**
     * Get aliases filtered by group.
     *
     * @param string $group
     * @return array
     */
    public static function getAliasesByGroup(string $group): array
    {
        $result = [];
        foreach (self::$aliases as $name => $data) {
            if (isset($data['group']) && $data['group'] === $group) {
                $result[$name] = $data;
            }
        }
        return $result;
    }

    /**
     * Change the group of an alias.
     *
     * @param string $name
     * @param string $group
     * @throws \Exception if alias is not defined.
     */
    public static function changeGroup(string $name, string $group): void
    {
        if (! isset(self::$aliases[$name])) {
            throw new \Exception("Alias '{$name}' not defined.");
        }

        self::$aliases[$name]['group'] = $group;
    }

    /**
     * Load alias definitions from a config file.
     *
     * The file should return an array where each key is an alias name,
     * and the value is an array with keys:
     *   - action: callable
     *   - options: optional array (group, args, etc.)
     *
     * @param string $filepath
     * @throws \Exception
     */
    public static function loadFromConfigFile(string $filepath): void
    {
        if (! file_exists($filepath)) {
            throw new \Exception("Configuration file not found: {$filepath}");
        }
        $config = include $filepath;
        if (! is_array($config)) {
            throw new \Exception("Configuration file must return an array.");
        }

        foreach ($config as $name => $aliasConfig) {
            if (isset($aliasConfig['action']) && is_callable($aliasConfig['action'])) {
                self::register($name, $aliasConfig['action'], $aliasConfig['options'] ?? []);
            }
        }
    }

    /**
     * Create a chained alias callable from multiple alias names.
     * When executed, it runs each alias in order.
     *
     * @param string ...$names
     * @return callable
     */
    public static function chain(string ...$names): callable
    {
        return function (...$args) use ($names) {
            $result = null;
            foreach ($names as $aliasName) {
                $result = self::run($aliasName, ...$args);
            }
            return $result;
        };
    }

}
