<?php

namespace App\Repository;

use App\Entity\Invoice;
use Symfony\Component\HttpFoundation\Request;

class InvoiceRepository extends BaseRepository
{
    const ENTITY_CLASS = Invoice::class;

    /**
     * Example GET parameters:
     *
     * ?status=draft|real&currency=JPY&name=INV
     *
     * @param Request $request
     *
     * @return Invoice[]
     */
    public function searchBy(Request $request)
    {
        $qb = $this->createQueryBuilder('i');
        $expr = $qb->expr();

        if ($status = $request->query->get('status', null)) {
            if ('draft' === $status) {
                $qb->andWhere($expr->eq('i.status', Invoice::STATUS_DRAFT));
            } else if (in_array($status, ['real', 'published'])) {
                $qb->andWhere($expr->eq('i.status', Invoice::STATUS_REAL));
            }
        }

        if ($currency = $request->query->get('currency', null)) {
            $qb->andWhere($expr->eq('i.currency', ':currency'));
            $qb->setParameter('currency', $currency);
        }

        if ($name = $request->query->get('name', null)) {
            $qb->andWhere("i.name LIKE '%{$name}%'");
        }

        return $qb->getQuery()->getResult();
    }
}