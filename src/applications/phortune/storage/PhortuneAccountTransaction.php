<?php

final class PhortuneAccountTransaction
  extends PhorgeModularTransaction {

  public function getApplicationName() {
    return 'phortune';
  }

  public function getApplicationTransactionType() {
    return PhortuneAccountPHIDType::TYPECONST;
  }

  public function getBaseTransactionClass() {
    return 'PhortuneAccountTransactionType';
  }

}
