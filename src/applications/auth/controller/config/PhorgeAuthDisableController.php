<?php

final class PhorgeAuthDisableController
  extends PhorgeAuthProviderConfigController {

  public function handleRequest(AphrontRequest $request) {
    $this->requireApplicationCapability(
      AuthManageProvidersCapability::CAPABILITY);

    $viewer = $this->getViewer();
    $config_id = $request->getURIData('id');
    $action = $request->getURIData('action');

    $config = id(new PhorgeAuthProviderConfigQuery())
      ->setViewer($viewer)
      ->requireCapabilities(
        array(
          PhorgePolicyCapability::CAN_VIEW,
          PhorgePolicyCapability::CAN_EDIT,
        ))
      ->withIDs(array($config_id))
      ->executeOne();
    if (!$config) {
      return new Aphront404Response();
    }

    $is_enable = ($action === 'enable');
    $done_uri = $config->getURI();

    if ($request->isDialogFormPost()) {
      $xactions = array();

      $xactions[] = id(new PhorgeAuthProviderConfigTransaction())
        ->setTransactionType(
          PhorgeAuthProviderConfigTransaction::TYPE_ENABLE)
        ->setNewValue((int)$is_enable);

      $editor = id(new PhorgeAuthProviderConfigEditor())
        ->setActor($viewer)
        ->setContentSourceFromRequest($request)
        ->setContinueOnNoEffect(true)
        ->applyTransactions($config, $xactions);

      return id(new AphrontRedirectResponse())->setURI($done_uri);
    }

    if ($is_enable) {
      $title = pht('Enable Provider?');
      if ($config->getShouldAllowRegistration()) {
        $body = pht(
          'Do you want to enable this provider? Users will be able to use '.
          'their existing external accounts to register new accounts and '.
          'log in using linked accounts.');
      } else {
        $body = pht(
          'Do you want to enable this provider? Users will be able to log '.
          'in using linked accounts.');
      }
      $button = pht('Enable Provider');
    } else {
      // TODO: We could tailor this a bit more. In particular, we could
      // check if this is the last provider and either prevent if from
      // being disabled or force the user through like 35 prompts. We could
      // also check if it's the last provider linked to the acting user's
      // account and pop a warning like "YOU WILL NO LONGER BE ABLE TO LOGIN
      // YOU GOOF, YOU PROBABLY DO NOT MEAN TO DO THIS". None of this is
      // critical and we can wait to see how users manage to shoot themselves
      // in the feet.

      // `bin/auth` can recover from these types of mistakes.

      $title = pht('Disable Provider?');
      $body = pht(
        'Do you want to disable this provider? Users will not be able to '.
        'register or log in using linked accounts. If there are any users '.
        'without other linked authentication mechanisms, they will no longer '.
        'be able to log in. If you disable all providers, no one will be '.
        'able to log in.');
      $button = pht('Disable Provider');
    }

    return $this->newDialog()
      ->setTitle($title)
      ->appendChild($body)
      ->addCancelButton($done_uri)
      ->addSubmitButton($button);
  }

}
