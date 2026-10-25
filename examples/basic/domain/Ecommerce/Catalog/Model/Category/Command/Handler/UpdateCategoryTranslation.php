<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Category\Command\Handler;

use DateTimeImmutable;
use Ecommerce\Catalog\Model\Category\Aggregate\Category;
use Ecommerce\Catalog\Model\Category\Command\Handler\Action\BuildUpdateCategoryTranslationData;
use Ecommerce\Catalog\Model\Category\Command\Validation\UpdateCategoryTranslationValidatorRegistry;
use Ecommerce\Catalog\Model\Category\Event\CategoryCategoryTranslationUpdated;
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
use Ecommerce\Catalog\Model\Category\Command\UpdateCategoryTranslation as CommandUpdateCategoryTranslation;
use Ecommerce\Catalog\Model\Category\Query\CategoryByIdentifier as QueryCategory;

/**
 * Command endpoint: UpdateCategoryTranslation
 *
 * Operation: update CategoryTranslation (many)
 */
class UpdateCategoryTranslation extends EcommerceContext
{
    /**
     * Updates a CategoryTranslation in the collection.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        /** @var CommandUpdateCategoryTranslation $updateCategoryTranslation */
        $updateCategoryTranslation = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(UpdateCategoryTranslationValidatorRegistry::class))(),
            fieldPaths: true
        );
        $findings = ($this->handle(ValidateInput::class, validator: $validator))($updateCategoryTranslation);
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
                tenant: $updateCategoryTranslation->tenant,
                identifier: $updateCategoryTranslation->categoryIdentifier
            );
            /** @var Category|null $handler */
            $handler = $this->handle(CategoryRepository::class)->getCategoryByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['categoryIdentifier' => $updateCategoryTranslation->categoryIdentifier]
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

            $entityData = $this->handle(BuildUpdateCategoryTranslationData::class)($updateCategoryTranslation);

            $items = $handler->getData()->getCategoryTranslation();
            $found = false;
            foreach ($items as $item) {
                if ($item->getId() === $updateCategoryTranslation->categoryTranslationId) {
                    $handler->updateCategoryTranslation($item->getId(), $entityData);
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['categoryTranslationId' => $updateCategoryTranslation->categoryTranslationId]
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

            $persistResult = $this->handle(CategoryRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                '@type' => 'category',
                'id' => $handler->getData()->getId(),
                'categoryIdentifier' => $updateCategoryTranslation->categoryIdentifier,
                'categoryTranslationId' => $updateCategoryTranslation->categoryTranslationId,
            ]);

            $event = $this->handle(
                CategoryCategoryTranslationUpdated::class,
                categoryIdentifier: $handler->getData()->getIdentifier(),
                categoryTranslationId: $updateCategoryTranslation->categoryTranslationId,
                locale: $updateCategoryTranslation->locale,
                title: $updateCategoryTranslation->title,
                description: $updateCategoryTranslation->description,
                metaTitle: $updateCategoryTranslation->metaTitle,
                metaDescription: $updateCategoryTranslation->metaDescription,
                occurredAt: new DateTimeImmutable()
            );
            $this->result()->addEvent($event, EventScope::Internal);

            return ($this->transformer())($this->result());
        } catch (Throwable $e) {
            $failure = ($this->handle(MapThrowable::class))($e, $owner, 'Category');
            if ($owner && $failure->status->value >= ResponseStatus::InternalError->value) {
                ($this->handle(LogCause::class, logger: $this->resource()->logger()))($e, 'UpdateCategoryTranslation');
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))($failure->status, $failure->response, null, '')
            );

            return ($this->transformer())($this->result(), $failure->status);
        }
    }
}
