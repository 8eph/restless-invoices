<?php

namespace App\Controller;

use App\Entity\EntityInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\PropertyInfo\DoctrineExtractor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Exception\ValidatorException;

/**
 * Quick and dirty methods which should be located in event listeners in an ideal world.
 */
trait ControllerHelper
{
    /**
     * Magical autowiring annotation. Don't try this at home, kids!
     *
     * @required
     *
     * @param EntityManagerInterface $entityManager
     */
    public function setEntityManager(EntityManagerInterface $entityManager)
    {
        $this->_em = $entityManager;
    }

    /**
     * Validates an entity as well as any 1st level collections
     *
     * @param EntityInterface $object
     */
    public function validate(EntityInterface $object)
    {
        $errors = $this->validator->validate($object);

        $propertyAccessor = new PropertyAccessor();
        $doctrineExtractor = new DoctrineExtractor($this->_em->getMetadataFactory());

        foreach ($doctrineExtractor->getProperties(get_class($object)) as $property) {
            $value = $propertyAccessor->getValue($object, $property);

            if (is_array($value)) {
                foreach ($value as $item) {
                    $errors->addAll($this->validator->validate($item));
                }
            }
        }

        if (count($errors)) {
            $message = '';
            foreach ($errors as $error) {
                $propertyPath = $error->getPropertyPath();
                // normally you'd want a formatter for custom output like this but i'm running out of time with this task already
                $message .= "Field \"{$propertyPath}\": ".$error->getMessage().PHP_EOL."You input \"{$error->getInvalidValue()}\". ";
                if ($error->getConstraint() instanceof Choice) {
                    $message .= 'Available choices are: "'. implode(', ', $error->getConstraint()->choices).'".';
                }
            }

            throw new ValidatorException($message);
        }
    }

    public function getJsonResponse($entity, array $serializationGroups = ['index'])
    {
        return new Response($this->serializer->serialize($entity, 'json', ['groups' => $serializationGroups]), Response::HTTP_OK, [
            ['Content-Type' => 'application/json']
        ]);
    }
}