<?php

final class PhorgeConfigClusterSearchController
  extends PhorgeConfigServicesController {

  public function handleRequest(AphrontRequest $request) {
    $title = pht('Search Servers');
    $doc_href = PhorgeEnv::getDoclink('Cluster: Search');

    $button = id(new PHUIButtonView())
      ->setIcon('fa-book')
      ->setHref($doc_href)
      ->setTag('a')
      ->setText(pht('Documentation'));

    $header = $this->buildHeaderView($title, $button);

    $search_status = $this->buildClusterSearchStatus();

    $crumbs = $this->newCrumbs()
      ->addTextCrumb($title);

    $content = id(new PHUITwoColumnView())
      ->setHeader($header)
      ->setFooter($search_status);

    $nav = $this->newNavigation('search-servers');

    return $this->newPage()
      ->setTitle($title)
      ->setCrumbs($crumbs)
      ->setNavigation($nav)
      ->appendChild($content);
  }

  private function buildClusterSearchStatus() {
    $viewer = $this->getViewer();

    $services = PhorgeSearchService::getAllServices();
    Javelin::initBehavior('phorge-tooltips');

    $view = array();
    foreach ($services as $service) {
      $view[] = $this->renderStatusView($service);
    }
    return $view;
  }

  private function renderStatusView($service) {
    $head = array_merge(
        array(pht('Type')),
        array_keys($service->getStatusViewColumns()),
        array(pht('Status')));

    $rows = array();

    $status_map = PhorgeSearchService::getConnectionStatusMap();
    $stats = false;
    $stats_view = false;

    foreach ($service->getHosts() as $host) {
      try {
        // Default status icon
        //
        // At the moment the default status is shown also when
        // you just use MySQL as search server. So, on MySQL it
        // shows "Unknown" even if probably it should says "Active".
        // If you have time, please improve the MySQL getConnectionStatus()
        // to return something more useful than this default.
        $default_status = array(
          'icon'  => 'fa-question-circle',
          'color' => 'blue',
          'label' => pht('Unknown'),
        );
        $status = $host->getConnectionStatus();
        $status = idx($status_map, $status, $default_status);
      } catch (Exception $ex) {
        $status['icon'] = 'fa-times';
        $status['label'] = pht('Connection Error');
        $status['color'] = 'red';
        $host->didHealthCheck(false);
      }

      if (!$stats_view) {
        try {
          $stats = $host->getEngine()->getIndexStats($host);
          $stats_view = $this->renderIndexStats($stats);
        } catch (Exception $e) {
          $stats_view = false;
        }
      }

      $type_icon = 'fa-search sky';
      $type_tip = $host->getDisplayName();

      $type_icon = id(new PHUIIconView())
        ->setIcon($type_icon);
      $status_view = array(
        id(new PHUIIconView())->setIcon($status['icon'].' '.$status['color']),
        ' ',
        $status['label'],
      );
      $row = array(array($type_icon, ' ', $type_tip));
      $row = array_merge($row, array_values(
        $host->getStatusViewColumns()));
      $row[] = $status_view;
      $rows[] = $row;
    }

    $table = id(new AphrontTableView($rows))
      ->setNoDataString(pht('No search servers are configured.'))
      ->setHeaders($head);

    $view = $this->buildConfigBoxView(pht('Search Servers'), $table);

    $stats = null;
    if ($stats_view->hasAnyProperties()) {
      $stats = $this->buildConfigBoxView(
        pht('%s Stats', $service->getDisplayName()),
        $stats_view);
    }

    return array($stats, $view);
  }

  private function renderIndexStats($stats) {
    $view = id(new PHUIPropertyListView());
    if ($stats !== false) {
      foreach ($stats as $label => $val) {
        $view->addProperty($label, $val);
      }
    }
    return $view;
  }

}
