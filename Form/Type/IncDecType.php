<?php

namespace Undf\FormBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class IncDecType extends IntegerType
{

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'inc_dec';
    }

}
