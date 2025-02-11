<?php

/**
 * Example event listener. For details about installing Phorge event hooks,
 * refer to @{article:Events User Guide: Installing Event Listeners}.
 */
final class PhorgeExampleEventListener extends PhorgeEventListener {

  public function register() {
    // When your listener is installed, its register() method will be called.
    // You should listen() to any events you are interested in here.
    $this->listen(PhorgeEventType::TYPE_TEST_DIDRUNTEST);
  }

  public function handleEvent(PhutilEvent $event) {
    // When an event you have called listen() for in your register() method
    // occurs, this method will be invoked. You should respond to the event.

    // In this case, we just echo a message out so the event test script will
    // do something visible.

    $console = PhutilConsole::getConsole();
    $console->writeOut(
      "%s\n",
      pht(
        '%s got test event at %d',
        __CLASS__,
        $event->getValue('time')));
  }

}
