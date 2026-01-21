<?php

/**
 * Hook Manager - Manages WordPress hooks registration
 *
 * @package SimpleLMS
 * @since 1.0.0
 */

namespace SimpleLMS\Managers;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hook Manager Class
 *
 * Centralized management of WordPress actions and filters.
 * Replaces scattered add_action/add_filter calls with structured approach.
 */
class HookManager
{
    /**
     * Registered hooks
     *
     * @var array<array{type: string, hook: string, callback: callable, priority: int, args: int}>
     */
    private array $hooks = [];

    /**
     * Service container
     *
     * @var \SimpleLMS\ServiceContainer
     */
    private $container;

    /**
     * Constructor
     *
     * @param \SimpleLMS\ServiceContainer $container Service container
     */
    public function __construct(\SimpleLMS\ServiceContainer $container)
    {
        $this->container = $container;
    }

    /**
     * Add an action hook
     *
     * @param string   $hook     Hook name
     * @param callable $callback Callback function
     * @param int      $priority Priority (default: 10)
     * @param int      $args     Number of arguments (default: 1)
     * @return self For method chaining
     */
    public function addAction(string $hook, callable $callback, int $priority = 10, int $args = 1): self
    {
        $this->hooks[] = [
            'type' => 'action',
            'hook' => $hook,
            'callback' => $callback,
            'priority' => $priority,
            'args' => $args,
        ];

        \add_action($hook, $callback, $priority, $args);

        return $this;
    }

    /**
     * Add a filter hook
     *
     * @param string   $hook     Hook name
     * @param callable $callback Callback function
     * @param int      $priority Priority (default: 10)
     * @param int      $args     Number of arguments (default: 1)
     * @return self For method chaining
     */
    public function addFilter(string $hook, callable $callback, int $priority = 10, int $args = 1): self
    {
        $this->hooks[] = [
            'type' => 'filter',
            'hook' => $hook,
            'callback' => $callback,
            'priority' => $priority,
            'args' => $args,
        ];

        \add_filter($hook, $callback, $priority, $args);

        return $this;
    }

    /**
     * Remove an action hook
     *
     * @param string   $hook     Hook name
     * @param callable $callback Callback function
     * @param int      $priority Priority (default: 10)
     * @return bool True if removed, false otherwise
     */
    public function removeAction(string $hook, callable $callback, int $priority = 10): bool
    {
        return \remove_action($hook, $callback, $priority);
    }

    /**
     * Remove a filter hook
     *
     * @param string   $hook     Hook name
     * @param callable $callback Callback function
     * @param int      $priority Priority (default: 10)
     * @return bool True if removed, false otherwise
     */
    public function removeFilter(string $hook, callable $callback, int $priority = 10): bool
    {
        return \remove_filter($hook, $callback, $priority);
    }

    /**
     * Register multiple hooks from an array
     *
     * @param array $hooks Array of hooks configuration
     *                     Format: [
     *                         ['type' => 'action|filter', 'hook' => 'hook_name', 'callback' => callable, 'priority' => 10, 'args' => 1],
     *                         ...
     *                     ]
     * @return self For method chaining
     */
    public function registerHooks(array $hooks): self
    {
        foreach ($hooks as $hookConfig) {
            $type = $hookConfig['type'] ?? 'action';
            $hook = $hookConfig['hook'];
            $callback = $hookConfig['callback'];
            $priority = $hookConfig['priority'] ?? 10;
            $args = $hookConfig['args'] ?? 1;

            if ($type === 'filter') {
                $this->addFilter($hook, $callback, $priority, $args);
            } else {
                $this->addAction($hook, $callback, $priority, $args);
            }
        }

        return $this;
    }

    /**
     * Get all registered hooks
     *
     * @return array<array{type: string, hook: string, callback: callable, priority: int, args: int}>
     */
    public function getHooks(): array
    {
        return $this->hooks;
    }

    /**
     * Get hooks by type (action or filter)
     *
     * @param string $type Hook type ('action' or 'filter')
     * @return array<array{type: string, hook: string, callback: callable, priority: int, args: int}>
     */
    public function getHooksByType(string $type): array
    {
        return array_filter($this->hooks, fn($hook) => $hook['type'] === $type);
    }

    /**
     * Get hooks by name
     *
     * @param string $hookName Hook name to filter by
     * @return array<array{type: string, hook: string, callback: callable, priority: int, args: int}>
     */
    public function getHooksByName(string $hookName): array
    {
        return array_filter($this->hooks, fn($hook) => $hook['hook'] === $hookName);
    }

    /**
     * Check if a hook is registered
     *
     * @param string   $hook     Hook name
     * @param callable $callback Callback function
     * @return bool
     */
    public function hasHook(string $hook, callable $callback): bool
    {
        foreach ($this->hooks as $registeredHook) {
            if ($registeredHook['hook'] === $hook && $registeredHook['callback'] === $callback) {
                return true;
            }
        }

        return false;
    }

    /**
     * Clear all registered hooks (useful for testing)
     *
     * @return void
     */
    public function clear(): void
    {
        $this->hooks = [];
    }
}
