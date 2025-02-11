<?php

final class PhorgeFileinfoSetupCheck extends PhorgeSetupCheck {

  public function getDefaultGroup() {
    return self::GROUP_OTHER;
  }

  protected function executeChecks() {
    if (!extension_loaded('fileinfo')) {
      $message = pht(
        "The '%s' extension is not installed. Without '%s', ".
        "support, this software may not be able to determine the MIME types ".
        "of uploaded files.",
        'fileinfo',
        'fileinfo');

      $this->newIssue('extension.fileinfo')
        ->setName(pht("Missing '%s' Extension", 'fileinfo'))
        ->setMessage($message);
    }
  }
}
