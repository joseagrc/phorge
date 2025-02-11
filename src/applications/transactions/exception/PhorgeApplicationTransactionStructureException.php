<?php

final class PhorgeApplicationTransactionStructureException
  extends Exception {

  public function __construct(
    PhorgeApplicationTransaction $xaction,
    $message) {

    $full_message = pht(
      'Attempting to apply a transaction (of class "%s", with type "%s") '.
      'which has not been constructed correctly: %s',
      get_class($xaction),
      $xaction->getTransactionType(),
      $message);

    parent::__construct($full_message);
  }

}
