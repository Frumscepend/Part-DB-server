<?php
/**
 * This file is part of Part-DB (https://github.com/Part-DB/Part-DB-symfony).
 *
 * Copyright (C) 2019 - 2020 Jan Böhmer (https://github.com/jbtronics)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace App\Tests\Controller\AdminPages;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use App\Entity\Parts\StorageLocation;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

#[Group('slow')]
#[Group('DB')]
final class StorelocationController extends AbstractAdminController
{
    protected static string $base_path = '/en/store_location';
    protected static string $entity_class = StorageLocation::class;

    public function testUserBarcodeScannerControlsAreRendered(): void
    {
        $client = $this->createAdminClient();
        $client->request('GET', self::$base_path.'/1/edit');

        self::assertSelectorExists('[data-controller~="elements--barcode-input-scanner"]');
        self::assertSelectorExists(
            '.input-group > input[data-elements--barcode-input-scanner-target="input"]'
        );
        self::assertSelectorExists(
            '.input-group > input + button[data-action~="elements--barcode-input-scanner#open"]'
        );
        self::assertSelectorExists('#storelocation_admin_form_user_barcode_scanner_reader');
    }

    public function testUserBarcodeCanBeAssignedAndCleared(): void
    {
        $client = $this->createAdminClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $crawler = $client->request('GET', self::$base_path.'/1/edit');
        $form = $crawler->filter('form[name="storelocation_admin_form"]')->form();
        $form['storelocation_admin_form[user_barcode]'] = 'ui-storage-location-code';
        $client->submit($form);
        self::assertResponseIsSuccessful();

        $entityManager->clear();
        self::assertSame(
            'ui-storage-location-code',
            $entityManager->find(StorageLocation::class, 1)?->getUserBarcode()
        );

        $crawler = $client->request('GET', self::$base_path.'/1/edit');
        $form = $crawler->filter('form[name="storelocation_admin_form"]')->form();
        $form['storelocation_admin_form[user_barcode]'] = '';
        $client->submit($form);
        self::assertResponseIsSuccessful();

        $entityManager->clear();
        self::assertNull($entityManager->find(StorageLocation::class, 1)?->getUserBarcode());
    }

    public function testDuplicateUserBarcodeShowsFormValidationError(): void
    {
        $client = $this->createAdminClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $firstLocation = $entityManager->find(StorageLocation::class, 1);
        self::assertInstanceOf(StorageLocation::class, $firstLocation);
        $firstLocation->setUserBarcode('duplicate-ui-location-code');
        $entityManager->flush();

        $crawler = $client->request('GET', self::$base_path.'/2/edit');
        $form = $crawler->filter('form[name="storelocation_admin_form"]')->form();
        $form['storelocation_admin_form[user_barcode]'] = 'duplicate-ui-location-code';
        $client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains(
            '.invalid-feedback',
            'This barcode is already assigned to another storage location.'
        );
    }

    private function createAdminClient(): KernelBrowser
    {
        $client = self::createClient([], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'test',
        ]);
        $client->disableReboot();
        $client->catchExceptions(false);

        return $client;
    }
}
