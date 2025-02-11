<?php

final class PhorgeSlowvoteCommentController
  extends PhorgeSlowvoteController {

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $id = $request->getURIData('id');

    if (!$request->isFormPost()) {
      return new Aphront400Response();
    }

    $poll = id(new PhorgeSlowvoteQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->executeOne();
    if (!$poll) {
      return new Aphront404Response();
    }

    $is_preview = $request->isPreviewRequest();
    $draft = PhorgeDraft::buildFromRequest($request);

    $view_uri = '/V'.$poll->getID();

    $xactions = array();
    $xactions[] = id(new PhorgeSlowvoteTransaction())
      ->setTransactionType(PhorgeTransactions::TYPE_COMMENT)
      ->attachComment(
        id(new PhorgeSlowvoteTransactionComment())
          ->setContent($request->getStr('comment')));

    $editor = id(new PhorgeSlowvoteEditor())
      ->setActor($viewer)
      ->setContinueOnNoEffect($request->isContinueRequest())
      ->setContentSourceFromRequest($request)
      ->setIsPreview($is_preview);

    try {
      $xactions = $editor->applyTransactions($poll, $xactions);
    } catch (PhorgeApplicationTransactionNoEffectException $ex) {
      return id(new PhorgeApplicationTransactionNoEffectResponse())
        ->setCancelURI($view_uri)
        ->setException($ex);
    }

    if ($draft) {
      $draft->replaceOrDelete();
    }

    if ($request->isAjax() && $is_preview) {
      return id(new PhorgeApplicationTransactionResponse())
        ->setObject($poll)
        ->setViewer($viewer)
        ->setTransactions($xactions)
        ->setIsPreview($is_preview);
    } else {
      return id(new AphrontRedirectResponse())
        ->setURI($view_uri);
    }
  }

}
