<?php

namespace Okvpn\Bundle\BetterOroBundle\Form\Extension;

use Oro\Bundle\QueryDesignerBundle\Form\Type\DateFieldChoiceType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateFieldChoiceExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return DateFieldChoiceType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'include_fields' => null //Show all fields, user must select that you want to use
            ]
        );
    }
}
