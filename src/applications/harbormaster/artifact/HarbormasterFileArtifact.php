<?php

final class HarbormasterFileArtifact extends HarbormasterArtifact {

  const ARTIFACTCONST = 'file';

  public function getArtifactTypeName() {
    return pht('File');
  }

  public function getArtifactTypeDescription() {
    return pht(
      'Stores a reference to file data.');
  }

  public function getArtifactParameterSpecification() {
    return array(
      'filePHID' => 'string',
    );
  }

  public function getArtifactParameterDescriptions() {
    return array(
      'filePHID' => pht('File to create an artifact from.'),
    );
  }

  public function getArtifactDataExample() {
    return array(
      'filePHID' => 'PHID-FILE-abcdefghijklmnopqrst',
    );
  }

  public function renderArtifactSummary(PhorgeUser $viewer) {
    $artifact = $this->getBuildArtifact();
    $file_phid = $artifact->getProperty('filePHID');
    return $viewer->renderHandle($file_phid);
  }

  public function willCreateArtifact(PhorgeUser $actor) {
    // NOTE: This is primarily making sure the actor has permission to view the
    // file. We don't want to let you run builds using files you don't have
    // permission to see, since this could let you violate permissions.
    $this->loadArtifactFile($actor);
  }

  public function loadArtifactFile(PhorgeUser $viewer) {
    $artifact = $this->getBuildArtifact();
    $file_phid = $artifact->getProperty('filePHID');

    $file = id(new PhorgeFileQuery())
      ->setViewer($viewer)
      ->withPHIDs(array($file_phid))
      ->executeOne();
    if (!$file) {
      throw new Exception(
        pht(
          'File PHID "%s" does not correspond to a valid file.',
          $file_phid));
    }

    return $file;
  }

}
