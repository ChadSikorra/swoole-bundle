<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Server\Runtime\HMR;

use Assert\InvalidArgumentException;
use K911\Swoole\Server\Runtime\HMR\InotifyHMR;
use K911\Swoole\Server\Runtime\HMR\LoadedFilesInterface;
use PHPUnit\Framework\TestCase;

class InotifyHMRTest extends TestCase
{
    private const NON_EXISTING_FILE = __DIR__.'/not_exists.php';

    private const NON_RELOADABLE_EXISTING_FILES = [
        __DIR__.'/HMRSpy.php',
        __DIR__.'/InotifyHMRTest.php',
    ];

    protected function setUp(): void
    {
        if (!\extension_loaded('inotify')) {
            $this->markTestSkipped('Swoole Bundle HMR requires "inotify" PHP extension present and installed on the system.');
        }
    }

    public function testConstructSetGetNonReloadableFiles(): void
    {
        /* @var $loadedFiles LoadedFilesInterface */
        $loadedFiles = $this->prophesize(LoadedFilesInterface::class)->reveal();
        $hmr = new InotifyHMR($loadedFiles, self::NON_RELOADABLE_EXISTING_FILES);
        $this->assertSame(self::NON_RELOADABLE_EXISTING_FILES, $hmr->getNonReloadableFiles());
    }

    public function testConstructSetNotExistingNonReloadableFiles(): void
    {
        $this->assertFileNotExists(self::NON_EXISTING_FILE);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('File "%s" was expected to exist.', self::NON_EXISTING_FILE));

        /* @var $loadedFiles LoadedFilesInterface */
        $loadedFiles = $this->prophesize(LoadedFilesInterface::class)->reveal();
        new InotifyHMR($loadedFiles, [self::NON_EXISTING_FILE]);
    }

    public function testBootSetGetNonReloadableFiles(): void
    {
        /* @var $loadedFiles LoadedFilesInterface */
        $loadedFiles = $this->prophesize(LoadedFilesInterface::class)->reveal();
        $hmr = new InotifyHMR($loadedFiles);
        $hmr->boot(['nonReloadableFiles' => self::NON_RELOADABLE_EXISTING_FILES]);

        $expected = array_unique(
            array_merge(get_included_files(), self::NON_RELOADABLE_EXISTING_FILES)
        );
        sort($expected);
        $result = $hmr->getNonReloadableFiles();
        sort($result);

        $this->assertSame($result, $expected);
    }

    public function testBootSetNotExistingNonReloadableFiles(): void
    {
        $this->assertFileNotExists(self::NON_EXISTING_FILE);

        /* @var $loadedFiles LoadedFilesInterface */
        $loadedFiles = $this->prophesize(LoadedFilesInterface::class)->reveal();
        $hmr = new InotifyHMR($loadedFiles);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('File "%s" was expected to exist.', self::NON_EXISTING_FILE));

        $hmr->boot(['nonReloadableFiles' => [self::NON_EXISTING_FILE]]);
    }
}
