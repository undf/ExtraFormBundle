<?php

namespace Undf\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\ReversedTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DataTransformerChain;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToArrayTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToStringTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToLocalizedStringTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToTimestampTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToRfc3339Transformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\ArrayToPartsTransformer;
use Undf\FormBundle\Form\DataTransformer\DateTimeToStringsTransformer;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class DateTimePickerType extends AbstractType
{

    const DEFAULT_DATE_FORMAT = \IntlDateFormatter::MEDIUM;
    const DEFAULT_TIME_FORMAT = \IntlDateFormatter::MEDIUM;

    /**
     * This is not quite the HTML5 format yet, because ICU lacks the
     * capability of parsing and generating RFC 3339 dates, which
     * are like the below pattern but with a timezone suffix. The
     * timezone suffix is
     *
     *  * "Z" for UTC
     *  * "(-|+)HH:mm" for other timezones (note the colon!)
     *
     * For more information see:
     *
     * http://userguide.icu-project.org/formatparse/datetime#TOC-Date-Time-Format-Syntax
     * http://www.w3.org/TR/html-markup/input.datetime.html
     * http://tools.ietf.org/html/rfc3339
     *
     * An ICU ticket was created:
     * http://icu-project.org/trac/ticket/9421
     *
     * It was supposedly fixed, but is not available in all PHP installations
     * yet. To temporarily circumvent this issue, DateTimeToRfc3339Transformer
     * is used when the format matches this constant.
     */
    const HTML5_FORMAT = "yyyy-MM-dd'T'HH:mm:ssZZZZZ";

    private static $acceptedFormats = array(
        \IntlDateFormatter::FULL,
        \IntlDateFormatter::LONG,
        \IntlDateFormatter::MEDIUM,
        \IntlDateFormatter::SHORT,
    );

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $dateFormat = $options['date_format'];
        $timeFormat = 'H';

        if ($options['with_minutes']) {
            $timeFormat .= ':i';
        }

        if ($options['with_seconds']) {
            $timeFormat .= ':s';
        }

        $builder
                ->addViewTransformer(new DateTimeToStringsTransformer(
                        $options['model_timezone'], $options['view_timezone'], $dateFormat, $timeFormat
                ))
                ->add('date', 'text')
                ->add('time', 'text')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['widget'] = $options['widget'];
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {

        // Defaults to the value of "widget"
        $dateWidget = function (Options $options) {
                    return $options['widget'];
                };

        // Defaults to the value of "widget"
        $timeWidget = function (Options $options) {
                    return $options['widget'];
                };

        $resolver->setDefaults(array(
            'model_timezone' => null,
            'view_timezone' => null,
            'date_format' => 'Y-m-d',
            'widget' => 'native',
            'date_widget' => $dateWidget,
            'time_widget' => $timeWidget,
            'with_minutes' => true,
            'with_seconds' => false,
            // Don't modify \DateTime classes by reference, we treat
            // them like immutable value objects
            'by_reference' => false,
            'error_bubbling' => false,
            // If initialized with a \DateTime object, FormType initializes
            // this option to "\DateTime". Since the internal, normalized
            // representation is not \DateTime, but an array, we need to unset
            // this option.
            'data_class' => null,
            'compound' => true,
        ));

        // Don't add some defaults in order to preserve the defaults
        // set in DateType and TimeType
        $resolver->setOptional(array(
            'empty_value',
        ));

        $resolver->setAllowedValues(array(
            'date_widget' => array(
                null, // inherit default from DateType
                'native',
            ),
            'time_widget' => array(
                null, // inherit default from TimeType
                'native',
            ),
            // This option will overwrite "date_widget" and "time_widget" options
            'widget' => array(
                null, // default, don't overwrite options
                'native',
            ),
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'datetime_picker';
    }

}
