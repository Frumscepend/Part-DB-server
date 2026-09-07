<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\Parts\StorageLocation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Storage location selector with a camera barcode scanner control.
 */
final class StorageLocationScannerType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('class', StorageLocation::class);
        $resolver->setDefault(
            'new_entity_configurator',
            static function (StorageLocation $location, string $barcode): void {
                $location->setUserBarcode($barcode);
            }
        );
    }

    public function getParent(): string
    {
        return StructuralEntityType::class;
    }
}
