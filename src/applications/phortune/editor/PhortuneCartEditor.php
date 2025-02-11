<?php

final class PhortuneCartEditor
  extends PhorgeApplicationTransactionEditor {

  private $invoiceIssues;

  public function setInvoiceIssues(array $invoice_issues) {
    $this->invoiceIssues = $invoice_issues;
    return $this;
  }

  public function getInvoiceIssues() {
    return $this->invoiceIssues;
  }

  public function isInvoice() {
    return (bool)$this->invoiceIssues;
  }

  public function getEditorApplicationClass() {
    return 'PhorgePhortuneApplication';
  }

  public function getEditorObjectsDescription() {
    return pht('Phortune Carts');
  }

  public function getTransactionTypes() {
    $types = parent::getTransactionTypes();

    $types[] = PhortuneCartTransaction::TYPE_CREATED;
    $types[] = PhortuneCartTransaction::TYPE_PURCHASED;
    $types[] = PhortuneCartTransaction::TYPE_HOLD;
    $types[] = PhortuneCartTransaction::TYPE_REVIEW;
    $types[] = PhortuneCartTransaction::TYPE_CANCEL;
    $types[] = PhortuneCartTransaction::TYPE_REFUND;
    $types[] = PhortuneCartTransaction::TYPE_INVOICED;

    return $types;
  }

  protected function getCustomTransactionOldValue(
    PhorgeLiskDAO $object,
    PhorgeApplicationTransaction $xaction) {

    switch ($xaction->getTransactionType()) {
      case PhortuneCartTransaction::TYPE_CREATED:
      case PhortuneCartTransaction::TYPE_PURCHASED:
      case PhortuneCartTransaction::TYPE_HOLD:
      case PhortuneCartTransaction::TYPE_REVIEW:
      case PhortuneCartTransaction::TYPE_CANCEL:
      case PhortuneCartTransaction::TYPE_REFUND:
      case PhortuneCartTransaction::TYPE_INVOICED:
        return null;
    }

    return parent::getCustomTransactionOldValue($object, $xaction);
  }

  protected function getCustomTransactionNewValue(
    PhorgeLiskDAO $object,
    PhorgeApplicationTransaction $xaction) {

    switch ($xaction->getTransactionType()) {
      case PhortuneCartTransaction::TYPE_CREATED:
      case PhortuneCartTransaction::TYPE_PURCHASED:
      case PhortuneCartTransaction::TYPE_HOLD:
      case PhortuneCartTransaction::TYPE_REVIEW:
      case PhortuneCartTransaction::TYPE_CANCEL:
      case PhortuneCartTransaction::TYPE_REFUND:
      case PhortuneCartTransaction::TYPE_INVOICED:
        return $xaction->getNewValue();
    }

    return parent::getCustomTransactionNewValue($object, $xaction);
  }

  protected function applyCustomInternalTransaction(
    PhorgeLiskDAO $object,
    PhorgeApplicationTransaction $xaction) {

    switch ($xaction->getTransactionType()) {
      case PhortuneCartTransaction::TYPE_CREATED:
      case PhortuneCartTransaction::TYPE_PURCHASED:
      case PhortuneCartTransaction::TYPE_HOLD:
      case PhortuneCartTransaction::TYPE_REVIEW:
      case PhortuneCartTransaction::TYPE_CANCEL:
      case PhortuneCartTransaction::TYPE_REFUND:
      case PhortuneCartTransaction::TYPE_INVOICED:
        return;
    }

    return parent::applyCustomInternalTransaction($object, $xaction);
  }

  protected function applyCustomExternalTransaction(
    PhorgeLiskDAO $object,
    PhorgeApplicationTransaction $xaction) {

    switch ($xaction->getTransactionType()) {
      case PhortuneCartTransaction::TYPE_CREATED:
      case PhortuneCartTransaction::TYPE_PURCHASED:
      case PhortuneCartTransaction::TYPE_HOLD:
      case PhortuneCartTransaction::TYPE_REVIEW:
      case PhortuneCartTransaction::TYPE_CANCEL:
      case PhortuneCartTransaction::TYPE_REFUND:
      case PhortuneCartTransaction::TYPE_INVOICED:
        return;
    }

    return parent::applyCustomExternalTransaction($object, $xaction);
  }

  protected function shouldSendMail(
    PhorgeLiskDAO $object,
    array $xactions) {
    return true;
  }

  protected function buildMailTemplate(PhorgeLiskDAO $object) {
    $id = $object->getID();
    $name = $object->getName();

    return id(new PhorgeMetaMTAMail())
      ->setSubject(pht('Order %d: %s', $id, $name));
  }

  protected function buildMailBody(
    PhorgeLiskDAO $object,
    array $xactions) {

    $body = parent::buildMailBody($object, $xactions);

    if ($this->isInvoice()) {
      $issues = $this->getInvoiceIssues();
      foreach ($issues as $key => $issue) {
        $issues[$key] = '  - '.$issue;
      }
      $issues = implode("\n", $issues);

      $overview = pht(
        "Payment for this invoice could not be processed automatically:\n\n".
        "%s",
        $issues);

      $body->addRemarkupSection(null, $overview);

      $body->addLinkSection(
        pht('PAY NOW'),
        PhorgeEnv::getProductionURI($object->getCheckoutURI()));
    }

    $items = array();
    foreach ($object->getPurchases() as $purchase) {
      $name = $purchase->getFullDisplayName();
      $price = $purchase->getTotalPriceAsCurrency()->formatForDisplay();

      $items[] = "{$name} {$price}";
    }

    $body->addTextSection(pht('ORDER CONTENTS'), implode("\n", $items));

    if ($this->isInvoice()) {
      $subscription = id(new PhortuneSubscriptionQuery())
        ->setViewer($this->requireActor())
        ->withPHIDs(array($object->getSubscriptionPHID()))
        ->executeOne();
      if ($subscription) {
        $body->addLinkSection(
          pht('SUBSCRIPTION'),
          PhorgeEnv::getProductionURI($subscription->getURI()));
      }
    } else {
      $body->addLinkSection(
        pht('ORDER DETAIL'),
        PhorgeEnv::getProductionURI($object->getDetailURI()));
    }

    $account_uri = '/phortune/'.$object->getAccount()->getID().'/';
    $body->addLinkSection(
      pht('ACCOUNT OVERVIEW'),
      PhorgeEnv::getProductionURI($account_uri));

    return $body;
  }

  protected function getMailTo(PhorgeLiskDAO $object) {
    $phids = array();

    // Reload the cart to pull account information, in case we just created the
    // object.
    $cart = id(new PhortuneCartQuery())
      ->setViewer($this->requireActor())
      ->withPHIDs(array($object->getPHID()))
      ->executeOne();

    foreach ($cart->getAccount()->getMemberPHIDs() as $account_member) {
      $phids[] = $account_member;
    }

    return $phids;
  }

  protected function getMailCC(PhorgeLiskDAO $object) {
    return array();
  }

  protected function getMailSubjectPrefix() {
    return '[Phortune]';
  }

  protected function buildReplyHandler(PhorgeLiskDAO $object) {
    return id(new PhortuneCartReplyHandler())
      ->setMailReceiver($object);
  }

  protected function willPublish(PhorgeLiskDAO $object, array $xactions) {
    // We need the purchases in order to build mail.
    return id(new PhortuneCartQuery())
      ->setViewer($this->getActor())
      ->withIDs(array($object->getID()))
      ->needPurchases(true)
      ->executeOne();
  }

  protected function getCustomWorkerState() {
    return array(
      'invoiceIssues' => $this->invoiceIssues,
    );
  }

  protected function loadCustomWorkerState(array $state) {
    $this->invoiceIssues = idx($state, 'invoiceIssues');
    return $this;
  }

  protected function applyFinalEffects(
    PhorgeLiskDAO $object,
    array $xactions) {

    $account = $object->getAccount();
    $merchant = $object->getMerchant();
    $account->writeMerchantEdge($merchant);

    return $xactions;
  }

  protected function newAuxiliaryMail($object, array $xactions) {
    $xviewer = PhorgeUser::getOmnipotentUser();
    $account = $object->getAccount();

    $addresses = id(new PhortuneAccountEmailQuery())
      ->setViewer($xviewer)
      ->withAccountPHIDs(array($account->getPHID()))
      ->withStatuses(
        array(
          PhortuneAccountEmailStatus::STATUS_ACTIVE,
        ))
      ->execute();

    $messages = array();
    foreach ($addresses as $address) {
      $message = $this->newExternalMail($address, $object, $xactions);
      if ($message) {
        $messages[] = $message;
      }
    }

    return $messages;
  }

  private function newExternalMail(
    PhortuneAccountEmail $email,
    PhortuneCart $cart,
    array $xactions) {
    $xviewer = PhorgeUser::getOmnipotentUser();
    $account = $cart->getAccount();

    $id = $cart->getID();
    $name = $cart->getName();

    $origin_user = id(new PhorgePeopleQuery())
      ->setViewer($xviewer)
      ->withPHIDs(array($email->getAuthorPHID()))
      ->executeOne();
    if (!$origin_user) {
      return null;
    }

    if ($this->isInvoice()) {
      $subject = pht('[Invoice #%d] %s', $id, $name);
      $order_header = pht('INVOICE DETAIL');
    } else {
      $subject = pht('[Order #%d] %s', $id, $name);
      $order_header = pht('ORDER DETAIL');
    }

    $body = id(new PhorgeMetaMTAMailBody())
      ->setViewer($xviewer)
      ->setContextObject($cart);

    $origin_username = $origin_user->getUsername();
    $origin_realname = $origin_user->getRealName();
    if (strlen($origin_realname)) {
      $origin_display = pht('%s (%s)', $origin_username, $origin_realname);
    } else {
      $origin_display = pht('%s', $origin_username);
    }

    $body->addRawSection(
      pht(
        'This email address (%s) was added to a payment account (%s) '.
        'by %s.',
        $email->getAddress(),
        $account->getName(),
        $origin_display));

    $body->addLinkSection(
      $order_header,
      PhorgeEnv::getProductionURI($email->getExternalOrderURI($cart)));

    $body->addLinkSection(
      pht('FULL ORDER HISTORY'),
      PhorgeEnv::getProductionURI($email->getExternalURI()));

    $body->addLinkSection(
      pht('UNSUBSCRIBE'),
      PhorgeEnv::getProductionURI($email->getUnsubscribeURI()));

    return id(new PhorgeMetaMTAMail())
      ->setFrom($this->getActingAsPHID())
      ->setSubject($subject)
      ->addRawTos(
        array(
          $email->getAddress(),
        ))
      ->setForceDelivery(true)
      ->setIsBulk(true)
      ->setSensitiveContent(true)
      ->setBody($body->render())
      ->setHTMLBody($body->renderHTML());

  }


}
