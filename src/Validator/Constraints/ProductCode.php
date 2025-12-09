<?php

namespace App\Validator\Constraints;
use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ProductCode extends Constraint
{
    public string $message = "Le code produit \"{{ value }}\" n'est pas valide. Format attendu: PRD-XXXX";
}