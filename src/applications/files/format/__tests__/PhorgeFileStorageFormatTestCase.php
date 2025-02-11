<?php

final class PhorgeFileStorageFormatTestCase extends PhorgeTestCase {

  protected function getPhorgeTestCaseConfiguration() {
    return array(
      self::PHORGE_TESTCONFIG_BUILD_STORAGE_FIXTURES => true,
    );
  }

  public function testRot13Storage() {
    $engine = new PhorgeTestStorageEngine();
    $rot13_format = PhorgeFileROT13StorageFormat::FORMATKEY;

    $data = 'The cow jumped over the full moon.';
    $expect = 'Gur pbj whzcrq bire gur shyy zbba.';

    $params = array(
      'name' => 'test.dat',
      'storageEngines' => array(
        $engine,
      ),
      'format' => $rot13_format,
    );

    $file = PhorgeFile::newFromFileData($data, $params);

    // We should have a file stored as rot13, which reads back the input
    // data correctly.
    $this->assertEqual($rot13_format, $file->getStorageFormat());
    $this->assertEqual($data, $file->loadFileData());

    // The actual raw data in the storage engine should be encoded.
    $raw_data = $engine->readFile($file->getStorageHandle());
    $this->assertEqual($expect, $raw_data);

    // If we generate an iterator over a slice of the file, it should return
    // the decrypted file.
    $iterator = $file->getFileDataIterator(4, 14);
    $raw_data = '';
    foreach ($iterator as $data_chunk) {
      $raw_data .= $data_chunk;
    }
    $this->assertEqual('cow jumped', $raw_data);
  }

  public function testAES256Storage() {
    if (!function_exists('openssl_encrypt')) {
      $this->assertSkipped(pht('No OpenSSL extension available.'));
    }

    $engine = new PhorgeTestStorageEngine();

    $key_name = 'test.abcd';
    $key_text = 'abcdefghijklmnopABCDEFGHIJKLMNOP';

    PhorgeKeyring::addKey(
      array(
        'name' => $key_name,
        'type' => 'aes-256-cbc',
        'material.base64' => base64_encode($key_text),
      ));

    $format = id(new PhorgeFileAES256StorageFormat())
      ->selectMasterKey($key_name);

    $data = 'The cow jumped over the full moon.';

    $params = array(
      'name' => 'test.dat',
      'storageEngines' => array(
        $engine,
      ),
      'format' => $format,
    );

    $file = PhorgeFile::newFromFileData($data, $params);

    // We should have a file stored as AES256.
    $format_key = $format->getStorageFormatKey();
    $this->assertEqual($format_key, $file->getStorageFormat());
    $this->assertEqual($data, $file->loadFileData());

    // The actual raw data in the storage engine should be encrypted. We
    // can't really test this, but we can make sure it's not the same as the
    // input data.
    $raw_data = $engine->readFile($file->getStorageHandle());
    $this->assertTrue($data !== $raw_data);

    // If we generate an iterator over a slice of the file, it should return
    // the decrypted file.
    $iterator = $file->getFileDataIterator(4, 14);
    $raw_data = '';
    foreach ($iterator as $data_chunk) {
      $raw_data .= $data_chunk;
    }
    $this->assertEqual('cow jumped', $raw_data);

    $iterator = $file->getFileDataIterator(4, null);
    $raw_data = '';
    foreach ($iterator as $data_chunk) {
      $raw_data .= $data_chunk;
    }
    $this->assertEqual('cow jumped over the full moon.', $raw_data);

  }

  public function testStorageTampering() {
    $engine = new PhorgeTestStorageEngine();

    $good = 'The cow jumped over the full moon.';
    $evil = 'The cow slept quietly, honoring the glorious dictator.';

    $params = array(
      'name' => 'message.txt',
      'storageEngines' => array(
        $engine,
      ),
    );

    // First, write the file normally.
    $file = PhorgeFile::newFromFileData($good, $params);
    $this->assertEqual($good, $file->loadFileData());

    // As an adversary, tamper with the file.
    $engine->tamperWithFile($file->getStorageHandle(), $evil);

    // Attempts to read the file data should now fail the integrity check.
    $caught = null;
    try {
      $file->loadFileData();
    } catch (PhorgeFileIntegrityException $ex) {
      $caught = $ex;
    }

    $this->assertTrue($caught instanceof PhorgeFileIntegrityException);
  }


}
