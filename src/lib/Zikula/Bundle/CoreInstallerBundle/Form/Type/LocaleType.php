<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\Bundle\CoreInstallerBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Zikula\Common\Translator\IdentityTranslator;

class LocaleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->setTranslator($options['translator']);
        $builder
            ->add('locale', ChoiceType::class, [
                'label' => $this->__('Select your default language'),
                'label_attr' => [
                    'class' => 'col-sm-3'
                ],
                'choices' => $options['choices'],
                'data' => $options['choice'],
            ])
        ;
    }

    public function getBlockPrefix()
    {
        return 'locale';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => ['English' => 'en'],
            'choice' => 'en',
            'translator' => new IdentityTranslator()
        ]);
    }
}
