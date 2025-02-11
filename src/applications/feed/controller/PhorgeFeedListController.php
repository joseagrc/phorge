<?php

final class PhorgeFeedListController
  extends PhorgeFeedController {

  public function shouldAllowPublic() {
    return true;
  }

  public function handleRequest(AphrontRequest $request) {
    $navigation = array();

    $navigation[] = id(new PHUIListItemView())
      ->setType(PHUIListItemView::TYPE_LABEL)
      ->setName(pht('Transactions'));

    $navigation[] = id(new PHUIListItemView())
      ->setName(pht('Transaction Logs'))
      ->setHref($this->getApplicationURI('transactions/'));

    return id(new PhorgeFeedSearchEngine())
      ->setController($this)
      ->setNavigationItems($navigation)
      ->buildResponse();
  }

}
