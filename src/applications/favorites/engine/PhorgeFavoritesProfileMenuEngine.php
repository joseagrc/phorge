<?php

final class PhorgeFavoritesProfileMenuEngine
  extends PhorgeProfileMenuEngine {

  protected function isMenuEngineConfigurable() {
    return true;
  }

  public function getItemURI($path) {
    return "/favorites/menu/{$path}";
  }

  protected function getBuiltinProfileItems($object) {
    $items = array();
    $viewer = $this->getViewer();

    $engines = PhorgeEditEngine::getAllEditEngines();
    $engines = msortv($engines, 'getQuickCreateOrderVector');

    foreach ($engines as $engine) {
      foreach ($engine->getDefaultQuickCreateFormKeys() as $form_key) {
        $form_hash = PhorgeHash::digestForIndex($form_key);
        $builtin_key = "editengine.form({$form_hash})";

        $properties = array(
          'name' => null,
          'formKey' => $form_key,
        );

        $items[] = $this->newItem()
          ->setBuiltinKey($builtin_key)
          ->setMenuItemKey(PhorgeEditEngineProfileMenuItem::MENUITEMKEY)
          ->setMenuItemProperties($properties);
      }
    }

    $items[] = $this->newDividerItem('tail');
    $items[] = $this->newManageItem()
      ->setMenuItemProperty('name', pht('Edit Favorites'));

    return $items;
  }

}
