<?php

final class PhorgeRemarkupUIExample extends PhorgeUIExample {

  public function getName() {
    return pht('Remarkup');
  }

  public function getDescription() {
    return pht(
      'Demonstrates the visual appearance of various Remarkup elements.');
  }

  public function getCategory() {
    return pht('Technical');
  }

  public function renderExample() {
    $viewer = $this->getRequest()->getUser();

    $content = pht(<<<EOCONTENT
This is some **remarkup text** using ~~exactly one style~~ //various styles//.

  - Fruit
    - Apple
    - Banana
    - Cherry
  - Vegetables
    1. Carrot
    2. Celery

NOTE: This is a note.

(NOTE) This is also a note.

WARNING: This is a warning.

(WARNING) This is also a warning.

IMPORTANT: This is not really important.

(IMPORTANT) This isn't important either.

EOCONTENT
);

    $remarkup = new PHUIRemarkupView($viewer, $content);

    $frame = id(new PHUIBoxView())
      ->addPadding(PHUI::PADDING_LARGE)
      ->appendChild($remarkup);

    return id(new PHUIObjectBoxView())
      ->setHeaderText(pht('Remarkup Example'))
      ->appendChild($frame);
  }

}
