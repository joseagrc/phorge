<?php

final class PhorgeFerretSearchEngineExtension
  extends PhorgeSearchEngineExtension {

  const EXTENSIONKEY = 'ferret';

  public function isExtensionEnabled() {
    return true;
  }

  public function getExtensionName() {
    return pht('Fulltext Search');
  }

  public function getExtensionOrder() {
    return 1000;
  }

  public function supportsObject($object) {
    return ($object instanceof PhorgeFerretInterface);
  }

  public function applyConstraintsToQuery(
    $object,
    $query,
    PhorgeSavedQuery $saved,
    array $map) {

    if (!strlen($map['query'])) {
      return;
    }

    $engine = $object->newFerretEngine();

    $raw_query = $map['query'];

    $compiler = id(new PhutilSearchQueryCompiler())
      ->setEnableFunctions(true);

    $raw_tokens = $compiler->newTokens($raw_query);

    $fulltext_tokens = array();
    foreach ($raw_tokens as $raw_token) {
      $fulltext_token = id(new PhorgeFulltextToken())
        ->setToken($raw_token);

      $fulltext_tokens[] = $fulltext_token;
    }

    $query->withFerretConstraint($engine, $fulltext_tokens);
  }

  public function getSearchFields($object) {
    $fields = array();

    $fields[] = id(new PhorgeSearchTextField())
      ->setKey('query')
      ->setLabel(pht('Query'))
      ->setDescription(
        pht(
          'Find objects matching a fulltext search query. See '.
          '"Search User Guide" in the documentation for details.'));

    return $fields;
  }

  public function getSearchAttachments($object) {
    return array();
  }


}
