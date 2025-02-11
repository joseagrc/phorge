<?php

final class PhorgeCountdownSearchEngine
  extends PhorgeApplicationSearchEngine {

  public function getResultTypeDescription() {
    return pht('Countdowns');
  }

  public function getApplicationClassName() {
    return 'PhorgeCountdownApplication';
  }

  public function newQuery() {
    return new PhorgeCountdownQuery();
  }

  protected function buildQueryFromParameters(array $map) {
    $query = $this->newQuery();

    if ($map['authorPHIDs']) {
      $query->withAuthorPHIDs($map['authorPHIDs']);
    }

    if ($map['upcoming'] && $map['upcoming'][0] == 'upcoming') {
      $query->withUpcoming();
    }

    return $query;
  }

  protected function buildCustomSearchFields() {
    return array(
      id(new PhorgeUsersSearchField())
        ->setLabel(pht('Authors'))
        ->setKey('authorPHIDs')
        ->setAliases(array('author', 'authors')),
      id(new PhorgeSearchCheckboxesField())
        ->setKey('upcoming')
        ->setOptions(
          array(
            'upcoming' => pht('Show only upcoming countdowns.'),
          )),
    );
  }

  protected function getURI($path) {
    return '/countdown/'.$path;
  }

  protected function getBuiltinQueryNames() {
    $names = array(
      'upcoming' => pht('Upcoming'),
      'all' => pht('All'),
    );

    if ($this->requireViewer()->getPHID()) {
      $names['authored'] = pht('Authored');
    }

    return $names;
  }

  public function buildSavedQueryFromBuiltin($query_key) {
    $query = $this->newSavedQuery();
    $query->setQueryKey($query_key);

    switch ($query_key) {
      case 'all':
        return $query;
      case 'authored':
        return $query->setParameter(
          'authorPHIDs',
          array($this->requireViewer()->getPHID()));
      case 'upcoming':
        return $query->setParameter('upcoming', array('upcoming'));
    }

    return parent::buildSavedQueryFromBuiltin($query_key);
  }

  protected function getRequiredHandlePHIDsForResultList(
    array $countdowns,
    PhorgeSavedQuery $query) {

    return mpull($countdowns, 'getAuthorPHID');
  }

  protected function renderResultList(
    array $countdowns,
    PhorgeSavedQuery $query,
    array $handles) {

    assert_instances_of($countdowns, 'PhorgeCountdown');

    $viewer = $this->requireViewer();

    $list = new PHUIObjectItemListView();
    $list->setUser($viewer);
    foreach ($countdowns as $countdown) {
      $id = $countdown->getID();
      $ended = false;
      $epoch = $countdown->getEpoch();
      if ($epoch <= PhorgeTime::getNow()) {
        $ended = true;
      }

      $item = id(new PHUIObjectItemView())
        ->setUser($viewer)
        ->setObject($countdown)
        ->setObjectName($countdown->getMonogram())
        ->setHeader($countdown->getTitle())
        ->setHref($countdown->getURI())
        ->addByline(
          pht(
            'Created by %s',
            $handles[$countdown->getAuthorPHID()]->renderLink()));

      if ($ended) {
        $item->addAttribute(
          pht('Launched on %s', phorge_datetime($epoch, $viewer)));
        $item->setDisabled(true);
      } else {
        $time_left = ($epoch - PhorgeTime::getNow());
        $num = round($time_left / (60 * 60 * 24));
        $noun = pht('Days');
        if ($num < 1) {
          $num = round($time_left / (60 * 60), 1);
          $noun = pht('Hours');
        }
        $item->setCountdown($num, $noun);
        $item->addAttribute(
          phorge_datetime($epoch, $viewer));
      }

      $list->addItem($item);
    }

    $result = new PhorgeApplicationSearchResultView();
    $result->setObjectList($list);
    $result->setNoDataString(pht('No countdowns found.'));

    return $result;
  }

  protected function getNewUserBody() {
    $create_button = id(new PHUIButtonView())
      ->setTag('a')
      ->setText(pht('Create a Countdown'))
      ->setHref('/countdown/edit/')
      ->setColor(PHUIButtonView::GREEN);

    $icon = $this->getApplication()->getIcon();
    $app_name =  $this->getApplication()->getName();
    $view = id(new PHUIBigInfoView())
      ->setIcon($icon)
      ->setTitle(pht('Welcome to %s', $app_name))
      ->setDescription(
        pht('Keep track of upcoming launch dates with '.
            'embeddable counters.'))
      ->addAction($create_button);

      return $view;
  }

}
