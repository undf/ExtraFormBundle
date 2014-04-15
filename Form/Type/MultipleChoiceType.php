<?php

namespace Undf\FormBundle\Form\Type;

use JMS\Serializer\Serializer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


class MultipleChoiceType extends EntityType
{

    private $serializer;

    public function __construct($doctrine, $propertyAccessor, Serializer $serializer)
    {
        parent::__construct($doctrine, $propertyAccessor);

        $this->serializer = $serializer;
    }

    public function getName()
    {
        return 'multiple_choice';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $choices = array();
        foreach ($view->vars['choices'] as $choiceView) {

            $choice = array();
            $choice['id'] = $choiceView->data->getId();
            $choice['name'] = $choiceView->data->getName();

            foreach ($form->getData()->toArray() as $selected) {
                if ($selected->getId() == $choiceView->data->getId()) {
                    $choice['selected'] = true;
                    break;
                }
            }

            $choices[] = $choice;
        }

        $view->vars['json_choices'] = $this->serializer->serialize($choices, 'json');
    }
}
