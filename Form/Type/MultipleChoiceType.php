<?php

namespace Undf\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Undf\FormBundle\Form\DataTransformer\CollectionToJsonTransformer;

class MultipleChoiceType extends AbstractType
{

    private $modelManager;
    private $serializer;

    public function __construct($modelManager, $serializer)
    {
        $this->modelManager = $modelManager;
        $this->serializer = $serializer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $collectionToJsonTransformer = new CollectionToJsonTransformer($this->serializer, $this->modelManager, $options['multiple_choice_class']);
        $builder->addViewTransformer($collectionToJsonTransformer);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'multiple_choice_class' => null,
            'horizontal' => false,
            'csrf_protection' => false,
            'show_legend' => false,
            'error_bubbling' => false,
            'required' => false,
            'translation_domain' => 'messages',
        ));
    }

    public function getParent()
    {
        return 'hidden';
    }

    public function getName()
    {
        return 'multiple_choice';
    }
}
