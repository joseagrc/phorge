<?php

final class PhorgePeopleProfileEditController
  extends PhorgePeopleProfileController {

  public function handleRequest(AphrontRequest $request) {
    $viewer = $this->getViewer();
    $id = $request->getURIData('id');

    $user = id(new PhorgePeopleQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->needProfileImage(true)
      ->requireCapabilities(
        array(
          PhorgePolicyCapability::CAN_VIEW,
          PhorgePolicyCapability::CAN_EDIT,
        ))
      ->executeOne();
    if (!$user) {
      return new Aphront404Response();
    }

    $this->setUser($user);

    $done_uri = $this->getApplicationURI("manage/{$id}/");

    $field_list = PhorgeCustomField::getObjectFields(
      $user,
      PhorgeCustomField::ROLE_EDIT);
    $field_list
      ->setViewer($viewer)
      ->readFieldsFromStorage($user);

    $validation_exception = null;
    if ($request->isFormPost()) {
      $xactions = $field_list->buildFieldTransactionsFromRequest(
        new PhorgeUserTransaction(),
        $request);

      $editor = id(new PhorgeUserTransactionEditor())
        ->setActor($viewer)
        ->setContentSourceFromRequest($request)
        ->setContinueOnNoEffect(true);

      try {
        $editor->applyTransactions($user, $xactions);
        return id(new AphrontRedirectResponse())->setURI($done_uri);
      } catch (PhorgeApplicationTransactionValidationException $ex) {
        $validation_exception = $ex;
      }
    }

    $title = pht('Edit Profile');

    $form = id(new AphrontFormView())
      ->setUser($viewer);

    $field_list->appendFieldsToForm($form);
    $form
      ->appendChild(
        id(new AphrontFormSubmitControl())
          ->addCancelButton($done_uri)
          ->setValue(pht('Save Profile')));

    $allow_public = PhorgeEnv::getEnvConfig('policy.allow-public');
    $note = null;
    if ($allow_public) {
      $note = id(new PHUIInfoView())
        ->setSeverity(PHUIInfoView::SEVERITY_WARNING)
        ->appendChild(pht(
          'Information on user profiles on this install is publicly '.
          'visible.'));
    }

    $form_box = id(new PHUIObjectBoxView())
      ->setHeaderText(pht('Profile'))
      ->setValidationException($validation_exception)
      ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
      ->setForm($form);

    $crumbs = $this->buildApplicationCrumbs();
    $crumbs->addTextCrumb(pht('Edit Profile'));
    $crumbs->setBorder(true);

    $nav = $this->newNavigation(
      $user,
      PhorgePeopleProfileMenuEngine::ITEM_MANAGE);

    $header = id(new PHUIHeaderView())
      ->setHeader(pht('Edit Profile: %s', $user->getFullName()))
      ->setHeaderIcon('fa-pencil');

    $view = id(new PHUITwoColumnView())
      ->setHeader($header)
      ->setFooter(array(
        $note,
        $form_box,
      ));

    return $this->newPage()
      ->setTitle($title)
      ->setCrumbs($crumbs)
      ->setNavigation($nav)
      ->appendChild($view);
  }
}
