<?php

namespace Undf\FormBundle\Form;

use Symfony\Component\Form\AbstractType;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\ValidatorInterface;
use Undf\FormBundle\Listener\ImageUploadListener;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ImageUploadType extends AbstractType
{

    /**
     * @var Vich\UploaderBundle\Templating\Helper\UploaderHelper
     */
    private $uploader;

    /**
     * @var Symfony\Component\Validator\ValidatorInterface
     */
    private $validator;

    /**
     * @var Doctrine\Bundle\DoctrineBundle\Registry
     */
    private $doctrine;

    /**
     * @var Symfony\Component\PropertyAccess\PropertyAccessor
     */
    private $propertyAccessor;

    public function __construct(UploaderHelper $uploader, ValidatorInterface $validator, Registry $doctrine, PropertyAccessor $propertyAccessor)
    {
        $this->uploader = $uploader;
        $this->validator = $validator;
        $this->doctrine = $doctrine;
        $this->propertyAccessor = $propertyAccessor;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
                ->add($options['file_property'], 'file', array(
                    'required' => $options['required'],
                    'constraints' => new Image(array(
                        'maxSize' => $options['max_size']
                            )),
                    'attr' => array(
                        'style' => 'display:hidden'
                    )
                ))
                ->add($options['name_property'], 'text', array(
                    'required' => $options['required'],
                    'attr' => array(
                        'style' => 'display:hidden'
                    )
                ))
        ;

        $builder->addEventSubscriber(new ImageUploadListener($this->validator, $this->doctrine->getManagerForClass($options['data_class']), $this->propertyAccessor));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'required' => false,
            'file_property' => 'image',
            'name_property' => 'imageName',
            'translation_domain' => 'messages',
            'max_size' => '1M',
            'default_image_url' => ''
        ));
        $resolver->setRequired(array(
            'data_class',
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
        //Let the parent form overwrite the default_image_url when building the view,
        //which means parent can already access the set data
        $defaultImageUrl = isset($view->parent->vars['default_image_url']) ? $view->parent->vars['default_image_url'] : $options['default_image_url'];

        $view->vars['translation_domain'] = $options['translation_domain'];
        $view->vars['default_image_url'] = $defaultImageUrl;
        $view->vars['file_property'] = $options['file_property'];
        $view->vars['name_property'] = $options['name_property'];
        $view->vars['label'] = false;

        try {
            $parentData = $form->getParent()->getData();
            $imageUrl = $this->uploader->asset($parentData, $options['file_property']);
        } catch (\InvalidArgumentException $e) {
            $imageUrl = $defaultImageUrl;
        }
        $view->vars['image_url'] = $imageUrl;
    }

}
