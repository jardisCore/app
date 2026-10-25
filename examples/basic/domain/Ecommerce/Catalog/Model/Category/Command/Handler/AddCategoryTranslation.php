<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Category\Command\Handler;

use DateTimeImmutable;
use Ecommerce\Catalog\Model\Category\Aggregate\Category;
use Ecommerce\Catalog\Model\Category\Command\Handler\Action\BuildAddCategoryTranslationData;
use Ecommerce\Catalog\Model\Category\Command\Validation\AddCategoryTranslationValidatorRegistry;
use Ecommerce\Catalog\Model\Category\Event\CategoryCategoryTranslationAdded;
use Ecommerce\Catalog\Model\Category\Repository\CategoryRepository;
use Ecommerce\EcommerceContext;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Repository\Handler\Transaction\ResolveConnection;
use JardisSupport\Response\Handler\Door\ValidateInput;
use JardisSupport\Response\Handler\Failure\LogCause;
use JardisSupport\Response\Handler\Failure\MapThrowable;
use JardisSupport\Response\Handler\Failure\NotFoundPayload;
use JardisSupport\Response\Handler\Failure\ValidationPayload;
use JardisSupport\Response\Handler\Response\BuildResponseData;
use JardisSupport\Validation\ObjectValidator;
use Throwable;
use Ecommerce\Catalog\Model\Category\Command\AddCategoryTranslation as CommandAddCategoryTranslation;
use Ecommerce\Catalog\Model\Category\Query\CategoryByIdentifier as QueryCategory;

/**
 * Command endpoint: AddCategoryTranslation
 *
 * Operation: add CategoryTranslation (many)
 */
class AddCategoryTranslation extends EcommerceContext
{
    /**
     * Adds the CategoryTranslation entity.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        /** @var CommandAddCategoryTranslation $addCategoryTranslation */
        $addCategoryTranslation = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(AddCategoryTranslationValidatorRegistry::class))(),
            fieldPaths: true
        );
        $findings = ($this->handle(ValidateInput::class, validator: $validator))($addCategoryTranslation);
        if ($findings->fields !== []) {
            foreach ($findings->errors as $error) {
                $this->result()->addError($error);
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))(
                    ResponseStatus::ValidationError,
                    ($this->handle(ValidationPayload::class))('validation', $findings->fields),
                    null,
                    ''
                )
            );

            return ($this->transformer())($this->result(), ResponseStatus::ValidationError);
        }
        $connection = ($this->handle(ResolveConnection::class))($this->resource()->dbConnection());
        $owner = $connection?->inTransaction() !== true;

        try {
            $query = $this->handle(
                QueryCategory::class,
                tenant: $addCategoryTranslation->tenant,
                identifier: $addCategoryTranslation->categoryIdentifier
            );
            /** @var Category|null $handler */
            $handler = $this->handle(CategoryRepository::class)->getCategoryByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['categoryIdentifier' => $addCategoryTranslation->categoryIdentifier]
                        ),
                        null,
                        ''
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::NotFound
                );
            }

            $entityData = $this->handle(BuildAddCategoryTranslationData::class)($addCategoryTranslation);

            $handler->addCategoryTranslation($entityData);

            $persistResult = $this->handle(CategoryRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $categoryTranslation = $handler->getData()->getCategoryTranslation();
            $this->result()->setData([
                '@type' => 'category',
                'id' => $handler->getData()->getId(),
                'categoryIdentifier' => $addCategoryTranslation->categoryIdentifier,
                'categoryTranslationId' => $categoryTranslation[array_key_last($categoryTranslation)]->getId(),
            ]);

            $categoryTranslationForEvent = $handler->getData()->getCategoryTranslation();
            $addedEntity = !empty($categoryTranslationForEvent)
                ? $categoryTranslationForEvent[array_key_last($categoryTranslationForEvent)]
                : null;

            $event = $this->handle(
                CategoryCategoryTranslationAdded::class,
                categoryIdentifier: $handler->getData()->getIdentifier(),
                categoryTranslationId: $addedEntity?->getId(),
                locale: $addedEntity?->getLocale(),
                title: $addedEntity?->getTitle(),
                description: $addedEntity?->getDescription(),
                metaTitle: $addedEntity?->getMetaTitle(),
                metaDescription: $addedEntity?->getMetaDescription(),
                occurredAt: new DateTimeImmutable()
            );
            $this->result()->addEvent($event, EventScope::Internal);

            return ($this->transformer())($this->result());
        } catch (Throwable $e) {
            $failure = ($this->handle(MapThrowable::class))($e, $owner, 'Category');
            if ($owner && $failure->status->value >= ResponseStatus::InternalError->value) {
                ($this->handle(LogCause::class, logger: $this->resource()->logger()))($e, 'AddCategoryTranslation');
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))($failure->status, $failure->response, null, '')
            );

            return ($this->transformer())($this->result(), $failure->status);
        }
    }
}
