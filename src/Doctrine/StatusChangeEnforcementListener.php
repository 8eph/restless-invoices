<?php

namespace App\Doctrine;

use App\Entity\Invoice;
use Doctrine\ORM\Event\OnFlushEventArgs;

class StatusChangeEnforcementListener
{
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (!$entity instanceof Invoice) continue;

            $changeSet = $uow->getEntityChangeSet($entity);

            // if we are creating an Invoice with a Published/real status right off the bat
            if (Invoice::STATUS_REAL === $changeSet['status'][1]) {

            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof Invoice) continue;

            $changeSet = $uow->getEntityChangeSet($entity);
            // if we are moving from Draft status to Published. we do need to check whether status was changed at all
            if (array_key_exists('status', $changeSet) && Invoice::STATUS_REAL === $changeSet['status'][1]) {

            }
        }
    }
}
