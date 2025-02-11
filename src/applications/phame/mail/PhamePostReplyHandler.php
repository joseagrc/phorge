<?php

final class PhamePostReplyHandler
  extends PhorgeApplicationTransactionReplyHandler {

  public function validateMailReceiver($mail_receiver) {
    if (!($mail_receiver instanceof PhamePost)) {
      throw new Exception(
        pht('Mail receiver is not a %s.', 'PhamePost'));
    }
  }

  public function getObjectPrefix() {
    return PhorgePhamePostPHIDType::TYPECONST;
  }

}
