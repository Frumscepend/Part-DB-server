<?php

declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Text input with a reusable camera barcode scanner control.
 */
final class BarcodeScannerType extends AbstractType
{
    public function getParent(): string
    {
        return TextType::class;
    }
}
