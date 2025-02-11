<?php

/**
 * MySQL blob storage engine. This engine is the easiest to set up but doesn't
 * scale very well.
 *
 * It uses the @{class:PhorgeFileStorageBlob} to actually access the
 * underlying database table.
 *
 * @task internal Internals
 */
final class PhorgeMySQLFileStorageEngine
  extends PhorgeFileStorageEngine {


/* -(  Engine Metadata  )---------------------------------------------------- */


  /**
   * For historical reasons, this engine identifies as "blob".
   */
  public function getEngineIdentifier() {
    return 'blob';
  }

  public function getEnginePriority() {
    return 1;
  }

  public function canWriteFiles() {
    return ($this->getFilesizeLimit() > 0);
  }


  public function hasFilesizeLimit() {
    return true;
  }


  public function getFilesizeLimit() {
    return PhorgeEnv::getEnvConfig('storage.mysql-engine.max-size');
  }


/* -(  Managing File Data  )------------------------------------------------- */


  /**
   * Write file data into the big blob store table in MySQL. Returns the row
   * ID as the file data handle.
   */
  public function writeFile($data, array $params) {
    $blob = new PhorgeFileStorageBlob();
    $blob->setData($data);
    $blob->save();

    return $blob->getID();
  }


  /**
   * Load a stored blob from MySQL.
   */
  public function readFile($handle) {
    return $this->loadFromMySQLFileStorage($handle)->getData();
  }


  /**
   * Delete a blob from MySQL.
   */
  public function deleteFile($handle) {
    $this->loadFromMySQLFileStorage($handle)->delete();
  }


/* -(  Internals  )---------------------------------------------------------- */


  /**
   * Load the Lisk object that stores the file data for a handle.
   *
   * @param string  File data handle.
   * @return PhorgeFileStorageBlob Data DAO.
   * @task internal
   */
  private function loadFromMySQLFileStorage($handle) {
    $blob = id(new PhorgeFileStorageBlob())->load($handle);
    if (!$blob) {
      throw new Exception(pht("Unable to load MySQL blob file '%s'!", $handle));
    }
    return $blob;
  }

}
