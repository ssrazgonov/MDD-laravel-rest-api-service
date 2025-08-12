<?php

namespace App\Core\Abstract;

use App\Core\Interfaces\ModuleInterface;
use Illuminate\Support\Facades\File;
use ReflectionClass;

abstract class AbstractModule implements ModuleInterface
{
    protected string $name;
    protected string $path;
    protected array $providers = [];

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getProviders(): array
    {
        return $this->providers;
    }

    public function boot(): void
    {
        $this->setupPath();
        $this->registerConfig();
        $this->registerProviders();
        $this->registerRoutes();
        $this->registerMigrations();
    }

    protected function registerConfig(): void
    {
        $configFileName = $this->getName() . '.php';
        $configKey = $this->getName();
        $configPath = $this->getPath() . '/config/' . $configFileName;

        if (File::exists($configPath)) {
            $this->mergeConfigFrom($configPath, $configKey);
        }
    }

    protected function registerProviders(): void
    {
        $app = app();
        
        foreach ($this->getProviders() as $provider) {
            $app->register($provider);
        }
    }

    protected function registerRoutes(): void
    {
        $routesPath = $this->path . '/Http/routes.php';
        
        if (file_exists($routesPath)) {
            require $routesPath;
        }
    }

    protected function registerMigrations(): void
    {
        $migrationsPath = $this->path . '/Database/Migrations';
        
        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }

    protected function mergeConfigFrom(string $path, string $key): void
    {
        $config = app('config');
        
        $config->set($key, array_merge(
            require $path,
            $config->get($key, [])
        ));
    }

    protected function loadMigrationsFrom(string $path): void
    {
        app('migrator')->path($path);
    }
    
    protected function setupPath(): void
    {
        try {
            $reflection = new ReflectionClass($this);
            $this->path = dirname($reflection->getFileName());
        } catch (\ReflectionException $e) {
            throw new \RuntimeException("Unable to determine module path for " . static::class . ": " . $e->getMessage());
        }    
    }
}