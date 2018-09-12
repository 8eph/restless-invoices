<?php

namespace App\Doctrine;

use App\Entity\Invoice;
use App\Entity\Item;
use App\Service\ExchangeRateService;
use Doctrine\ORM\Event\OnFlushEventArgs;

/**
 * On status change to Published:
 *  - computes EUR values o
 *  - sets Invoice->publishedAt to current DateTimeTz
 */
class StatusChangeEnforcementListener
{
    /** @var ExchangeRateService */
    private $exchangeRateService;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (!$entity instanceof Invoice) continue;

            $changeSet = $uow->getEntityChangeSet($entity);

            // if we are creating an Invoice with a Published/real status right off the bat
            if (Invoice::STATUS_REAL === $changeSet['status'][1]) {
                $itemClassMetadata = $em->getClassMetadata(Item::class);
                $invoiceClassMetadata = $em->getClassMetadata(Invoice::class);
                /** @var Invoice $entity */
                /** @var Item $item */
                // first, compute the EUR currency values for each item
                foreach ($entity->getItems() as $item) {
                    $item->setPriceEur($this->exchangeRateService->toEuro($item->getPrice(), $entity->getCurrency()));
                    $uow->recomputeSingleEntityChangeSet($itemClassMetadata, $item);
                }

                // next, set the Published date
                $entity->setPublishedAt(new \DateTime());
                $uow->recomputeSingleEntityChangeSet($invoiceClassMetadata, $entity);
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof Invoice) continue;

            $changeSet = $uow->getEntityChangeSet($entity);
            // if we are moving from Draft status to Published. we do need to check whether status was changed at all
            if (array_key_exists('status', $changeSet) && Invoice::STATUS_REAL === $changeSet['status'][1]) {
                $itemClassMetadata = $em->getClassMetadata(Item::class);
                $invoiceClassMetadata = $em->getClassMetadata(Invoice::class);

                // first, compute the EUR currency values for each item
                foreach ($entity->getItems() as $item) {
                    $item->setPriceEur($this->exchangeRateService->toEuro($item->getPrice(), $entity->getCurrency()));
                    $uow->recomputeSingleEntityChangeSet($itemClassMetadata, $item);
                }

                // next, set the Published date
                $entity->setPublishedAt(new \DateTime());
                $uow->recomputeSingleEntityChangeSet($invoiceClassMetadata, $entity);
            }
        }
    }
}
