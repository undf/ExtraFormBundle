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
        foreach ($view->vars['choices'] as $group => $choiceView) {

            $choiceViews = is_array($choiceView) ? $choiceView : array($choiceView);

            foreach($choiceViews as $choiceView) {
                $choice = array();
                $choice['id'] = $choiceView->data->getId();
                $choice['name'] = $choiceView->data->getName();

                if ($this->isSelectedOption($form, $choiceView->data->getId())) {
                    $choice['selected'] = true;
                }

                if(isset($options['group_by'])) {
                    if(!isset($choices[$group])) {
                        $choices[$group] = array();
                    }
                    $choices[$group][] = $choice;
                } else {
                    $choices[] = $choice;
                }
            }
        }
        $view->vars['group_by'] = isset($options['group_by']) ? $options['group_by'] : false;
        $view->vars['json_choices'] = $this->serializer->serialize($choices, 'json');
    }

    /**
     * Decides if the choice should be maked as selected
     *
     * @param FormInterface $form
     * @param $id
     * @return bool
     */
    private function isSelectedOption(FormInterface $form, $id)
    {
        if ($form->getData()) {
            foreach ($form->getData() as $selected) {
                if ($selected->getId() == $id) {
                    return true;
                }
            }
        }

        return false;
    }
}
