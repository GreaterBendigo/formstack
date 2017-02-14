<?php

namespace Drupal\formstack\Plugin\Field\FieldWidget;

use Drupal;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\formstack\Formstack;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'formstack_form_widget' widget.
 *
 * @FieldWidget(
 *   id = "formstack_form_widget",
 *   label = @Translation("Formstack form widget"),
 *   field_types = {
 *     "formstack_form"
 *   }
 * )
 */
class FormstackFormWidget extends WidgetBase implements ContainerFactoryPluginInterface {

    /**
     * @var Formstack
     */
    protected $formstack;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * FormstackFormWidget constructor.
     * @param mixed $plugin_id
     * @param FieldDefinitionInterface $plugin_definition
     * @param FieldDefinitionInterface $field_definition
     * @param array $settings
     * @param array $third_party_settings
     * @param ConfigFactoryInterface $config_factory
     * @param Formstack $formstack
     * @param LoggerChannelFactoryInterface $logger
     */
    public function __construct(
        $plugin_id,
        $plugin_definition,
        FieldDefinitionInterface $field_definition,
        array $settings,
        array $third_party_settings,
        Formstack $formstack,
        LoggerChannelFactoryInterface $logger) {

        $this->formstack = $formstack;
        $this->logger = $logger->get('formstack');

        parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    }


  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $widget = $element;
    $widget['#delta'] = $delta;

    $element = [];
    $formstackResult = $this->formstack->form();

    if($formstackResult->isSuccessful()) {
        $element['formstack_id'] = $element + array(
                '#type' => 'select',
                '#options' => $formstackResult->getFormsList(),
                '#empty_option' => '-- No Form --',
                '#default_value' => $items->formstack_id,
            );
    }
    else {
        $this->logger->
            warning('Formstack Error: @errorcode @errormessage',
            array('@errorcode' => $formstackResult->getStatusCode(), '@errormessage' => $formstackResult->getErrorMessage()));
    }
    return $element;
  }

    /**
     * Creates an instance of the plugin.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     *   The container to pull out services used in the plugin.
     * @param array $configuration
     *   A configuration array containing information about the plugin instance.
     * @param string $plugin_id
     *   The plugin ID for the plugin instance.
     * @param mixed $plugin_definition
     *   The plugin implementation definition.
     *
     * @return static
     *   Returns an instance of this plugin.
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static(
            $plugin_id,
            $plugin_definition,
            $configuration['field_definition'],
            $configuration['settings'],
            $configuration['third_party_settings'],
            $container->get('formstack.formstack'),
            $container->get('logger.factory')
        );
    }
}
