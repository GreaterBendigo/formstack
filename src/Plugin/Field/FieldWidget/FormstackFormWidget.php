<?php

namespace Drupal\formstack\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\formstack\Formstack;
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
   * @var \Drupal\formstack\Formstack
   */
  protected $formstack;
  /**
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * FormstackFormWidget constructor.
   *
   * @param mixed $plugin_id
   * @param \Drupal\Core\Field\FieldDefinitionInterface $plugin_definition
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   * @param array $settings
   * @param array $third_party_settings
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\formstack\Formstack $formstack
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
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
   * Ajax callback to retrieve Frormstack form field data.
   *
   * @param array $form
   *   Drupal form array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   A render array of formstack form details.
   */
  public static function ajaxUpdateFormstackFields(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $formstack_id = $triggering_element['#value'];
    $delta = $triggering_element['#delta'];
    $details = self::getFormDetails($formstack_id);

    return $details;
  }

  /**
   * Generate an item list of Formstack form details.
   *
   * @param string $formstack_id
   *   $formstack_id The formstack form id.
   *
   * @return array
   *   A render array of formstack field ids and labels.
   */
  public static function getFormDetails($formstack_id) {
    $formstack = \Drupal::service('formstack.formstack');
    $response = $formstack->form($formstack_id)->getResult();
    $fields = [];
    foreach ($response->fields as $field) {
      $fields[] = 'field' . $field->id . ' - ' . $field->label;
      if ($field->type == 'name') {
        $fields[] = 'field' . $field->id . '-first - ' . $field->label . ' (First)';
        $fields[] = 'field' . $field->id . '-last - ' . $field->label . ' (Last)';
      }
    }
    return [
      '#theme' => 'item_list',
      '#title' => t('Formstack Fields'),
      '#items' => $fields,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $formstackResult = $this->formstack->form();

    if ($formstackResult->isSuccessful()) {
      $element['formstack_id'] = $element + [
        '#type' => 'select',
        '#options' => $formstackResult->getFormsList(),
        '#empty_option' => '-- No Form --',
        '#default_value' => $items->formstack_id,
        // Workaround for https://www.drupal.org/node/2745491
        '#submit' => [],
        '#ajax' => [
          'callback' => [get_class($this), 'ajaxUpdateFormstackFields'],
          'wrapper' => 'formstack-default-values-' . $delta . '-container',
          'method' => 'html',
        ],
      ];
    }
    else {
      $element['formstack_id'] = array_merge($element, [
        '#type' => 'select',
        '#options' => [],
        '#description' => 'Error: ' . $formstackResult->getErrorMessage(),
        '#empty_option' => '-- No Form --',
        '#default_value' => $items->formstack_id,
      ]);

      $this->logger
        ->warning('Formstack Error: @errorcode @errormessage',
            ['@errorcode' => $formstackResult->getStatusCode(), '@errormessage' => $formstackResult->getErrorMessage()]);
    }

    // Get settings and extract default values setting.
    if (!empty($items->settings)) {
      $settings = unserialize($items->settings);
    }

    $default_values_string = '';
    if (!empty($settings['default_values'])) {
      $default_values = [];
      foreach ($settings['default_values'] as $key => $value) {
        $default_values[] = "{$key}|{$value}";
      }
      $default_values_string = implode("\n", $default_values);
    }

    $element['default_values'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default Values'),
      '#default_value' => $default_values_string,
      '#description' => $this->t('A list of default values for form fields, one per line, in the format formstack_field|value. The tokens below are available.'),
    ];

    if (!empty($items->formstack_id)) {
      $details = self::getFormDetails($items->formstack_id);
      $details['#prefix'] = '<div id="formstack-default-values-' . $delta . '-container"></div>';
      $details['#suffix'] = '</div>';
    }
    else {
      $details = [
        '#markup' => '<div id="formstack-default-values-' . $delta . '-container"></div>',
      ];
    }

    $element['form_details'] = $details;

    $element['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['user']
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$value) {
      if (!empty($value['default_values'])) {
        $default_values = [];
        foreach (explode("\n", $value['default_values']) as $row) {
          $split = explode('|', $row);
          $default_values[trim($split[0])] = trim($split[1]);
        }
        if (!empty($default_values)) {
          $value['settings'] = serialize(compact('default_values'));
        }
      }
    }
    return $values;
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
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
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
