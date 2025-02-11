<?php

final class PhorgeEmailContentSource
  extends PhorgeContentSource {

  const SOURCECONST = 'email';

  public function getSourceName() {
    return pht('Email');
  }

  public function getSourceDescription() {
    return pht('Content sent by electronic mail, also known as e-mail.');
  }

}
