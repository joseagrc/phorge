<?php

final class PassphraseCredentialFerretEngine
  extends PhorgeFerretEngine {

  public function getApplicationName() {
    return 'passphrase';
  }

  public function getScopeName() {
    return 'credential';
  }

  public function newSearchEngine() {
    return new PassphraseCredentialSearchEngine();
  }

}
