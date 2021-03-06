<?php

namespace Cidetsi\DepartamentosBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CarreraForm extends AbstractType
{
    private $departamento = null;

    public function __construct($departamento = null) {
        $this->departamento = $departamento;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        if ($this->departamento == null) {
            $builder->add('departamento', 'entity', array(
                'class' => 'CidetsiDepartamentosBundle:Departamento'));
        }

        $builder->add('name', 'text', array(
                      'required' => true,
                      'label' => 'Nombre:'))
                ->add('abbreviation', 'text', array(
                      'required' => true,
                      'label' => 'Abreviatura:'));
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Cidetsi\DepartamentosBundle\Entity\Carrera'
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return 'carrera';
    }
}
