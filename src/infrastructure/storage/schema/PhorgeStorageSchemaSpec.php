<?php

final class PhorgeStorageSchemaSpec
  extends PhorgeConfigSchemaSpec {

  public function buildSchemata() {
    $this->buildRawSchema(
      'meta_data',
      'patch_status',
      array(
        'patch' => 'text128',
        'applied' => 'uint32',
        'duration' => 'uint64?',
      ),
      array(
        'PRIMARY' => array(
          'columns' => array('patch'),
          'unique' => true,
        ),
      ));

    $this->buildRawSchema(
      'meta_data',
      PhorgeStorageManagementAPI::TABLE_HOSTSTATE,
      array(
        'stateKey' => 'text128',
        'stateValue' => 'text',
      ),
      array(
        'PRIMARY' => array(
          'columns' => array('stateKey'),
          'unique' => true,
        ),
      ));
  }

}
