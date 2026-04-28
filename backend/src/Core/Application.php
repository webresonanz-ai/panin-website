<?php

namespace App\Core;

use ReflectionClass;
use ReflectionNamedType;

class Application
{
    private array $instances = [];

    public function __construct(private readonly Config $config)
    {
        $this->instances[Config::class] = $config;
        $this->instances[self::class] = $this;
        $this->instances[Database::class] = new Database($config);
    }

    public function config(): Config
    {
        return $this->config;
    }

    public function make(string $class): object
    {
        if (isset($this->instances[$class])) {
            return $this->instances[$class];
        }

        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $this->instances[$class] = new $class();
        }

        $dependencies = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                throw new ApiException("Unable to resolve dependency for {$class}.", 500);
            }

            $dependencies[] = $this->make($type->getName());
        }

        return $this->instances[$class] = $reflection->newInstanceArgs($dependencies);
    }
}
