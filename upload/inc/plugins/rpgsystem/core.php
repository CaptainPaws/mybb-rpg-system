<?php
namespace RPGSystem;

class Core
{
    private static ?Core $instance = null;

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
        // Register a module for later use
    }
}
