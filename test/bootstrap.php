<?php

declare(strict_types=1);

namespace {
    // Autoload if vendor is available; tests also work with lightweight stubs below.
    @require dirname(__DIR__) . '/vendor/autoload.php';

    // Simple PSR-4 autoloader for HelpAssistant classes when composer autoload is absent.
    spl_autoload_register(function ($class): void {
        $prefix = 'HelpAssistant\\';

        if (strpos($class, $prefix) !== 0) {
            return;
        }

        $relative = substr($class, strlen($prefix));
        $baseDir = dirname(__DIR__);

        if ($relative === 'Module') {
            $file = $baseDir . '/Module.php';
        } else {
            $file = $baseDir . '/src/' . str_replace('\\', '/', $relative) . '.php';
        }

        if (is_readable($file)) {
            require_once $file;
        }
    });
}
