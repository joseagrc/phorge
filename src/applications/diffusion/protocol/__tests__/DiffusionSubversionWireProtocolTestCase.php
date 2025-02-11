<?php

final class DiffusionSubversionWireProtocolTestCase
  extends PhorgeTestCase {

  public function testSubversionWireProtocolParser() {
    $this->assertSameSubversionMessages(
      '( ) ',
      array(
        array(
        ),
      ));

    $this->assertSameSubversionMessages(
      '( duck 5:quack 42 ( item1 item2 ) ) ',
      array(
        array(
          array(
            'type' => 'word',
            'value' => 'duck',
          ),
          array(
            'type' => 'string',
            'value' => 'quack',
          ),
          array(
            'type' => 'number',
            'value' => 42,
          ),
          array(
            'type' => 'list',
            'value' => array(
              array(
                'type' => 'word',
                'value' => 'item1',
              ),
              array(
                'type' => 'word',
                'value' => 'item2',
              ),
            ),
          ),
        ),
      ));

    $this->assertSameSubversionMessages(
      '( msg1 ) ( msg2 ) ',
      array(
        array(
          array(
            'type' => 'word',
            'value' => 'msg1',
          ),
        ),
        array(
          array(
            'type' => 'word',
            'value' => 'msg2',
          ),
        ),
      ));

    // This is testing that multiple spaces are parsed correctly. See T13140
    // for discussion.
    $this->assertSameSubversionMessages(
      '( get-file true  false ) ',
      //               ^-- Note extra space!
      array(
        array(
          array(
            'type' => 'word',
            'value' => 'get-file',
          ),
          array(
            'type' => 'word',
            'value' => 'true',
          ),
          array(
            'type' => 'word',
            'value' => 'false',
          ),
        ),
      ),
      '( get-file true false ) ');

    $this->assertSameSubversionMessages(
      '( duck 5:quack    moo  ) ',
      array(
        array(
          array(
            'type' => 'word',
            'value' => 'duck',
          ),
          array(
            'type' => 'string',
            'value' => 'quack',
          ),
          array(
            'type' => 'word',
            'value' => 'moo',
          ),
        ),
      ),
      '( duck 5:quack moo ) ');

  }

  public function testSubversionWireProtocolPartialFrame() {
    $proto = new DiffusionSubversionWireProtocol();

    // This is primarily a test that we don't hang when we write() a frame
    // which straddles a string boundary.
    $msg1 = $proto->writeData('( duck 5:qu');
    $msg2 = $proto->writeData('ack ) ');

    $this->assertEqual(array(), ipull($msg1, 'structure'));
    $this->assertEqual(
      array(
        array(
          array(
            'type' => 'word',
            'value' => 'duck',
          ),
          array(
            'type' => 'string',
            'value' => 'quack',
          ),
        ),
      ),
      ipull($msg2, 'structure'));
  }

  private function assertSameSubversionMessages(
    $string,
    array $structs,
    $serial_string = null) {

    $proto = new DiffusionSubversionWireProtocol();

    // Verify that the wire message parses into the structs.
    $messages = $proto->writeData($string);
    $messages = ipull($messages, 'structure');
    $this->assertEqual($structs, $messages, 'parse<'.$string.'>');

    // Verify that the structs serialize into the wire message.
    $serial = array();
    foreach ($structs as $struct) {
      $serial[] = $proto->serializeStruct($struct);
    }
    $serial = implode('', $serial);

    if ($serial_string === null) {
      $expect_serial = $string;
    } else {
      $expect_serial = $serial_string;
    }

    $this->assertEqual($expect_serial, $serial, 'serialize<'.$string.'>');
  }
}
