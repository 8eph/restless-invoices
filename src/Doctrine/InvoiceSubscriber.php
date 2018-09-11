<?php

namespace App\Doctrine;

use App\Entity\Invoice;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

class InvoiceSubscriber implements EventSubscriber
{
    const PAD_LENGTH = 6;

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return string[]
     */
    public function getSubscribedEvents()
    {
        return [
            Events::postPersist,
        ];
    }

    /**
     * @param LifecycleEventArgs $lifecycleEventArgs
     */
    public function postPersist(LifecycleEventArgs $lifecycleEventArgs)
    {
        $entity = $lifecycleEventArgs->getEntity();

        if ($entity instanceof Invoice) {
            $lastInsertId = $lifecycleEventArgs->getEntityManager()->getConnection()->lastInsertId();

            $entity->setName(sprintf('INV-%s-%s',
                date('y'),
                str_pad((int) $lastInsertId, self::PAD_LENGTH, 0, STR_PAD_LEFT)
            ));

            try {
                if ($entity)
                $lifecycleEventArgs->getEntityManager()->flush($entity);
            } catch (OptimisticLockException $e) {
                // do nothing
            } catch (ORMException $e) {
                // do nothing
            }
        }
    }
}