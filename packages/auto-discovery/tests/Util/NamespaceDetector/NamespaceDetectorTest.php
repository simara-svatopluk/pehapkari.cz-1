<?php declare(strict_types=1);

namespace OpenProject\AutoDiscovery\Tests\Util;

use OpenProject\AutoDiscovery\Util\NamespaceDetector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\SplFileInfo;

final class NamespaceDetectorTest extends TestCase
{
    public function test(): void
    {
        $namespaceDetector = new NamespaceDetector();
        $directoryFileInfo = new SplFileInfo(__DIR__ . '/Source', '', '');

        $this->assertSame(
            'OpenProject\AutoDiscovery\Tests\Util\Source',
            $namespaceDetector->detectFromDirectory($directoryFileInfo)
        );
    }
}