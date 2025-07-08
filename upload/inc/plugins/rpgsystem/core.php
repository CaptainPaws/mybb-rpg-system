<?php

namespace RPGSystem;

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

use MyBB;

class Core
{
    private static ?Core $instance = null;

    /** @var array<string, object> */
    private array $modules = [];

    public static function getInstance(): Core
    {
        if (self::$instance === null) {
            self::$instance = new Core();
        }
        return self::$instance;
    }

    public function registerModule(string $name, object $handler): void
    {
        $this->modules[$name] = $handler;
        if (method_exists($handler, 'registerHooks')) {
            $handler->registerHooks();
        }
    }

    public function getModule(string $name): ?object
    {
        return $this->modules[$name] ?? null;
    }

    public function loadEnabledModules(): void
    {
        global $mybb, $db;

        $query = $db->simple_select('rpgsystem_modules', '*', 'active=1');
        while ($mod = $db->fetch_array($query)) {
            $folderName = strtolower($mod['name']);
            $folder = MYBB_ROOT . 'inc/plugins/rpgsystem/modules/' . $folderName . '/';
            $classFile = $folder . ucfirst($folderName) . '.php';
            if (file_exists($classFile)) {
                require_once $classFile;
                $className = "\\RPGSystem\\Modules\\" . ucfirst($folderName);
                if (class_exists($className)) {
                    $this->registerModule($mod['name'], new $className());
                }
            }
        }
    }

    public function scanAndRegisterModules(bool $writeToDb = false): void
    {
        global $db;

        $modulesDir = __DIR__ . '/modules';
        foreach (glob($modulesDir . '/*/config.php') as $configPath) {
            $config = include $configPath;
            if (!is_array($config) || empty($config['name'])) {
                continue;
            }

            if ($writeToDb) {
                $exists = $db->fetch_field(
                    $db->simple_select('rpgsystem_modules', 'id', "name='" . $db->escape_string($config['name']) . "'"),
                    'id'
                );
                if (!$exists) {
                    $insert = [
                        'name' => $db->escape_string($config['name']),
                        'title' => $db->escape_string($config['title'] ?? $config['name']),
                        'version' => $db->escape_string($config['version'] ?? ''),
                        'active' => 0
                    ];
                    $db->insert_query('rpgsystem_modules', $insert);
                }
            }
        }
    }
}
// Принудительная инициализация ядра на всех страницах
if (!defined('IN_ADMINCP') && !isset($GLOBALS['rpgsystem_loaded'])) {
    $GLOBALS['rpgsystem_loaded'] = true;
    \RPGSystem\Core::getInstance()->loadEnabledModules();
}
