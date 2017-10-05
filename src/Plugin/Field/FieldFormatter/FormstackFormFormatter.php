<?php

namespace Drupal\formstack\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\formstack\Formstack;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'formstack_form_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "formstack_form_formatter",
 *   label = @Translation("Formstack form formatter"),
 *   field_types = {
 *     "formstack_form"
 *   },
 *     quickedit = {
 *     "editor" = "plain_text"
 *   }
 * )
 */
class FormstackFormFormatter extends FormatterBase implements ContainerFactoryPluginInterface {
  /**
   * @var \Drupal\formstack\Formstack
   */
  private $formstack;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Implement default settings.
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      // Implement settings form.
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = $this->viewValue($item);
    }
    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    $result = $this->formstack->form($item->formstack_id);
    $embed = Html::decodeEntities($result->getResult()->javascript);

    // Get settings and extract default values setting.
    if (!empty($item->settings)) {
      $settings = unserialize($item->settings);
    }
    if (!empty($settings['default_values'])) {
      $user = User::load(\Drupal::currentUser()->id());
      $data = compact('user');
      $token = \Drupal::token();
      foreach ($settings['default_values'] as $k => &$v) {
        $v = $token->replace($v, $data);
      }
      // If we have default values, inject them into the embed code.
      // TODO: handle if already has a query string.
      $query = http_build_query($settings['default_values']);
      $embed = preg_replace('/^(.*<script.*?src=")(.*?)(".*)$/', "$1$2?{$query}$3", $embed);
    }



    // Check to see if we have set any default values, and if so adjust embed URL accordingly.
    return [
      '#type' => 'inline_template',
      '#template' => '{{ value|raw }}',
      '#context' => ['value' => $embed],
    ];
  }

  /**
   *
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, Formstack $formstack) {
    {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    }
    $this->formstack = $formstack;
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
        $configuration['label'],
        $configuration['view_mode'],
        $configuration['third_party_settings'],
        $container->get('formstack.formstack')
    );
  }

}
