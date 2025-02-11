<?php

final class PhorgeSubscriptionsMuteController
  extends PhorgeController {

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $phid = $request->getURIData('phid');

    $handle = id(new PhorgeHandleQuery())
      ->setViewer($viewer)
      ->withPHIDs(array($phid))
      ->executeOne();

    $object = id(new PhorgeObjectQuery())
      ->setViewer($viewer)
      ->withPHIDs(array($phid))
      ->executeOne();

    if (!($object instanceof PhorgeSubscribableInterface)) {
      return new Aphront400Response();
    }

    $muted_type = PhorgeMutedByEdgeType::EDGECONST;

    $edge_query = id(new PhorgeEdgeQuery())
      ->withSourcePHIDs(array($object->getPHID()))
      ->withEdgeTypes(array($muted_type))
      ->withDestinationPHIDs(array($viewer->getPHID()));

    $edge_query->execute();

    $is_mute = !$edge_query->getDestinationPHIDs();
    $object_uri = $handle->getURI();

    if ($request->isFormPost()) {
      if ($is_mute) {
        $xaction_value = array(
          '+' => array_fuse(array($viewer->getPHID())),
        );
      } else {
        $xaction_value = array(
          '-' => array_fuse(array($viewer->getPHID())),
        );
      }

      $xaction = id($object->getApplicationTransactionTemplate())
        ->setTransactionType(PhorgeTransactions::TYPE_EDGE)
        ->setMetadataValue('edge:type', $muted_type)
        ->setNewValue($xaction_value);

      $editor = id($object->getApplicationTransactionEditor())
        ->setActor($viewer)
        ->setContinueOnNoEffect(true)
        ->setContinueOnMissingFields(true)
        ->setContentSourceFromRequest($request);

      $editor->applyTransactions($object, array($xaction));

      return id(new AphrontReloadResponse())->setURI($object_uri);
    }

    $dialog = $this->newDialog()
      ->addCancelButton($object_uri);

    if ($is_mute) {
      $dialog
        ->setTitle(pht('Mute Notifications'))
        ->appendParagraph(
          pht(
            'Mute this object? You will no longer receive notifications or '.
            'email about it.'))
        ->addSubmitButton(pht('Mute'));
    } else {
      $dialog
        ->setTitle(pht('Unmute Notifications'))
        ->appendParagraph(
          pht(
            'Unmute this object? You will receive notifications and email '.
            'again.'))
        ->addSubmitButton(pht('Unmute'));
    }

    return $dialog;
  }


}
