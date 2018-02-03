<?php

namespace Okvpn\Bundle\BetterOroBundle\Form\Type;

use Oro\Bundle\QueryDesignerBundle\Form\Type\FieldChoiceType;

class DateFieldChoiceType extends FieldChoiceType
{
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_date_field_choice';
    }
}
