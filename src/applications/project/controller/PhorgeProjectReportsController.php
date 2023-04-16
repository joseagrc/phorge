<?php

final class PhorgeProjectReportsController
  extends PhorgeProjectController {

  public function shouldAllowPublic() {
    return true;
  }

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();

    $response = $this->loadProject();
    if ($response) {
      return $response;
    }

    $project = $this->getProject();
    $id = $project->getID();

    $can_edit = PhorgePolicyFilter::hasCapability(
      $viewer,
      $project,
      PhorgePolicyCapability::CAN_EDIT);

    $nav = $this->newNavigation(
      $project,
      PhorgeProject::ITEM_REPORTS);

    $crumbs = $this->buildApplicationCrumbs();
    $crumbs->addTextCrumb(pht('Reports'));
    $crumbs->setBorder(true);

    $chart_panel = id(new PhorgeProjectBurndownChartEngine())
      ->setViewer($viewer)
      ->setProjects(array($project))
      ->buildChartPanel();

    $chart_panel->setName(pht('%s: Burndown', $project->getName()));

    $chart_view = id(new PhorgeDashboardPanelRenderingEngine())
      ->setViewer($viewer)
      ->setPanel($chart_panel)
      ->setParentPanelPHIDs(array())
      ->renderPanel();

    $activity_panel = id(new PhorgeProjectActivityChartEngine())
      ->setViewer($viewer)
      ->setProjects(array($project))
      ->buildChartPanel();

    $activity_panel->setName(pht('%s: Activity', $project->getName()));

    $activity_view = id(new PhorgeDashboardPanelRenderingEngine())
      ->setViewer($viewer)
      ->setPanel($activity_panel)
      ->setParentPanelPHIDs(array())
      ->renderPanel();

    $view = id(new PHUITwoColumnView())
      ->setFooter(
        array(
          $chart_view,
          $activity_view,
        ));

    return $this->newPage()
      ->setNavigation($nav)
      ->setCrumbs($crumbs)
      ->setTitle(array($project->getName(), pht('Reports')))
      ->appendChild($view);
  }

}
