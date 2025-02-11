<?php

final class PhorgeProjectsSearchEngineAttachment
  extends PhorgeSearchEngineAttachment {

  public function getAttachmentName() {
    return pht('Projects');
  }

  public function getAttachmentDescription() {
    return pht('Get information about projects.');
  }

  public function loadAttachmentData(array $objects, $spec) {
    $object_phids = mpull($objects, 'getPHID');

    $projects_query = id(new PhorgeEdgeQuery())
      ->withSourcePHIDs($object_phids)
      ->withEdgeTypes(
        array(
          PhorgeProjectObjectHasProjectEdgeType::EDGECONST,
        ));
    $projects_query->execute();

    return array(
      'projects.query' => $projects_query,
    );
  }

  public function getAttachmentForObject($object, $data, $spec) {
    $projects_query = $data['projects.query'];
    $object_phid = $object->getPHID();

    $project_phids = $projects_query->getDestinationPHIDs(
      array($object_phid),
      array(PhorgeProjectObjectHasProjectEdgeType::EDGECONST));

    return array(
      'projectPHIDs' => array_values($project_phids),
    );
  }

}
