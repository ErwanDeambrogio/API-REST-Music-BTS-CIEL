
<?php

use App\Application\Settings\Settings;
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Logger;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        SettingsInterface::class => function () {
            return new Settings([
                'displayErrorDetails' => true,
                'logError' => false,
                'logErrorDetails' => false,

                // Configuration de la base de données
                'db' => [
                    'host' => getenv('DB_HOST') ?: '127.0.0.1',
                    'port' => (int) (getenv('DB_PORT') ?: 3308),
                    'database' => getenv('DB_NAME') ?: 'music',
                    'username' => getenv('DB_USER') ?: 'root',
                    'password' => getenv('DB_PASSWORD') ?: '',
                    'charset' => 'utf8mb4',
                    'flags' => [
                        \PDO::ATTR_PERSISTENT => false,
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                        \PDO::ATTR_EMULATE_PREPARES => true,
                        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    ],
                ],

                // Configuration des logs
                'logger' => [
                    'name' => 'slim-app',
                    'path' => isset($_ENV['docker'])
                        ? 'php://stdout'
                        : __DIR__ . '/../logs/app.log',
                    'level' => Logger::DEBUG,
                ],
            ]);
        },
    ]);
};
