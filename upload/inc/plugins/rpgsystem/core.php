<?php
namespace RPGSystem;

class Core
{
    private static ?Core $instance = null;

    /** @var array<string,object> */
    private array $modules = [];

    private function __construct()
    {
        // Modules can be loaded here in the future

    private function __construct()
    {
        // Load modules here

    }

    public static function getInstance(): Core
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function registerModule(string $name, object $module): void
    {
        $this->modules[$name] = $module;
    }

    public function getModule(string $name): ?object
    {
        return $this->modules[$name] ?? null;

        // Register a module for later use
    }
}
