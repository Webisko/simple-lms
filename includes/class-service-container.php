<?php
/**
 * PSR-11 Compatible Service Container for Simple LMS
 *
 * @package SimpleLMS
 * @since 1.4.0
 */

declare(strict_types=1);

namespace SimpleLMS;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Container Interface (PSR-11 compatible)
 *
 * Simple interface for dependency injection container.
 * Compatible with PSR-11 but doesn't require Composer dependencies.
 */
interface ContainerInterface {
    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     * @return mixed Entry.
     */
    public function get(string $id);

    /**
     * Returns true if the container can return an entry for the given identifier.
     *
     * @param string $id Identifier of the entry to look for.
     * @return bool
     */
    public function has(string $id): bool;
}

/**
 * Not Found Exception Interface
 */
interface NotFoundExceptionInterface extends \Throwable {}

/**
 * Container Exception Interface
 */
interface ContainerExceptionInterface extends \Throwable {}

/**
 * Service Container Implementation
 *
 * Provides dependency injection container following PSR-11 standard.
 * Replaces Singleton pattern with proper DI for better testability.
 */
class ServiceContainer implements ContainerInterface
{
    /**
     * Container instance
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Registered services
     *
     * @var array<string, callable>
     */
    private array $services = [];

    /**
     * Resolved service instances (singletons)
     *
     * @var array<string, object>
     */
    private array $instances = [];

    /**
     * Shared services (always return same instance)
     *
     * @var array<string, bool>
     */
    private array $shared = [];

    /**
     * Private constructor to enforce singleton pattern for container itself
     */
    private function __construct()
    {
        // Container itself is a singleton, but services use proper DI
    }

    /**
     * Get container instance
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register a service in the container
     *
     * @param string   $id       Service identifier
     * @param callable $factory  Factory function that creates the service
     * @param bool     $shared   Whether to share the instance (singleton)
     * @return void
     */
    public function register(string $id, callable $factory, bool $shared = true): void
    {
        $this->services[$id] = $factory;
        $this->shared[$id] = $shared;

        // Clear any existing instance if re-registering
        if (isset($this->instances[$id])) {
            unset($this->instances[$id]);
        }
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get(string $id)
    {
        if (!$this->has($id)) {
            throw new NotFoundException("Service '$id' not found in container");
        }

        // Return cached instance if service is shared and already resolved
        if ($this->shared[$id] && isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        // Resolve the service using the factory
        try {
            $service = ($this->services[$id])($this);

            // Cache if shared (singleton)
            if ($this->shared[$id]) {
                $this->instances[$id] = $service;
            }

            return $service;
        } catch (\Exception $e) {
            throw new ContainerException(
                "Error resolving service '$id': " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }

    /**
     * Register a singleton service (always shared)
     *
     * @param string   $id      Service identifier
     * @param callable $factory Factory function
     * @return void
     */
    public function singleton(string $id, callable $factory): void
    {
        $this->register($id, $factory, true);
    }

    /**
     * Register a factory service (new instance each time)
     *
     * @param string   $id      Service identifier
     * @param callable $factory Factory function
     * @return void
     */
    public function factory(string $id, callable $factory): void
    {
        $this->register($id, $factory, false);
    }

    /**
     * Register an existing instance as a service
     *
     * @param string $id       Service identifier
     * @param object $instance Service instance
     * @return void
     */
    public function instance(string $id, object $instance): void
    {
        $this->instances[$id] = $instance;
        $this->shared[$id] = true;

        // Register a factory that returns the instance
        $this->services[$id] = fn() => $instance;
    }

    /**
     * Bind an interface to an implementation
     *
     * @param string $interface Interface or abstract class name
     * @param string $concrete  Concrete class name
     * @param bool   $shared    Whether to share the instance
     * @return void
     */
    public function bind(string $interface, string $concrete, bool $shared = true): void
    {
        $this->register(
            $interface,
            fn($container) => new $concrete(),
            $shared
        );
    }

    /**
     * Resolve a class with automatic dependency injection
     *
     * @param string $class Class name to resolve
     * @return object Resolved instance
     * @throws ContainerException If class cannot be resolved
     */
    public function make(string $class): object
    {
        try {
            $reflector = new \ReflectionClass($class);

            if (!$reflector->isInstantiable()) {
                throw new ContainerException("Class '$class' is not instantiable");
            }

            $constructor = $reflector->getConstructor();

            // No constructor? Just instantiate
            if (null === $constructor) {
                return new $class();
            }

            $parameters = $constructor->getParameters();
            $dependencies = [];

            foreach ($parameters as $parameter) {
                $type = $parameter->getType();

                // Skip if no type hint
                if (null === $type || $type->isBuiltin()) {
                    // Try to get default value
                    if ($parameter->isDefaultValueAvailable()) {
                        $dependencies[] = $parameter->getDefaultValue();
                    } else {
                        throw new ContainerException(
                            "Cannot resolve parameter '{$parameter->getName()}' for class '$class'"
                        );
                    }
                    continue;
                }

                $typeName = $type->getName();

                // Try to resolve from container
                if ($this->has($typeName)) {
                    $dependencies[] = $this->get($typeName);
                } else {
                    // Recursively resolve the dependency
                    $dependencies[] = $this->make($typeName);
                }
            }

            return $reflector->newInstanceArgs($dependencies);
        } catch (\ReflectionException $e) {
            throw new ContainerException(
                "Cannot resolve class '$class': " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Call a method with dependency injection
     *
     * @param object|string $class  Object instance or class name
     * @param string        $method Method name
     * @param array         $params Additional parameters
     * @return mixed Method result
     * @throws ContainerException
     */
    public function call($class, string $method, array $params = [])
    {
        try {
            if (is_string($class)) {
                $class = $this->make($class);
            }

            $reflector = new \ReflectionMethod($class, $method);
            $parameters = $reflector->getParameters();
            $dependencies = [];

            foreach ($parameters as $parameter) {
                $name = $parameter->getName();

                // Use provided parameter if available
                if (isset($params[$name])) {
                    $dependencies[] = $params[$name];
                    continue;
                }

                $type = $parameter->getType();

                // Skip if no type hint
                if (null === $type || $type->isBuiltin()) {
                    if ($parameter->isDefaultValueAvailable()) {
                        $dependencies[] = $parameter->getDefaultValue();
                    } else {
                        throw new ContainerException(
                            "Cannot resolve parameter '$name' for method '$method'"
                        );
                    }
                    continue;
                }

                $typeName = $type->getName();

                if ($this->has($typeName)) {
                    $dependencies[] = $this->get($typeName);
                } else {
                    $dependencies[] = $this->make($typeName);
                }
            }

            return $reflector->invokeArgs($class, $dependencies);
        } catch (\ReflectionException $e) {
            throw new ContainerException(
                "Cannot call method '$method': " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Clear all instances (useful for testing)
     *
     * @return void
     */
    public function clear(): void
    {
        $this->instances = [];
    }

    /**
     * Get all registered service IDs
     *
     * @return array<string>
     */
    public function getServiceIds(): array
    {
        return array_keys($this->services);
    }

    /**
     * Check if a service has been resolved
     *
     * @param string $id Service identifier
     * @return bool
     */
    public function isResolved(string $id): bool
    {
        return isset($this->instances[$id]);
    }
}

/**
 * Container Not Found Exception
 */
class NotFoundException extends \Exception implements NotFoundExceptionInterface
{
}

/**
 * Container Exception
 */
class ContainerException extends \Exception implements ContainerExceptionInterface
{
}
