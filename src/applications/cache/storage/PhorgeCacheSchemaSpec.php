<?php

final class PhorgeCacheSchemaSpec extends PhorgeConfigSchemaSpec {

  public function buildSchemata() {
    $this->buildRawSchema(
      'cache',
      id(new PhorgeKeyValueDatabaseCache())->getTableName(),
      array(
        'id' => 'auto64',
        'cacheKeyHash' => 'bytes12',
        'cacheKey' => 'text128',
        'cacheFormat' => 'text16',
        'cacheData' => 'bytes',
        'cacheCreated' => 'epoch',
        'cacheExpires' => 'epoch?',
      ),
      array(
        'PRIMARY' => array(
          'columns' => array('id'),
          'unique' => true,
        ),
        'key_cacheKeyHash' => array(
          'columns' => array('cacheKeyHash'),
          'unique' => true,
        ),
        'key_cacheCreated' => array(
          'columns' => array('cacheCreated'),
        ),
        'key_ttl' => array(
          'columns' => array('cacheExpires'),
        ),
      ),
      array(
        'persistence' => PhorgeConfigTableSchema::PERSISTENCE_CACHE,
      ));

  }

}
