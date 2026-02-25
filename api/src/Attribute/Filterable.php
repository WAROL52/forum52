<?php

namespace App\Attribute;

use Attribute;

/**
 * Marks an entity class as filterable
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Filterable
{
}
