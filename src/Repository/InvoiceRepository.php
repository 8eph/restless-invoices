<?php

namespace App\Repository;

use App\Entity\Invoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Bridge\Doctrine\PropertyInfo\DoctrineExtractor;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

class InvoiceRepository extends ServiceEntityRepository
{
    /** @var PropertyAccessor */
    private $propertyAccessor;
    /** @var DoctrineExtractor */
    private $doctrineExtractor;

    public function __construct(ManagerRegistry $registry)
    {
        $this->propertyAccessor = new PropertyAccessor();
        $this->doctrineExtractor = new DoctrineExtractor($registry->getManager()->getMetadataFactory());

        parent::__construct($registry, Invoice::class);
    }

    public function save(Invoice $invoice)
    {
        try {
            $this->getEntityManager()->persist($invoice);
            $this->getEntityManager()->flush($invoice);
        } catch (OptimisticLockException $e) {
            // do nothing
        } catch (ORMException $e) {
            // do nothing
        }
    }

    public function merge(Invoice $invoice, $data)
    {
        foreach ($this->doctrineExtractor->getProperties(Invoice::class) as $property) {
            if ($this->propertyAccessor->isWritable($invoice, $property) && $this->propertyAccessor->isReadable($data, $property)) {

                $newValue = $this->propertyAccessor->getValue($data, $property);

                $this->propertyAccessor->setValue($invoice, $property, $newValue);
            }
        }

        return $invoice;
    }
}