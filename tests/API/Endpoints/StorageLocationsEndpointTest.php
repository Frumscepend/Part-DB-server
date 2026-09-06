<?php
/*
 * This file is part of Part-DB (https://github.com/Part-DB/Part-DB-symfony).
 *
 *  Copyright (C) 2019 - 2024 Jan Böhmer (https://github.com/jbtronics)
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

declare(strict_types=1);


namespace API\Endpoints;

use App\Tests\API\Endpoints\CrudEndpointTestCase;

final class StorageLocationsEndpointTest extends CrudEndpointTestCase
{

    protected function getBasePath(): string
    {
        return '/api/storage_locations';
    }

    public function testGetCollection(): void
    {
        $this->_testGetCollection();
        self::assertJsonContains([
            'hydra:totalItems' => 7,
        ]);
    }

    public function testGetChildrenCollection(): void
    {
        $this->_testGetChildrenCollection(1);
    }

    public function testGetItem(): void
    {
        $this->_testGetItem(1);
        $this->_testGetItem(2);
        $this->_testGetItem(3);
    }

    public function testCreateItem(): void
    {
        $response = $this->_testPostItem([
            'name' => 'Test API',
            'parent' => '/api/storage_locations/1',
            'user_barcode' => 'api-created-location-code',
        ]);

        self::assertSame('api-created-location-code', $response->toArray()['user_barcode']);
    }

    public function testUpdateItem(): void
    {
        $response = $this->_testPatchItem(5, [
            'name' => 'Updated',
            'parent' => '/api/storage_locations/2',
            'user_barcode' => 'api-updated-location-code',
        ]);

        self::assertSame('api-updated-location-code', $response->toArray()['user_barcode']);
    }

    public function testUserBarcodeCanBeClearedAndReassigned(): void
    {
        $this->_testPatchItem(1, ['user_barcode' => 'reassignable-location-code']);

        $duplicateResponse = self::createAuthenticatedClient()->request('PATCH', $this->getItemPath(2), [
            'json' => ['user_barcode' => 'reassignable-location-code'],
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
        ]);
        self::assertResponseStatusCodeSame(422);
        self::assertSame('user_barcode', $duplicateResponse->toArray(false)['violations'][0]['propertyPath']);

        $clearedResponse = $this->_testPatchItem(1, ['user_barcode' => '']);
        self::assertNull($clearedResponse->toArray()['user_barcode']);

        $reassignedResponse = $this->_testPatchItem(2, ['user_barcode' => 'reassignable-location-code']);
        self::assertSame('reassignable-location-code', $reassignedResponse->toArray()['user_barcode']);

        $this->_testPatchItem(2, ['user_barcode' => 'replacement-location-code']);
        $releasedResponse = $this->_testPatchItem(1, ['user_barcode' => 'reassignable-location-code']);
        self::assertSame('reassignable-location-code', $releasedResponse->toArray()['user_barcode']);
    }

    public function testUserBarcodeLengthIsValidated(): void
    {
        self::createAuthenticatedClient()->request('PATCH', $this->getItemPath(1), [
            'json' => ['user_barcode' => str_repeat('x', 256)],
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testDeleteItem(): void
    {
        $this->_testDeleteItem(7);
    }
}
