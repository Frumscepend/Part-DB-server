<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Parts\StorageLocation;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[Group('DB')]
final class StorelocationRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testUserBarcodeSchemaIsNullableAndUnique(): void
    {
        $table = $this->entityManager->getConnection()->createSchemaManager()->introspectTable('storelocations');
        $column = $table->getColumn('user_barcode');

        self::assertFalse($column->getNotnull());
        self::assertSame(255, $column->getLength());
        self::assertTrue($table->getIndex('storelocations_unique_user_barcode')->isUnique());
    }

    public function testMultipleStorageLocationsWithoutUserBarcodeRemainValid(): void
    {
        $locations = $this->entityManager->getRepository(StorageLocation::class)
            ->findBy(['user_barcode' => null]);

        self::assertGreaterThanOrEqual(2, count($locations));
    }
}
