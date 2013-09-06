<?php

namespace Undf\FormBundle\Listener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\ValidatorInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * @author Dani Gonzalez <daniel.gonzalex@undefined.com>
 */
class ImageUploadListener implements EventSubscriberInterface
{

    /**
     * @var Symfony\Component\Validator\ValidatorInterface
     */
    private $validator;

    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Symfony\Component\PropertyAccess\PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * Form data before binding the submitted data
     * @var mixed
     */
    private $oldData;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'saveOldData',
            FormEvents::POST_SUBMIT => 'validateForm'
        );
    }

    /**
     *
     * @param \Symfony\Component\Validator\ValidatorInterface $validator
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Symfony\Component\PropertyAccess\PropertyAccessor $propertyAccessor
     */
    public function __construct(ValidatorInterface $validator, EntityManager $em, PropertyAccessor $propertyAccessor)
    {
        $this->validator = $validator;
        $this->em = $em;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * Save old form data before setting submitting data
     * @param \Symfony\Component\Form\FormEvent $event
     */
    public function saveOldData(FormEvent $event)
    {
        if ($event->getData()) {
            $this->oldData = clone $event->getData();
        }
    }

    /**
     * Validates the form and its domain object.
     *
     * @param FormEvent $event The event object
     */
    public function validateForm(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        // Validate the form in group "Default"
        $violations = $this->validator->validate($form);

        // If the form has no entity or the entity is new
        if(!$this->oldData || !$this->oldData->getId()){
            return;
        }

        if ($data->getImageName() !== $this->oldData->getImageName()) {
            if (count($violations) == 0) {

                foreach ($form as $child) {
                    /* @var $child \Symfony\Component\Form\Form */
                    $propertyPath = $child->getPropertyPath();
                    $this->propertyAccessor->setValue($this->oldData, $propertyPath, $this->propertyAccessor->getValue($data, $propertyPath));
                }

                $this->em->merge($this->oldData);
                $this->em->flush();
            } else {
                foreach ($form as $child) {
                    /* @var $child \Symfony\Component\Form\Form */
                    $propertyPath = $child->getPropertyPath();
                    $this->propertyAccessor->setValue($data, $propertyPath, $this->propertyAccessor->getValue($this->oldData, $propertyPath));
                }
            }
        }

    }

}
