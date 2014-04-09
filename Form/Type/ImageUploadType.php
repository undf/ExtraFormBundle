<?php

namespace Undf\FormBundle\Form\Type;

use InvalidArgumentException;
use Sonata\MediaBundle\Form\DataTransformer\ProviderDataTransformer;
use Sonata\MediaBundle\Provider\Pool;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Image;

class ImageUploadType extends AbstractType
{

    protected $pool;
    protected $class;

    /**
     * @param Pool   $pool
     * @param string $class
     */
    public function __construct(Pool $pool, $class)
    {
        $this->pool = $pool;
        $this->class = $class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new ProviderDataTransformer($this->pool, array(
            'provider' => $options['provider'],
            'context' => $options['context'],
        )));
        $builder
            ->add($options['file_property'], 'file', array(
                'required' => $options['required'],
                'horizontal' => $options['horizontal'],
                'constraints' => new Image(array(
                        'maxSize' => $options['max_size']
                    )),
                'error_bubbling' => $options['error_bubbling']
            ))
            ->add($options['name_property'], 'text', array(
                'required' => $options['required'],
                'horizontal' => $options['horizontal'],
                'error_bubbling' => $options['error_bubbling']
            ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'required' => false,
            'horizontal' => false,
            'error_bubbling' => true,
            'file_property' => 'image',
            'name_property' => 'imageName',
            'translation_domain' => 'messages',
            'max_size' => '1M',
            'default_image_url' => '',
            'provider' => 'sonata.media.provider.image',
            'context' => 'user_gallery',
            'format' => 'thumbnail',
            'data_class' => $this->class
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

        $imageUrl = $defaultImageUrl;
        try {
            if($form->getData()) {
                $imageUrl = $form->getData()->getUrl($options['format']);
            }
        } catch (InvalidArgumentException $e) {
        }
        $view->vars['image_url'] = $imageUrl;
    }

}
