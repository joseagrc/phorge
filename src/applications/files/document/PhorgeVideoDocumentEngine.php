<?php

final class PhorgeVideoDocumentEngine
  extends PhorgeDocumentEngine {

  const ENGINEKEY = 'video';

  public function getViewAsLabel(PhorgeDocumentRef $ref) {
    return pht('View as Video');
  }

  protected function getContentScore(PhorgeDocumentRef $ref) {
    // Some video documents can be rendered as either video or audio, but we
    // want to prefer video.
    return 2500;
  }

  protected function getByteLengthLimit() {
    return null;
  }

  protected function getDocumentIconIcon(PhorgeDocumentRef $ref) {
    return 'fa-film';
  }

  protected function canRenderDocumentType(PhorgeDocumentRef $ref) {
    $file = $ref->getFile();
    if ($file) {
      return $file->isVideo();
    }

    $viewable_types = PhorgeEnv::getEnvConfig('files.viewable-mime-types');
    $viewable_types = array_keys($viewable_types);

    $video_types = PhorgeEnv::getEnvConfig('files.video-mime-types');
    $video_types = array_keys($video_types);

    return
      $ref->hasAnyMimeType($viewable_types) &&
      $ref->hasAnyMimeType($video_types);
  }

  protected function newDocumentContent(PhorgeDocumentRef $ref) {
    $file = $ref->getFile();
    if ($file) {
      $source_uri = $file->getViewURI();
    } else {
      throw new PhutilMethodNotImplementedException();
    }

    $mime_type = $ref->getMimeType();

    $video = phutil_tag(
      'video',
      array(
        'controls' => 'controls',
      ),
      phutil_tag(
        'source',
        array(
          'src' => $source_uri,
          'type' => $mime_type,
        )));

    $container = phutil_tag(
      'div',
      array(
        'class' => 'document-engine-video',
      ),
      $video);

    return $container;
  }

}
