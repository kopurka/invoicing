<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InitialDataFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('csv', FileType::class, [
                'required' => true,
                'label' => 'CSV file',
                'constraints' => [
                    new File([
                        'maxSize' => '1M',
                        'maxSizeMessage' => 'Max file size is 1 MB',
                        'mimeTypes' => [
                            'text/plain',
                            'text/x-csv',
                            'application/vnd.ms-excel',
                            'application/csv',
                            'application/x-csv',
                            'text/csv',
                            'text/comma-separated-values',
                            'text/x-comma-separated-values',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid CSV file',
                    ]),
                ]
            ])
            ->add('exchange_rates', HiddenType::class, [
                'required' => true,
            ])
            ->add('output_currency', HiddenType::class, [
                'required' => true,
            ])
            ->add('customer_name', TextType::class, [
                'required' => false,
                'label' => 'Customer name',
            ])
            ->add('vat', TextType::class, [
                'required' => false,
                'label' => 'Customer VAT',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Submit && Calculate'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
