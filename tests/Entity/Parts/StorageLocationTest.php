<?php

declare(strict_types=1);

namespace App\Tests\Entity\Parts;

use App\Entity\Parts\StorageLocation;
use PHPUnit\Framework\TestCase;

final class StorageLocationTest extends TestCase
{
    public function testUserBarcodeIsStoredAsOpaqueString(): void
    {
        $location = new StorageLocation();

        self::assertNull($location->getUserBarcode());
        self::assertSame($location, $location->setUserBarcode('  opaque-Code  '));
        self::assertSame('  opaque-Code  ', $location->getUserBarcode());
    }

    public function testEmptyUserBarcodeIsCanonicalizedToNull(): void
    {
        $location = (new StorageLocation())->setUserBarcode('assigned');

        $location->setUserBarcode('');

        self::assertNull($location->getUserBarcode());
    }
}
