<?php

declare(strict_types=1);

/*
 * This file is part of Part-DB (https://github.com/Part-DB/Part-DB-symfony).
 *
 *  Copyright (C) 2019 - 2023 Jan Böhmer (https://github.com/jbtronics)
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published
 *  by the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
namespace App\Tests\Controller;

use App\Entity\Parts\StorageLocation;
use App\Form\Type\StorageLocationScannerType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\FormFactoryInterface;

final class ScanControllerTest extends WebTestCase
{

    private ?KernelBrowser $client = null;

    public function setUp(): void
    {
        $this->client = static::createClient([], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'test',
        ]);
        $this->client->disableReboot();
        $this->client->catchExceptions(false);
    }

    public function testRedirectOnInputParameter(): void
    {
        $this->client->request('GET', '/en/scan?input=0000001');
        $this->assertResponseRedirects('/en/part/1');
    }

    public function testScanQRCode(): void
    {
        $this->client->request('GET', '/scan/part/1');
        $this->assertResponseRedirects('/en/part/1');
    }

    public function testUserDefinedStorageLocationBarcodeRedirectsUntilCleared(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $location = $entityManager->find(StorageLocation::class, 4);
        self::assertInstanceOf(StorageLocation::class, $location);
        $location->setUserBarcode('scanner-storage-location-code');
        $entityManager->flush();

        $this->client->request('GET', '/en/scan?input=scanner-storage-location-code');
        self::assertResponseRedirects('/en/store_location/4/parts');

        $location->setUserBarcode(null);
        $entityManager->flush();

        $this->client->request('GET', '/en/scan?input=scanner-storage-location-code');
        self::assertResponseIsSuccessful();
    }

    public function testStorageLocationLookupResolvesUserAndInternalBarcodes(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $location = $entityManager->find(StorageLocation::class, 4);
        self::assertInstanceOf(StorageLocation::class, $location);
        $location->setUserBarcode('storage-location-picker-code');
        $entityManager->flush();

        $this->client->request('GET', '/en/scan/storage-location?barcode=storage-location-picker-code');
        self::assertResponseIsSuccessful();
        self::assertSame(['found' => true, 'id' => 4], json_decode(
            (string) $this->client->getResponse()->getContent(),
            true,
            flags: JSON_THROW_ON_ERROR
        ));

        $this->client->request('GET', '/en/scan/storage-location?barcode=S0004');
        self::assertResponseIsSuccessful();
        self::assertSame(4, json_decode(
            (string) $this->client->getResponse()->getContent(),
            true,
            flags: JSON_THROW_ON_ERROR
        )['id']);
    }

    public function testStorageLocationLookupRejectsUnknownBarcode(): void
    {
        $this->client->request('GET', '/en/scan/storage-location?barcode=missing-location-code');

        self::assertResponseStatusCodeSame(404);
    }

    public function testScannedNewStorageLocationKeepsBarcode(): void
    {
        $formFactory = self::getContainer()->get(FormFactoryInterface::class);
        $form = $formFactory->create(StorageLocationScannerType::class, null, ['allow_add' => true]);
        $payload = rawurlencode(json_encode([
            'path' => 'Scanned storage location',
            'barcode' => 'new-storage-location-code',
        ], JSON_THROW_ON_ERROR));

        $form->submit('$%SCAN$'.$payload);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid(), (string) $form->getErrors(true));
        $location = $form->getData();
        self::assertInstanceOf(StorageLocation::class, $location);
        self::assertSame('Scanned storage location', $location->getName());
        self::assertSame('new-storage-location-code', $location->getUserBarcode());

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->flush();
        $this->client->request('GET', '/en/scan/storage-location?barcode=new-storage-location-code');
        self::assertResponseIsSuccessful();
        self::assertSame($location->getID(), json_decode(
            (string) $this->client->getResponse()->getContent(),
            true,
            flags: JSON_THROW_ON_ERROR
        )['id']);
    }

    public function testManuallyEnteredNewStorageLocationDoesNotSetBarcode(): void
    {
        $formFactory = self::getContainer()->get(FormFactoryInterface::class);
        $form = $formFactory->create(StorageLocationScannerType::class, null, ['allow_add' => true]);

        $form->submit('$%$scan: Manually entered storage location');

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid(), (string) $form->getErrors(true));
        $location = $form->getData();
        self::assertInstanceOf(StorageLocation::class, $location);
        self::assertSame('scan: Manually entered storage location', $location->getName());
        self::assertNull($location->getUserBarcode());
    }

    public function testScannedNewStorageLocationCanBeCreatedBelowSelectedLocation(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $parent = $entityManager->find(StorageLocation::class, 1);
        self::assertInstanceOf(StorageLocation::class, $parent);

        $formFactory = self::getContainer()->get(FormFactoryInterface::class);
        $form = $formFactory->create(StorageLocationScannerType::class, null, ['allow_add' => true]);
        $payload = rawurlencode(json_encode([
            'path' => $parent->getFullPath('->').'->Scanned child location',
            'barcode' => 'new-child-location-code',
        ], JSON_THROW_ON_ERROR));

        $form->submit('$%SCAN$'.$payload);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid(), (string) $form->getErrors(true));
        $location = $form->getData();
        self::assertInstanceOf(StorageLocation::class, $location);
        self::assertSame('Scanned child location', $location->getName());
        self::assertSame($parent, $location->getParent());
        self::assertSame('new-child-location-code', $location->getUserBarcode());
    }
}
