<?php

namespace Drupal\formstack;

use Drupal;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class Formstack implements ContainerInjectionInterface {
  public static $apiUrl = 'https://www.formstack.com/api/v2';
  const FORM = 'form';

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   *
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('formstack.config');
  }

  /**
   * Makes a Formstack API request.
   *
   * This function uses v2 of the Formstack API
   * and returns a JSON decoded response.
   *
   * @param string $method
   *   The API web method.
   * @param array $args
   *   The parameters for the API request.
   *
   * @return FormstackResult The JSON decodes response
   *   The JSON decodes response
   */
  public function request($method, $args = []) {
    $oauth_token = $this->config->get('access_token');
    if (!empty($oauth_token)) {
      $args['oauth_token'] = $oauth_token;

      $url = self::$apiUrl . "/" . $method . '.json';

      $query = UrlHelper::buildQuery($args);

      // Due to issues with drupal_http_request.
      $url = $url . '?' . $query;

      try {
        $response = Drupal::httpClient()->get($url, ['headers' => ['Accept' => 'application/json']]);
        return new FormstackResult($response);
      }
      catch (RequestException $e) {
        watchdog_exception('formstack', $e);
      }

      return new FormstackResult();
    }
    else {
      return new FormstackResult();
    }
  }

  /**
   * @param null $form_id
   * @return FormstackResult
   */
  public function form($form_id = NULL) {
    $method = self::FORM;

    if ($form_id !== NULL) {
      // Method for specific form details.
      $method .= '/' . $form_id;
    }

    return $this->request($method);
  }

  /**
   * Instantiates a new instance of this class.
   *
   * This is a factory method that returns a new instance of this class. The
   * factory should pass any needed dependencies into the constructor of this
   * class, but not the container itself. Every call to this method must return
   * a new instance of this class; that is, it may not implement a singleton.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this instance should use.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'));
  }

}