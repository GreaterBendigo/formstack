<?php

namespace Drupal\formstack\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigManager;

/**
 * Class FormstackConfigForm.
 *
 * @package Drupal\formstack\Form
 */
class FormstackConfigForm extends ConfigFormBase {

  /**
   * Drupal\Core\Config\ConfigManager definition.
   *
   * @var Drupal\Core\Config\ConfigManager
   */
  protected $config_manager;

  /**
   *
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
      ConfigManager $config_manager
    ) {
    parent::__construct($config_factory);
    $this->config_manager = $config_manager;
  }

  /**
   *
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
            $container->get('config.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'formstack.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'formstack_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('formstack.config');
    $form['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Token'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('access_token'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('formstack.config')
      ->set('access_token', $form_state->getValue('access_token'))
      ->save();
  }

}
