<?php

final class PhorgeSearchScopeSetting
  extends PhorgeSelectSetting {

  const SETTINGKEY = 'search-scope';

  public function getSettingName() {
    return pht('Search Scope');
  }

  public function getSettingPanelKey() {
    return PhorgeSearchSettingsPanel::PANELKEY;
  }

  public function getSettingDefaultValue() {
    return 'all';
  }

  protected function getControlInstructions() {
    return pht(
      'Choose the default behavior of the global search in the main menu.');
  }

  protected function getSelectOptions() {
    $scopes = PhorgeMainMenuSearchView::getGlobalSearchScopeItems(
      $this->getViewer(),
      new PhorgeSettingsApplication(),
      $only_global = true);

    $scope_map = array();
    foreach ($scopes as $scope) {
      if (!isset($scope['value'])) {
        continue;
      }
      $scope_map[$scope['value']] = $scope['name'];
    }

    return $scope_map;
  }

}
