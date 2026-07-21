<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Command\Validation;

use Ecommerce\Catalog\Aggregate\Product\Command\AddProductVariantPrice;
use Ecommerce\Catalog\Aggregate\Product\Command\Validation\ProductVariantPriceValidator;
use Ecommerce\EcommerceContext;
use JardisSupport\Validation\ObjectValidator;
use JardisSupport\Validation\ValidatorRegistry;
use JsonException;
use Throwable;
use Ecommerce\Catalog\Aggregate\Product\Command\AddProductVariantPrice as CommandAddProductVariantPrice;

/**
 * Validates the CommandAddProductVariantPrice command DTO graph.
 *
 * Sets up ObjectValidator with ValidatorRegistry for recursive validation
 * of the complete DTO tree (root + nested ONE + nested MANY).
 */
class ValidateAddProductVariantPrice extends EcommerceContext
{
    /**
     * Validates the command DTO.
     *
     * @param CommandAddProductVariantPrice $dto Command DTO
     * @throws ValidationException If validation fails
     * @throws JsonException
     * @throws Throwable
     */
    public function __invoke(CommandAddProductVariantPrice $dto): void
    {
        $registry = new ValidatorRegistry();
        $registry->register(AddProductVariantPrice::class, ($this->handle(ProductVariantPriceValidator::class))());

        $validator = new ObjectValidator($registry);
        $result = $validator->validate($dto);
        $errors = $this->filterEmptyErrors($result->getErrors());

        if (!empty($errors)) {
            throw new ValidationException(json_encode($errors, JSON_THROW_ON_ERROR));
        }
    }

    /**
     * Recursively filters empty error arrays from validation results.
     *
     * @param array<string, mixed> $errors
     * @return array<string, mixed>
     */
    private function filterEmptyErrors(array $errors): array
    {
        $filtered = [];

        foreach ($errors as $key => $value) {
            if (is_array($value)) {
                $nested = $this->filterEmptyErrors($value);
                if (!empty($nested)) {
                    $filtered[$key] = $nested;
                }
            } else {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }
}
