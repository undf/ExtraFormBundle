<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Undf\FormBundle\Form\DataTransformer;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * @author Guillermo Ferrer
 */
class CollectionToJsonTransformer implements DataTransformerInterface
{
    private $serializer;
    private $modelManager;
    private $className;

    public function __construct(SerializerInterface $serializer, EntityManager $modelManager, $className)
    {
        $this->serializer = $serializer;
        $this->modelManager = $modelManager;
        $this->className = $className;
    }

    /**
     * Transforms a collection into an array.
     *
     * @param Collection $collection A collection of entities
     *
     * @return mixed An array of entities
     *
     * @throws TransformationFailedException
     */
    public function transform($collection)
    {
        if (null === $collection || empty($collection)) {
            return array();
        }

        // For cases when the collection getter returns $collection->toArray()
        // in order to prevent modifications of the returned collection
        if (is_array($collection)) {
            return $collection;
        }

        if (!$collection instanceof Collection) {
            throw new TransformationFailedException('Expected a Doctrine\Common\Collections\Collection object.');
        }

        return $this->serializer->serialize($collection, 'json');
    }

    /**
     * Transforms choice keys into entities.
     *
     * @param mixed $keys
     * @throws \Symfony\Component\Form\Exception\TransformationFailedException
     * @throws UnexpectedTypeException
     *
     * @return Collection   A collection of entities
     */
    public function reverseTransform($keys)
    {
        if (!is_array($keys) || empty($keys)) {
            return array();
        }
        
        // optimize this into a SELECT WHERE IN query
        $repo = $this->modelManager->getRepository($this->className);
        $qb = $repo->createQueryBuilder('a');
        $qb->add('where', $qb->expr()->in('a.id', $keys));
        $collection = $qb->getQuery()->execute();

        return $collection;
    }
}
