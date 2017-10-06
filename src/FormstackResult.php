<?php

namespace Drupal\formstack;

use Psr\Http\Message\ResponseInterface;

/**
 *
 */
class FormstackResult {
  protected $result;

  protected $statusCode;

  protected $successful = FALSE;

  protected $error_message = 'No Error';

  /**
   *
   */
  public function __construct(ResponseInterface $response = NULL) {
    if ($response == NULL) {
      return;
    }

    $statusCode = $response->getStatusCode();

    if ($statusCode == 200 || $statusCode == 201) {
      $this->result = json_decode($response->getBody());
      $this->successful = TRUE;
    }
    else {
      $this->set_error_message($statusCode);
    }
  }

  /**
   *
   */
  protected function set_error_message($error_code) {

    $error_message = 'Unknown Error';

    switch ($error_code) {
      case 400:
        $error_message = 'Bad Request - The request URI was invalid.';
        break;

      case 401:
        $error_message = 'Unauthorized - Valid oAuth credentials were not supplied.';
        break;

      case 403:
        $error_message = 'Forbidden - The current does not have access to this method.';
        break;

      case 404:
        $error_message = 'Not Found - The resource requested could not be found.';
        break;

      case 405:
        $error_message = 'Method Not Allowed - The requested method does not exist.';
        break;

      case 415:
        $error_message = 'Unsupported Media Type - A valid media type (JSON, XML, HTTP URL Encoded) was not used.';
        break;

      case 500:
        $error_message = 'Internal Server Error - An error occurred while processing the request.';
        break;
    }

    return $error_message;
  }

  /**
   * @return bool
   */
  public function isSuccessful() {
    return $this->successful;
  }

  /**
   * @return string
   */
  public function getErrorMessage() {
    return $this->error_message;
  }

  /**
   * @return array
   */
  public function getFormsList() {
    $forms = $this->result->forms;

    $form_options = [];

    foreach ($forms as $form_option) {
      $form_options[$form_option->id] = $form_option->name;
    }

    return $form_options;
  }

  /**
   * @return mixed
   */
  public function getStatusCode() {
    return $this->statusCode;
  }

  /**
   * @return mixed
   */
  public function getResult() {
    return $this->result;
  }

}
