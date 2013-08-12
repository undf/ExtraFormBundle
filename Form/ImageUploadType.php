<?php

namespace Undf\FormBundle\Form;

use Symfony\Component\Form\AbstractType;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ImageUploadType extends AbstractType
{

    /**
     * @var Vich\UploaderBundle\Templating\Helper\UploaderHelper
     */
    private $uploader;

    public function __construct(UploaderHelper $uploader)
    {
        $this->uploader = $uploader;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
                ->add($options['file_property'], 'file', array(
                    'required' => false,
                    'attr' => array(
                        'class' => 'hide'
                    )
                ))
                ->add($options['name_property'], 'text', array(
                    'required' => false
                ))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'file_property' => 'image',
            'name_property' => 'imageName',
            'translation_domain' => 'messages',
        ));
        $resolver->setRequired(array(
            'data_class',
            'default_image_url'
        ));

    }

    public function getParent()
    {
        return 'form';
    }

    public function getName()
    {
        return 'image_upload';
    }

    /**
     * Pass the image url to the view
     *
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['translation_domain'] = $options['translation_domain'];
        $view->vars['default_image_url'] = $options['default_image_url'];
        $view->vars['file_property'] = $options['file_property'];
        $view->vars['name_property'] = $options['name_property'];

        try {
            $parentData = $form->getParent()->getData();
            $imageUrl = $this->uploader->asset($parentData, $options['file_property']);
        } catch (\InvalidArgumentException $e) {
            $imageUrl = $options['default_image_url'];
        }
        $view->vars['image_url'] = $imageUrl;
    }

}
