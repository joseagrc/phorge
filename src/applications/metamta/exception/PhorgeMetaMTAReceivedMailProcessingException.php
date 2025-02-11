<?php

final class PhorgeMetaMTAReceivedMailProcessingException
  extends Exception {

  private $statusCode;

  public function getStatusCode() {
    return $this->statusCode;
  }

  public function __construct($status_code /* ... */) {
    $args = func_get_args();
    $this->statusCode = $args[0];

    $args = array_slice($args, 1);
    $parent = get_parent_class($this);
    call_user_func_array(array($parent, '__construct'), $args);
  }

}
