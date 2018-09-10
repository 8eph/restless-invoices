<?php

namespace App\Doctrine;

use App\Entity\Invoice;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

class InvoiceNameGenerationSubscriber implements EventSubscriber
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
            Events::postPersist
        ];
    }

    public function postPersist(LifecycleEventArgs $lifecycleEventArgs)
    {
        $entity = $lifecycleEventArgs->getEntity();

        if ($entity instanceof Invoice) {

            $entity->setName(sprintf('INV-%s-%s',
                date('y'),
                str_pad((int) $lifecycleEventArgs->getEntityManager()->getConnection()->lastInsertId(), self::PAD_LENGTH, 0, STR_PAD_LEFT)
            ));

            try {
                $lifecycleEventArgs->getEntityManager()->flush($entity);
            } catch (OptimisticLockException $e) {
                // do nothing
            } catch (ORMException $e) {
                // do nothing
            }
        }
    }
}