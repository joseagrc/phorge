<?php

abstract class AphrontPageView extends AphrontView {

  private $title;

  public function setTitle($title) {
    $this->title = $title;
    return $this;
  }

  public function getTitle() {
    $title = $this->title;
    if (is_array($title)) {
      $title = implode(" \xC2\xB7 ", $title);
    }
    return $title;
  }

  protected function getHead() {
    return '';
  }

  protected function getBody() {
    return phutil_implode_html('', $this->renderChildren());
  }

  protected function getTail() {
    return '';
  }

  protected function willRenderPage() {
    return;
  }

  protected function willSendResponse($response) {
    return $response;
  }

  protected function getBodyClasses() {
    return null;
  }

  public function render() {

    $this->willRenderPage();

    $title = $this->getTitle();
    $head  = $this->getHead();
    $body  = $this->getBody();
    $tail  = $this->getTail();

    $body_classes = $this->getBodyClasses();

    $body = phutil_tag(
      'body',
      array(
        'class' => nonempty($body_classes, null),
      ),
      array($body, $tail));

    if (PhorgeEnv::getEnvConfig('phorge.developer-mode')) {
      $data_fragment = phutil_safe_html(' data-developer-mode="1"');
    } else {
      $data_fragment = null;
    }

    $response = hsprintf(
      '<!DOCTYPE html>'.
      '<html%s>'.
        '<head>'.
          '<meta charset="UTF-8" />'.
          '<title>%s</title>'.
          '%s'.
        '</head>'.
        '%s'.
      '</html>',
      $data_fragment,
      $title,
      $head,
      $body);

    $response = $this->willSendResponse($response);

    return $response;

  }

}
