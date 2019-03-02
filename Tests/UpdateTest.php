<?php

declare(strict_types=1);

namespace Okvpn\Bundle\BetterOroBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

class UpdateTest extends TestCase
{
    /**
     * @dataProvider checkPreUpdateProvider
     *
     * @param string $package
     * @param string $file
     * @param string $hash
     */
    public function testSha1Hash(string $package, string $file, string $hash)
    {
        if (!defined('PHPUNIT_COMPOSER_INSTALL')) {
            self::markTestSkipped(
                'You need to set up the project dependencies using Composer:' . PHP_EOL . PHP_EOL .
                '    composer install' . PHP_EOL . PHP_EOL .
                'You can learn all about Composer on https://getcomposer.org/.' . PHP_EOL
            );
        }
        $file = realpath(dirname(PHPUNIT_COMPOSER_INSTALL) . '/' . $package . '/' . $file);

        if (!file_exists($file)) {
            self::markTestSkipped('File not found ' . $file);
        }

        self::assertSame($hash, sha1(file_get_contents($file)), "The file '$file' has been changed since last bugfix.");
    }

    public function checkPreUpdateProvider(): \Generator
    {
        $content = file_get_contents(__DIR__ . '/update.yml');
        $filesByPackages = Yaml::parse($content);

        foreach ($filesByPackages as $package => $files) {
            foreach ($files as $fileName => $hash) {
                yield "Packages: $package $hash" => [$package, $fileName, $hash];
            }
        }
    }
}
