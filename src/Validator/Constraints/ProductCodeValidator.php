<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use App\Validator\Constraints\ProductCode;

class ProductCodeValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ProductCode) {
            throw new UnexpectedTypeException($constraint, ProductCode::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        // Format attendu: PRD-XXXX où X est un chiffre
        if (!preg_match('/^PRD-\\d{4}$/', $value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}