<?php
namespace RPGSystem;

class Core
{
    /** Единственный экземпляр ядра */
    private static ?self $instance = null;

    /** @var array<string,object> зарегистрированные модули */
    private array $modules = [];

    /** Запрещаем прямое создание объекта */
    private function __construct()
    {
        // Здесь можно автоматически подключать базовые модули,
        // если захочешь делать автозагрузку.
    }

    /** Получаем (или создаём) singleton-экземпляр */
    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    /** Регистрируем модуль для дальнейшего использования */
    public function registerModule(string $name, object $module): void
    {
        $this->modules[$name] = $module;
    }

    /** Получаем модуль по имени или null */
    public function getModule(string $name): ?object
    {
        return $this->modules[$name] ?? null;
    }
}
