<?php

final class PhorgeSettingsEmailPanelGroup
  extends PhorgeSettingsPanelGroup {

  const PANELGROUPKEY = 'email';

  public function getPanelGroupName() {
    return pht('Email');
  }

  protected function getPanelGroupOrder() {
    return 500;
  }

}
