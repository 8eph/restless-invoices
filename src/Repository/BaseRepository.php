<?php

namespace App\Repository;

use App\Entity\EntityInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Bridge\Doctrine\PropertyInfo\DoctrineExtractor;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class BaseRepository extends ServiceEntityRepository
{
    const ENTITY_CLASS = null;
    const FLUSH_ALL = 0;
    const FLUSH_ONE = 1;
    const FLUSH_NONE = 2;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, $this::ENTITY_CLASS);
    }

    public function save($entity, $flushMode = self::FLUSH_ONE)
    {
        try {
            $this->getEntityManager()->persist($entity);
            switch ($flushMode) {
                case self::FLUSH_ALL: $this->getEntityManager()->flush(); break;
                case self::FLUSH_ONE: $this->getEntityManager()->flush($entity); break;
                default: break;
            }
        } catch (OptimisticLockException $e) {
            // do nothing
        } catch (ORMException $e) {
            // do nothing
        }
    }

    public function flush($entity = null)
    {
        try {
            $this->getEntityManager()->flush($entity);
        } catch (OptimisticLockException $e) {
            // do nothing
        } catch (ORMException $e) {
            // do nothing
        }
    }

    public function mergeFromData(EntityInterface $entity, $data)
    {
        $propertyAccessor = new PropertyAccessor();
        $doctrineExtractor = new DoctrineExtractor($this->getEntityManager()->getMetadataFactory());

        foreach ($doctrineExtractor->getProperties(get_class($entity)) as $property) {
            if ($propertyAccessor->isWritable($entity, $property) && $propertyAccessor->isReadable($data, $property)) {

                $newValue = $propertyAccessor->getValue($data, $property);

                $propertyAccessor->setValue($entity, $property, $newValue);
            }
        }

        return $entity;
    }

    public function findOr404($id)
    {
        $object = $this->find($id);

        if (!$object) {
            throw new NotFoundHttpException(sprintf('Object of class %s and ID %s could not be found.', $this::ENTITY_CLASS, $id));
        }

        return $object;
    }

    public function merge($entity)
    {
        return $this->_em->merge($entity);
    }

    public function refresh($entity)
    {
        try {
            $this->getEntityManager()->refresh($entity);
        } catch (ORMException $e) {
            // do nothing
        }
    }

    public function persist($entity)
    {
        $this->getEntityManager()->persist($entity);
    }
}