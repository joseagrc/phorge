<?php

final class PhorgeAuthLoginMessageType
  extends PhorgeAuthMessageType {

  const MESSAGEKEY = 'auth.login';

  public function getDisplayName() {
    return pht('Login Screen Instructions');
  }

  public function getShortDescription() {
    return pht(
      'Guidance shown on the main login screen before users log in or '.
      'register.');
  }

}
