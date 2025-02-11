<?php

final class DifferentialJIRAIssuesField
  extends DifferentialStoredCustomField {

  private $error;

  public function getFieldKey() {
    return 'phorge:jira-issues';
  }

  public function getFieldKeyForConduit() {
    return 'jira.issues';
  }

  public function isFieldEnabled() {
    return (bool)PhorgeJIRAAuthProvider::getJIRAProvider();
  }

  public function canDisableField() {
    return false;
  }

  public function getValueForStorage() {
    return json_encode($this->getValue());
  }

  public function setValueFromStorage($value) {
    try {
      $this->setValue(phutil_json_decode($value));
    } catch (PhutilJSONParserException $ex) {
      $this->setValue(array());
    }
    return $this;
  }

  public function getFieldName() {
    return pht('JIRA Issues');
  }

  public function getFieldDescription() {
    return pht('Lists associated JIRA issues.');
  }

  public function shouldAppearInPropertyView() {
    return true;
  }

  public function renderPropertyViewLabel() {
    return $this->getFieldName();
  }

  public function renderPropertyViewValue(array $handles) {
    $xobjs = $this->loadDoorkeeperExternalObjects($this->getValue());
    if (!$xobjs) {
      return null;
    }

    $links = array();
    foreach ($xobjs as $xobj) {
      $links[] = id(new DoorkeeperTagView())
        ->setExternalObject($xobj);
    }

    return phutil_implode_html(phutil_tag('br'), $links);
  }

  private function buildDoorkeeperRefs($value) {
    $provider = PhorgeJIRAAuthProvider::getJIRAProvider();

    $refs = array();
    if ($value) {
      foreach ($value as $jira_key) {
        $refs[] = id(new DoorkeeperObjectRef())
          ->setApplicationType(DoorkeeperBridgeJIRA::APPTYPE_JIRA)
          ->setApplicationDomain($provider->getProviderDomain())
          ->setObjectType(DoorkeeperBridgeJIRA::OBJTYPE_ISSUE)
          ->setObjectID($jira_key);
      }
    }

    return $refs;
  }

  private function loadDoorkeeperExternalObjects($value) {
    $refs = $this->buildDoorkeeperRefs($value);
    if (!$refs) {
      return array();
    }

    $xobjs = id(new DoorkeeperExternalObjectQuery())
      ->setViewer($this->getViewer())
      ->withObjectKeys(mpull($refs, 'getObjectKey'))
      ->execute();

    return $xobjs;
  }

  public function shouldAppearInEditView() {
    return PhorgeJIRAAuthProvider::getJIRAProvider();
  }

  public function shouldAppearInApplicationTransactions() {
    return PhorgeJIRAAuthProvider::getJIRAProvider();
  }

  public function readValueFromRequest(AphrontRequest $request) {
    $this->setValue($request->getStrList($this->getFieldKey()));
    return $this;
  }

  public function renderEditControl(array $handles) {
    return id(new AphrontFormTextControl())
      ->setLabel(pht('JIRA Issues'))
      ->setCaption(
        pht('Example: %s', phutil_tag('tt', array(), 'JIS-3, JIS-9')))
      ->setName($this->getFieldKey())
      ->setValue(implode(', ', nonempty($this->getValue(), array())))
      ->setError($this->error);
  }

  public function getOldValueForApplicationTransactions() {
    return array_unique(nonempty($this->getValue(), array()));
  }

  public function getNewValueForApplicationTransactions() {
    return array_unique(nonempty($this->getValue(), array()));
  }

  public function validateApplicationTransactions(
    PhorgeApplicationTransactionEditor $editor,
    $type,
    array $xactions) {

    $this->error = null;

    $errors = parent::validateApplicationTransactions(
      $editor,
      $type,
      $xactions);

    $transaction = null;
    foreach ($xactions as $xaction) {
      $old = $xaction->getOldValue();
      $new = $xaction->getNewValue();

      $add = array_diff($new, $old);
      if (!$add) {
        continue;
      }

      // Only check that the actor can see newly added JIRA refs. You're
      // allowed to remove refs or make no-op changes even if you aren't
      // linked to JIRA.

      try {
        $refs = id(new DoorkeeperImportEngine())
          ->setViewer($this->getViewer())
          ->setRefs($this->buildDoorkeeperRefs($add))
          ->setThrowOnMissingLink(true)
          ->execute();
      } catch (DoorkeeperMissingLinkException $ex) {
        $this->error = pht('Not Linked');
        $errors[] = new PhorgeApplicationTransactionValidationError(
          $type,
          pht('Not Linked'),
          pht(
            'You can not add JIRA issues (%s) to this revision because your '.
            '%s account is not linked to a JIRA account.',
            implode(', ', $add),
            PlatformSymbols::getPlatformServerName()),
          $xaction);
        continue;
      }

      $bad = array();
      foreach ($refs as $ref) {
        if (!$ref->getIsVisible()) {
          $bad[] = $ref->getObjectID();
        }
      }

      if ($bad) {
        $bad = implode(', ', $bad);
        $this->error = pht('Invalid');

        $errors[] = new PhorgeApplicationTransactionValidationError(
          $type,
          pht('Invalid'),
          pht(
            'Some JIRA issues could not be loaded. They may not exist, or '.
            'you may not have permission to view them: %s',
            $bad),
          $xaction);
      }
    }

    return $errors;
  }

  public function getApplicationTransactionTitle(
    PhorgeApplicationTransaction $xaction) {

    $old = $xaction->getOldValue();
    if (!is_array($old)) {
      $old = array();
    }

    $new = $xaction->getNewValue();
    if (!is_array($new)) {
      $new = array();
    }

    $add = array_diff($new, $old);
    $rem = array_diff($old, $new);

    $author_phid = $xaction->getAuthorPHID();
    if ($add && $rem) {
      return pht(
        '%s updated JIRA issue(s): added %d %s; removed %d %s.',
        $xaction->renderHandleLink($author_phid),
        phutil_count($add),
        implode(', ', $add),
        phutil_count($rem),
        implode(', ', $rem));
    } else if ($add) {
      return pht(
        '%s added %d JIRA issue(s): %s.',
        $xaction->renderHandleLink($author_phid),
        phutil_count($add),
        implode(', ', $add));
    } else if ($rem) {
      return pht(
        '%s removed %d JIRA issue(s): %s.',
        $xaction->renderHandleLink($author_phid),
        phutil_count($rem),
        implode(', ', $rem));
    }

    return parent::getApplicationTransactionTitle($xaction);
  }

  public function applyApplicationTransactionExternalEffects(
    PhorgeApplicationTransaction $xaction) {

    // Update the CustomField storage.
    parent::applyApplicationTransactionExternalEffects($xaction);

    // Now, synchronize the Doorkeeper edges.
    $revision = $this->getObject();
    $revision_phid = $revision->getPHID();

    $edge_type = PhorgeJiraIssueHasObjectEdgeType::EDGECONST;
    $xobjs = $this->loadDoorkeeperExternalObjects($xaction->getNewValue());
    $edge_dsts = mpull($xobjs, 'getPHID');

    $edges = PhorgeEdgeQuery::loadDestinationPHIDs(
      $revision_phid,
      $edge_type);

    $editor = new PhorgeEdgeEditor();

    foreach (array_diff($edges, $edge_dsts) as $rem_edge) {
      $editor->removeEdge($revision_phid, $edge_type, $rem_edge);
    }

    foreach (array_diff($edge_dsts, $edges) as $add_edge) {
      $editor->addEdge($revision_phid, $edge_type, $add_edge);
    }

    $editor->save();
  }

  public function shouldAppearInConduitDictionary() {
    return true;
  }

  public function shouldAppearInConduitTransactions() {
    return true;
  }

  protected function newConduitEditParameterType() {
    return new ConduitStringListParameterType();
  }

}
