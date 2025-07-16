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
        global $mybb, $db, $page;

        $query = $db->simple_select('rpgsystem_modules', '*', 'active=1');
        while ($mod = $db->fetch_array($query)) {
            $name = strtolower($mod['name']); // всегда нижний регистр
            $folder = MYBB_ROOT . "inc/plugins/rpgsystem/modules/{$name}/";
            $classFile = $folder . "{$name}.php";
            $className = "\\RPGSystem\\Modules\\{$name}\\{$name}";

            if (file_exists($classFile)) {
                require_once $classFile;

                if (class_exists($className)) {
                    $this->registerModule($name, new $className());
                }
            }

            // Добавим вкладку в ACP, если есть соответствующий файл
            if (defined('IN_ADMINCP')) {
                $adminDir = dirname(__FILE__, 4) . '/admin/modules/rpgstuff/';
                $adminModuleFile = $adminDir . "rpgsystem_{$name}.php";

                if (file_exists($adminModuleFile)) {
                    $this->addAdminModule($name, $mod['title']);
                }
            }

        }
    }

    private function addAdminModule(string $id, string $title): void
    {
        global $sub_menu;
        if (!isset($sub_menu)) return;

        $sub_menu[] = [
            'id' => 'rpgsystem_' . $id,
            'title' => $title,
            'link' => 'index.php?module=rpgstuff-rpgsystem_' . $id,
        ];
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
                        'name' => strtolower($db->escape_string($config['name'])), 
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

// Принудительная инициализация ядра на всех страницах (кроме ACP)
if (!defined('IN_ADMINCP') && !isset($GLOBALS['rpgsystem_loaded'])) {
    $GLOBALS['rpgsystem_loaded'] = true;
    \RPGSystem\Core::getInstance()->loadEnabledModules();
}
