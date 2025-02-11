<?php

final class DifferentialRevertPlanField
  extends DifferentialStoredCustomField {

  public function getFieldKey() {
    return 'phorge:revert-plan';
  }

  public function getFieldKeyForConduit() {
    return 'revertPlan';
  }

  public function getFieldName() {
    return pht('Revert Plan');
  }

  public function getFieldDescription() {
    return pht('Instructions for reverting/undoing this change.');
  }

  public function shouldDisableByDefault() {
    return true;
  }

  public function shouldAppearInPropertyView() {
    return true;
  }

  public function renderPropertyViewLabel() {
    return $this->getFieldName();
  }

  public function getStyleForPropertyView() {
    return 'block';
  }

  public function getIconForPropertyView() {
    return PHUIPropertyListView::ICON_TESTPLAN;
  }

  public function renderPropertyViewValue(array $handles) {
    if (!strlen($this->getValue())) {
      return null;
    }

    return new PHUIRemarkupView($this->getViewer(), $this->getValue());
  }

  public function shouldAppearInGlobalSearch() {
    return true;
  }

  public function updateAbstractDocument(
    PhorgeSearchAbstractDocument $document) {
    if (strlen($this->getValue())) {
      $document->addField('rvrt', $this->getValue());
    }
  }

  public function shouldAppearInEditView() {
    return true;
  }

  public function shouldAppearInApplicationTransactions() {
    return true;
  }

  public function getOldValueForApplicationTransactions() {
    return $this->getValue();
  }

  public function getNewValueForApplicationTransactions() {
    return $this->getValue();
  }

  public function readValueFromRequest(AphrontRequest $request) {
    $this->setValue($request->getStr($this->getFieldKey()));
  }

  public function renderEditControl(array $handles) {
    return id(new PhorgeRemarkupControl())
      ->setUser($this->getViewer())
      ->setName($this->getFieldKey())
      ->setValue($this->getValue())
      ->setLabel($this->getFieldName());
  }

  public function getApplicationTransactionTitle(
    PhorgeApplicationTransaction $xaction) {
    $author_phid = $xaction->getAuthorPHID();
    $old = $xaction->getOldValue();
    $new = $xaction->getNewValue();

    return pht(
      '%s updated the revert plan for this revision.',
      $xaction->renderHandleLink($author_phid));
  }

  public function getApplicationTransactionTitleForFeed(
    PhorgeApplicationTransaction $xaction) {

    $object_phid = $xaction->getObjectPHID();
    $author_phid = $xaction->getAuthorPHID();
    $old = $xaction->getOldValue();
    $new = $xaction->getNewValue();

    return pht(
      '%s updated the revert plan for %s.',
      $xaction->renderHandleLink($author_phid),
      $xaction->renderHandleLink($object_phid));
  }

  public function getApplicationTransactionHasChangeDetails(
    PhorgeApplicationTransaction $xaction) {
    return true;
  }

  public function getApplicationTransactionChangeDetails(
    PhorgeApplicationTransaction $xaction,
    PhorgeUser $viewer) {
    return $xaction->renderTextCorpusChangeDetails(
      $viewer,
      $xaction->getOldValue(),
      $xaction->getNewValue());
  }

  public function getApplicationTransactionRemarkupBlocks(
    PhorgeApplicationTransaction $xaction) {
    return array($xaction->getNewValue());
  }

  public function shouldAppearInConduitDictionary() {
    return true;
  }

  public function shouldAppearInConduitTransactions() {
    return true;
  }

  protected function newConduitEditParameterType() {
    return new ConduitStringParameterType();
  }

}
