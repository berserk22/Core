<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Core\Factory;

use DI\Container;
use DI\ContainerBuilder;
use Exception;

class ContainerFactory {

    /**
     * Директория для скомпилированного контейнера
     */
    private const string COMPILE_DIR = ROOT_DIR . 'cache/container/';

    /**
     * Имя скомпилированного класса контейнера
     */
    private const string COMPILED_CLASS = 'CompiledContainer';

    /**
     * @param array  $settings
     * @param string $environment development|production
     * @return Container
     * @throws Exception
     */
    public static function createInstance(array $settings = [], string $environment = 'development'): Container {
        $builder = new ContainerBuilder();
        $builder->addDefinitions($settings);

        if ($environment === 'production') {
            self::optimizeForProduction($builder);
        }

        return $builder->build();
    }

    /**
     * Включаем оптимизации только для production:
     *
     * 1. enableCompilation()     — компилирует DI-дефиниции в PHP-класс
     *                              при первом запуске. Последующие запросы
     *                              используют скомпилированный класс напрямую
     *                              без парсинга дефиниций.
     *
     * 2. writeProxiesToFile()    — записывает lazy-прокси на диск вместо
     *                              генерации в памяти при каждом запросе.
     *
     * 3. useAutowiring(true)     — в production оставляем, но в dev можно
     *                              отключить для явного контроля зависимостей.
     *
     * @param ContainerBuilder $builder
     * @return void
     * @throws Exception
     */
    private static function optimizeForProduction(ContainerBuilder $builder): void {
        // Создаём директории если не существуют
        self::ensureDirectoryExists(self::COMPILE_DIR);
        self::ensureDirectoryExists(self::COMPILE_DIR . 'proxies/');

        // 1. Компиляция контейнера в PHP-класс
        // ВАЖНО: при деплое новой версии нужно очищать cache/container/
        $builder->enableCompilation(
            self::COMPILE_DIR,
            self::COMPILED_CLASS
        );

        // 2. Lazy-прокси записываются на диск
        $builder->writeProxiesToFile(
            true,
            self::COMPILE_DIR . 'proxies/'
        );
    }

    /**
     * @param string $dir
     * @return void
     * @throws Exception
     */
    private static function ensureDirectoryExists(string $dir): void {
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new Exception(
                sprintf('Failed to create container cache directory: "%s"', $dir)
            );
        }
    }

}
