<?php

final class PhorgeIDsSearchEngineExtension
  extends PhorgeSearchEngineExtension {

  const EXTENSIONKEY = 'ids';

  public function isExtensionEnabled() {
    return true;
  }

  public function getExtensionName() {
    return pht('Supports ID/PHID Queries');
  }

  public function getExtensionOrder() {
    return 1000;
  }

  public function supportsObject($object) {
    return true;
  }

  public function getSearchFields($object) {
    return array(
      id(new PhorgeIDsSearchField())
        ->setKey('ids')
        ->setLabel(pht('IDs'))
        ->setDescription(
          pht('Search for objects with specific IDs.')),
      id(new PhorgePHIDsSearchField())
        ->setKey('phids')
        ->setLabel(pht('PHIDs'))
        ->setDescription(
          pht('Search for objects with specific PHIDs.')),
    );
  }

  public function applyConstraintsToQuery(
    $object,
    $query,
    PhorgeSavedQuery $saved,
    array $map) {

    if ($map['ids']) {
      $query->withIDs($map['ids']);
    }

    if ($map['phids']) {
      $query->withPHIDs($map['phids']);
    }

  }

}
