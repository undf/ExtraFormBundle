<?php

namespace Undf\FormBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class MultipleChoiceType extends EntityType
{

    public function getName()
    {
        return 'multiple_choice';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);
        $resolver->setRequired(array('api_url'));
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['api_url'] = $options['api_url'];
    }
}
