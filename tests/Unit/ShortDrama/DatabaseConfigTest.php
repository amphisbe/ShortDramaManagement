<?php

declare(strict_types=1);

namespace HyperfTests\Unit\ShortDrama;

use PHPUnit\Framework\TestCase;

class DatabaseConfigTest extends TestCase
{
    public function testDramaConnectionUsesMysqlAndDramaDatabaseByDefault(): void
    {
        $config = require BASE_PATH . '/config/autoload/databases.php';

        self::assertArrayHasKey('drama', $config);
        self::assertSame('mysql', $config['drama']['driver']);
        self::assertSame('drama', $config['drama']['database']);
    }

    public function testDramaConnectionReusesDefaultPoolConfiguration(): void
    {
        $config = require BASE_PATH . '/config/autoload/databases.php';

        self::assertArrayHasKey('drama', $config);
        self::assertSame($config['default']['pool'], $config['drama']['pool']);
    }

    public function testPluginManifestDefinesShortDramaPlugin(): void
    {
        $manifestPath = BASE_PATH . '/plugin/shortdrama/mine.json';

        self::assertFileExists($manifestPath);
        $manifest = json_decode((string) file_get_contents($manifestPath), false, 512, JSON_THROW_ON_ERROR);

        self::assertSame('shortdrama/admin', $manifest->name);
        self::assertSame('1.0.0', $manifest->version);
        self::assertSame('mix', $manifest->type);
        self::assertNotSame('', $manifest->description);
        self::assertIsArray($manifest->author);
        self::assertNotSame('', $manifest->author[0]->name);
        self::assertSame('src', $manifest->composer->{'psr-4'}->{'Plugin\\ShortDrama\\'});
        self::assertSame('Plugin\\ShortDrama\\InstallScript', $manifest->composer->installScript);
        self::assertSame('Plugin\\ShortDrama\\UninstallScript', $manifest->composer->uninstallScript);
        self::assertSame('Plugin\\ShortDrama\\ConfigProvider', $manifest->composer->config);
        self::assertInstanceOf(\stdClass::class, $manifest->package->dependencies);
        self::assertSame([], get_object_vars($manifest->package->dependencies));
    }

    public function testPluginConfigProviderScansItsSourceDirectory(): void
    {
        $providerPath = BASE_PATH . '/plugin/shortdrama/src/ConfigProvider.php';

        self::assertFileExists($providerPath);
        require_once $providerPath;

        $config = (new \Plugin\ShortDrama\ConfigProvider())();

        self::assertContains(dirname($providerPath), $config['annotations']['scan']['paths']);
    }

    public function testInstallAndUninstallScriptsAreAvailable(): void
    {
        $scriptPaths = [
            BASE_PATH . '/plugin/shortdrama/src/InstallScript.php',
            BASE_PATH . '/plugin/shortdrama/src/UninstallScript.php',
        ];

        foreach ($scriptPaths as $scriptPath) {
            self::assertFileExists($scriptPath);
            require_once $scriptPath;
        }

        self::assertIsCallable(new \Plugin\ShortDrama\InstallScript());

        ob_start();
        $uninstallResult = (new \Plugin\ShortDrama\UninstallScript())();
        $output = ob_get_clean();

        self::assertNull($uninstallResult);
        self::assertSame('', $output);
    }
}
