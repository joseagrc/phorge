<?php

/**
 * Authentication adapter for Phorge OAuth2.
 */
final class PhutilPhorgeAuthAdapter extends PhutilOAuthAuthAdapter {

  private $phorgeBaseURI;
  private $adapterDomain;

  public function setPhorgeBaseURI($uri) {
    $this->phorgeBaseURI = $uri;
    return $this;
  }

  public function getPhorgeBaseURI() {
    return $this->phorgeBaseURI;
  }

  public function getAdapterDomain() {
    return $this->adapterDomain;
  }

  public function setAdapterDomain($domain) {
    $this->adapterDomain = $domain;
    return $this;
  }

  public function getAdapterType() {
    return 'phorge';
  }

  public function getAccountID() {
    return $this->getOAuthAccountData('phid');
  }

  public function getAccountEmail() {
    return $this->getOAuthAccountData('primaryEmail');
  }

  public function getAccountName() {
    return $this->getOAuthAccountData('userName');
  }

  public function getAccountImageURI() {
    return $this->getOAuthAccountData('image');
  }

  public function getAccountURI() {
    return $this->getOAuthAccountData('uri');
  }

  public function getAccountRealName() {
    return $this->getOAuthAccountData('realName');
  }

  protected function getAuthenticateBaseURI() {
    return $this->getPhorgeURI('oauthserver/auth/');
  }

  protected function getTokenBaseURI() {
    return $this->getPhorgeURI('oauthserver/token/');
  }

  public function getScope() {
    return '';
  }

  public function getExtraAuthenticateParameters() {
    return array(
      'response_type' => 'code',
    );
  }

  public function getExtraTokenParameters() {
    return array(
      'grant_type' => 'authorization_code',
    );
  }

  protected function loadOAuthAccountData() {
    $uri = id(new PhutilURI($this->getPhorgeURI('api/user.whoami')))
      ->replaceQueryParam('access_token', $this->getAccessToken());
    list($body) = id(new HTTPSFuture($uri))->resolvex();

    try {
      $data = phutil_json_decode($body);
      return $data['result'];
    } catch (PhutilJSONParserException $ex) {
      throw new Exception(
        pht(
          'Expected valid JSON response from "user.whoami" request.'),
        $ex);
    }
  }

  private function getPhorgeURI($path) {
    return rtrim($this->phorgeBaseURI, '/').'/'.ltrim($path, '/');
  }

}
