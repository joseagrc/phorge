<?php

abstract class PhorgeAuthFactor extends Phobject {

  abstract public function getFactorName();
  abstract public function getFactorShortName();
  abstract public function getFactorKey();
  abstract public function getFactorCreateHelp();
  abstract public function getFactorDescription();
  abstract public function processAddFactorForm(
    PhorgeAuthFactorProvider $provider,
    AphrontFormView $form,
    AphrontRequest $request,
    PhorgeUser $user);

  abstract public function renderValidateFactorForm(
    PhorgeAuthFactorConfig $config,
    AphrontFormView $form,
    PhorgeUser $viewer,
    PhorgeAuthFactorResult $validation_result);

  public function getParameterName(
    PhorgeAuthFactorConfig $config,
    $name) {
    return 'authfactor.'.$config->getID().'.'.$name;
  }

  public static function getAllFactors() {
    return id(new PhutilClassMapQuery())
      ->setAncestorClass(__CLASS__)
      ->setUniqueMethod('getFactorKey')
      ->execute();
  }

  protected function newConfigForUser(PhorgeUser $user) {
    return id(new PhorgeAuthFactorConfig())
      ->setUserPHID($user->getPHID())
      ->setFactorSecret('');
  }

  protected function newResult() {
    return new PhorgeAuthFactorResult();
  }

  public function newIconView() {
    return id(new PHUIIconView())
      ->setIcon('fa-mobile');
  }

  public function canCreateNewProvider() {
    return true;
  }

  public function getProviderCreateDescription() {
    return null;
  }

  public function canCreateNewConfiguration(
    PhorgeAuthFactorProvider $provider,
    PhorgeUser $user) {
    return true;
  }

  public function getConfigurationCreateDescription(
    PhorgeAuthFactorProvider $provider,
    PhorgeUser $user) {
    return null;
  }

  public function getConfigurationListDetails(
    PhorgeAuthFactorConfig $config,
    PhorgeAuthFactorProvider $provider,
    PhorgeUser $viewer) {
    return null;
  }

  public function newEditEngineFields(
    PhorgeEditEngine $engine,
    PhorgeAuthFactorProvider $provider) {
    return array();
  }

  public function newChallengeStatusView(
    PhorgeAuthFactorConfig $config,
    PhorgeAuthFactorProvider $provider,
    PhorgeUser $viewer,
    PhorgeAuthChallenge $challenge) {
    return null;
  }

  /**
   * Is this a factor which depends on the user's contact number?
   *
   * If a user has a "contact number" factor configured, they can not modify
   * or switch their primary contact number.
   *
   * @return bool True if this factor should lock contact numbers.
   */
  public function isContactNumberFactor() {
    return false;
  }

  abstract public function getEnrollDescription(
    PhorgeAuthFactorProvider $provider,
    PhorgeUser $user);

  public function getEnrollButtonText(
    PhorgeAuthFactorProvider $provider,
    PhorgeUser $user) {
    return pht('Continue');
  }

  public function getFactorOrder() {
    return 1000;
  }

  final public function newSortVector() {
    return id(new PhutilSortVector())
      ->addInt($this->canCreateNewProvider() ? 0 : 1)
      ->addInt($this->getFactorOrder())
      ->addString($this->getFactorName());
  }

  protected function newChallenge(
    PhorgeAuthFactorConfig $config,
    PhorgeUser $viewer) {

    $engine = $config->getSessionEngine();

    return PhorgeAuthChallenge::initializeNewChallenge()
      ->setUserPHID($viewer->getPHID())
      ->setSessionPHID($viewer->getSession()->getPHID())
      ->setFactorPHID($config->getPHID())
      ->setIsNewChallenge(true)
      ->setWorkflowKey($engine->getWorkflowKey());
  }

  abstract public function getRequestHasChallengeResponse(
    PhorgeAuthFactorConfig $config,
    AphrontRequest $response);

  final public function getNewIssuedChallenges(
    PhorgeAuthFactorConfig $config,
    PhorgeUser $viewer,
    array $challenges) {
    assert_instances_of($challenges, 'PhorgeAuthChallenge');

    $now = PhorgeTime::getNow();

    // Factor implementations may need to perform writes in order to issue
    // challenges, particularly push factors like SMS.
    $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();

    $new_challenges = $this->newIssuedChallenges(
      $config,
      $viewer,
      $challenges);

    if ($this->isAuthResult($new_challenges)) {
      unset($unguarded);
      return $new_challenges;
    }

    assert_instances_of($new_challenges, 'PhorgeAuthChallenge');

    foreach ($new_challenges as $new_challenge) {
      $ttl = $new_challenge->getChallengeTTL();
      if (!$ttl) {
        throw new Exception(
          pht('Newly issued MFA challenges must have a valid TTL!'));
      }

      if ($ttl < $now) {
        throw new Exception(
          pht(
            'Newly issued MFA challenges must have a future TTL. This '.
            'factor issued a bad TTL ("%s"). (Did you use a relative '.
            'time instead of an epoch?)',
            $ttl));
      }
    }

    foreach ($new_challenges as $challenge) {
      $challenge->save();
    }

    unset($unguarded);

    return $new_challenges;
  }

  abstract protected function newIssuedChallenges(
    PhorgeAuthFactorConfig $config,
    PhorgeUser $viewer,
    array $challenges);

  final public function getResultFromIssuedChallenges(
    PhorgeAuthFactorConfig $config,
    PhorgeUser $viewer,
    array $challenges) {
    assert_instances_of($challenges, 'PhorgeAuthChallenge');

    $result = $this->newResultFromIssuedChallenges(
      $config,
      $viewer,
      $challenges);

    if ($result === null) {
      return $result;
    }

    if (!$this->isAuthResult($result)) {
      throw new Exception(
        pht(
          'Expected "newResultFromIssuedChallenges()" to return null or '.
          'an object of class "%s"; got something else (in "%s").',
          'PhorgeAuthFactorResult',
          get_class($this)));
    }

    return $result;
  }

  final public function getResultForPrompt(
    PhorgeAuthFactorConfig $config,
    PhorgeUser $viewer,
    AphrontRequest $request,
    array $challenges) {
    assert_instances_of($challenges, 'PhorgeAuthChallenge');

    $result = $this->newResultForPrompt(
      $config,
      $viewer,
      $request,
      $challenges);

    if (!$this->isAuthResult($result)) {
      throw new Exception(
        pht(
          'Expected "newResultForPrompt()" to return an object of class "%s", '.
          'but it returned something else ("%s"; in "%s").',
          'PhorgeAuthFactorResult',
          phutil_describe_type($result),
          get_class($this)));
    }

    return $result;
  }

  protected function newResultForPrompt(
    PhorgeAuthFactorConfig $config,
    PhorgeUser $viewer,
    AphrontRequest $request,
    array $challenges) {
    return $this->newResult();
  }

  abstract protected function newResultFromIssuedChallenges(
    PhorgeAuthFactorConfig $config,
    PhorgeUser $viewer,
    array $challenges);

  final public function getResultFromChallengeResponse(
    PhorgeAuthFactorConfig $config,
    PhorgeUser $viewer,
    AphrontRequest $request,
    array $challenges) {
    assert_instances_of($challenges, 'PhorgeAuthChallenge');

    $result = $this->newResultFromChallengeResponse(
      $config,
      $viewer,
      $request,
      $challenges);

    if (!$this->isAuthResult($result)) {
      throw new Exception(
        pht(
          'Expected "newResultFromChallengeResponse()" to return an object '.
          'of class "%s"; got something else (in "%s").',
          'PhorgeAuthFactorResult',
          get_class($this)));
    }

    return $result;
  }

  abstract protected function newResultFromChallengeResponse(
    PhorgeAuthFactorConfig $config,
    PhorgeUser $viewer,
    AphrontRequest $request,
    array $challenges);

  final protected function newAutomaticControl(
    PhorgeAuthFactorResult $result) {

    $is_error = $result->getIsError();
    if ($is_error) {
      return $this->newErrorControl($result);
    }

    $is_continue = $result->getIsContinue();
    if ($is_continue) {
      return $this->newContinueControl($result);
    }

    $is_answered = (bool)$result->getAnsweredChallenge();
    if ($is_answered) {
      return $this->newAnsweredControl($result);
    }

    $is_wait = $result->getIsWait();
    if ($is_wait) {
      return $this->newWaitControl($result);
    }

    return null;
  }

  private function newWaitControl(
    PhorgeAuthFactorResult $result) {

    $error = $result->getErrorMessage();

    $icon = $result->getIcon();
    if (!$icon) {
      $icon = id(new PHUIIconView())
        ->setIcon('fa-clock-o', 'red');
    }

    return id(new PHUIFormTimerControl())
      ->setIcon($icon)
      ->appendChild($error)
      ->setError(pht('Wait'));
  }

  private function newAnsweredControl(
    PhorgeAuthFactorResult $result) {

    $icon = $result->getIcon();
    if (!$icon) {
      $icon = id(new PHUIIconView())
        ->setIcon('fa-check-circle-o', 'green');
    }

    return id(new PHUIFormTimerControl())
      ->setIcon($icon)
      ->appendChild(
        pht('You responded to this challenge correctly.'));
  }

  private function newErrorControl(
    PhorgeAuthFactorResult $result) {

    $error = $result->getErrorMessage();

    $icon = $result->getIcon();
    if (!$icon) {
      $icon = id(new PHUIIconView())
        ->setIcon('fa-times', 'red');
    }

    return id(new PHUIFormTimerControl())
      ->setIcon($icon)
      ->appendChild($error)
      ->setError(pht('Error'));
  }

  private function newContinueControl(
    PhorgeAuthFactorResult $result) {

    $error = $result->getErrorMessage();

    $icon = $result->getIcon();
    if (!$icon) {
      $icon = id(new PHUIIconView())
        ->setIcon('fa-commenting', 'green');
    }

    $control = id(new PHUIFormTimerControl())
      ->setIcon($icon)
      ->appendChild($error);

    $status_challenge = $result->getStatusChallenge();
    if ($status_challenge) {
      $id = $status_challenge->getID();
      $uri = "/auth/mfa/challenge/status/{$id}/";
      $control->setUpdateURI($uri);
    }

    return $control;
  }



/* -(  Synchronizing New Factors  )------------------------------------------ */


  final protected function loadMFASyncToken(
    PhorgeAuthFactorProvider $provider,
    AphrontRequest $request,
    AphrontFormView $form,
    PhorgeUser $user) {

    // If the form included a synchronization key, load the corresponding
    // token. The user must synchronize to a key we generated because this
    // raises the barrier to theoretical attacks where an attacker might
    // provide a known key for factors like TOTP.

    // (We store and verify the hash of the key, not the key itself, to limit
    // how useful the data in the table is to an attacker.)

    $sync_type = PhorgeAuthMFASyncTemporaryTokenType::TOKENTYPE;
    $sync_token = null;

    $sync_key = $request->getStr($this->getMFASyncTokenFormKey());
    if (strlen($sync_key)) {
      $sync_key_digest = PhorgeHash::digestWithNamedKey(
        $sync_key,
        PhorgeAuthMFASyncTemporaryTokenType::DIGEST_KEY);

      $sync_token = id(new PhorgeAuthTemporaryTokenQuery())
        ->setViewer($user)
        ->withTokenResources(array($user->getPHID()))
        ->withTokenTypes(array($sync_type))
        ->withExpired(false)
        ->withTokenCodes(array($sync_key_digest))
        ->executeOne();
    }

    if (!$sync_token) {

      // Don't generate a new sync token if there are too many outstanding
      // tokens already. This is mostly relevant for push factors like SMS,
      // where generating a token has the side effect of sending a user a
      // message.

      $outstanding_limit = 10;
      $outstanding_tokens = id(new PhorgeAuthTemporaryTokenQuery())
        ->setViewer($user)
        ->withTokenResources(array($user->getPHID()))
        ->withTokenTypes(array($sync_type))
        ->withExpired(false)
        ->execute();
      if (count($outstanding_tokens) > $outstanding_limit) {
        throw new Exception(
          pht(
            'Your account has too many outstanding, incomplete MFA '.
            'synchronization attempts. Wait an hour and try again.'));
      }

      $now = PhorgeTime::getNow();

      $sync_key = Filesystem::readRandomCharacters(32);
      $sync_key_digest = PhorgeHash::digestWithNamedKey(
        $sync_key,
        PhorgeAuthMFASyncTemporaryTokenType::DIGEST_KEY);
      $sync_ttl = $this->getMFASyncTokenTTL();

      $sync_token = id(new PhorgeAuthTemporaryToken())
        ->setIsNewTemporaryToken(true)
        ->setTokenResource($user->getPHID())
        ->setTokenType($sync_type)
        ->setTokenCode($sync_key_digest)
        ->setTokenExpires($now + $sync_ttl);

      $properties = $this->newMFASyncTokenProperties(
        $provider,
        $user);

      if ($this->isAuthResult($properties)) {
        return $properties;
      }

      foreach ($properties as $key => $value) {
        $sync_token->setTemporaryTokenProperty($key, $value);
      }

      $sync_token->save();
    }

    $form->addHiddenInput($this->getMFASyncTokenFormKey(), $sync_key);

    return $sync_token;
  }

  protected function newMFASyncTokenProperties(
    PhorgeAuthFactorProvider $provider,
    PhorgeUser $user) {
    return array();
  }

  private function getMFASyncTokenFormKey() {
    return 'sync.key';
  }

  private function getMFASyncTokenTTL() {
    return phutil_units('1 hour in seconds');
  }

  final protected function getChallengeForCurrentContext(
    PhorgeAuthFactorConfig $config,
    PhorgeUser $viewer,
    array $challenges) {

    $session_phid = $viewer->getSession()->getPHID();
    $engine = $config->getSessionEngine();
    $workflow_key = $engine->getWorkflowKey();

    foreach ($challenges as $challenge) {
      if ($challenge->getSessionPHID() !== $session_phid) {
        continue;
      }

      if ($challenge->getWorkflowKey() !== $workflow_key) {
        continue;
      }

      if ($challenge->getIsCompleted()) {
        continue;
      }

      if ($challenge->getIsReusedChallenge()) {
        continue;
      }

      return $challenge;
    }

    return null;
  }


  /**
   * @phutil-external-symbol class QRcode
   */
  final protected function newQRCode($uri) {
    $root = dirname(phutil_get_library_root('phorge'));
    require_once $root.'/externals/phpqrcode/phpqrcode.php';

    $lines = QRcode::text($uri);

    $total_width = 240;
    $cell_size = floor($total_width / count($lines));

    $rows = array();
    foreach ($lines as $line) {
      $cells = array();
      for ($ii = 0; $ii < strlen($line); $ii++) {
        if ($line[$ii] == '1') {
          $color = '#000';
        } else {
          $color = '#fff';
        }

        $cells[] = phutil_tag(
          'td',
          array(
            'width' => $cell_size,
            'height' => $cell_size,
            'style' => 'background: '.$color,
          ),
          '');
      }
      $rows[] = phutil_tag('tr', array(), $cells);
    }

    return phutil_tag(
      'table',
      array(
        'style' => 'margin: 24px auto;',
      ),
      $rows);
  }

  final protected function getInstallDisplayName() {
    $uri = PhorgeEnv::getURI('/');
    $uri = new PhutilURI($uri);
    return $uri->getDomain();
  }

  final protected function getChallengeResponseParameterName(
    PhorgeAuthFactorConfig $config) {
    return $this->getParameterName($config, 'mfa.response');
  }

  final protected function getChallengeResponseFromRequest(
    PhorgeAuthFactorConfig $config,
    AphrontRequest $request) {

    $name = $this->getChallengeResponseParameterName($config);

    $value = $request->getStr($name);
    $value = (string)$value;
    $value = trim($value);

    return $value;
  }

  final protected function hasCSRF(PhorgeAuthFactorConfig $config) {
    $engine = $config->getSessionEngine();
    $request = $engine->getRequest();

    if (!$request->isHTTPPost()) {
      return false;
    }

    return $request->validateCSRF();
  }

  final protected function loadConfigurationsForProvider(
    PhorgeAuthFactorProvider $provider,
    PhorgeUser $user) {

    return id(new PhorgeAuthFactorConfigQuery())
      ->setViewer($user)
      ->withUserPHIDs(array($user->getPHID()))
      ->withFactorProviderPHIDs(array($provider->getPHID()))
      ->execute();
  }

  final protected function isAuthResult($object) {
    return ($object instanceof PhorgeAuthFactorResult);
  }

}
