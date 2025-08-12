<?php

namespace App\Providers;

use App\Core\Interfaces\ModuleInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerModules();
    }

    public function boot(): void
    {
        $this->bootModules();
    }

    protected function registerModules(): void
    {
        $modules = $this->getModules();

        foreach ($modules as $module) {
            $providers = $module->getProviders();
            foreach ($providers as $provider) {
                if (class_exists($provider)) {
                    $this->app->register($provider);
                } else {
                   Log::warning("Service provider class not found for module {$module->getName()}: {$provider}");
                }
            }
        }
    }

    protected function bootModules(): void
    {
        $modules = $this->getModules();

        foreach ($modules as $module) {
            $moduleName = $module->getName();
            
            try {
                $module->boot();
            } catch (\Throwable $e) {
                Log::error("Error booting module [{$moduleName}]: " . $e->getMessage(), [
                    'exception' => $e
                ]);

                throw $e;
            }
        }
    }

    /**
     * @return ModuleInterface[]
     */
    protected function getModules(): array
    {
        $modules = [];
        $config = config('modules');

        foreach ($config['modules'] as $moduleClass) {
            if (class_exists($moduleClass)) {
                try {
                    $moduleInstance = new $moduleClass();

                    if ($moduleInstance instanceof ModuleInterface) {
                        $modules[] = $moduleInstance;
                    } else {
                        Log::warning("Module class {$moduleClass} does not implement ModuleInterface.");
                    }
                } catch (\Throwable $e) {
                    Log::error("Error instantiating module class {$moduleClass}: " . $e->getMessage());
                }
            } else {
                Log::warning("Module class not found: {$moduleClass}");
            }
        }

        return $modules;
    }


}
