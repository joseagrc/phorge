<?php

final class PhorgeProjectsEditEngineExtension
  extends PhorgeEditEngineExtension {

  const EXTENSIONKEY = 'projects.projects';

  const EDITKEY_ADD = 'projects.add';
  const EDITKEY_SET = 'projects.set';
  const EDITKEY_REMOVE = 'projects.remove';

  public function getExtensionPriority() {
    return 500;
  }

  public function isExtensionEnabled() {
    return PhorgeApplication::isClassInstalled(
      'PhorgeProjectApplication');
  }

  public function getExtensionName() {
    return pht('Projects');
  }

  public function supportsObject(
    PhorgeEditEngine $engine,
    PhorgeApplicationTransactionInterface $object) {

    return ($object instanceof PhorgeProjectInterface);
  }

  public function buildCustomEditFields(
    PhorgeEditEngine $engine,
    PhorgeApplicationTransactionInterface $object) {

    $edge_type = PhorgeTransactions::TYPE_EDGE;
    $project_edge_type = PhorgeProjectObjectHasProjectEdgeType::EDGECONST;

    $object_phid = $object->getPHID();
    if ($object_phid) {
      $project_phids = PhorgeEdgeQuery::loadDestinationPHIDs(
        $object_phid,
        $project_edge_type);
      $project_phids = array_reverse($project_phids);
    } else {
      $project_phids = array();
    }

    $viewer = $engine->getViewer();

    $projects_field = id(new PhorgeProjectsEditField())
      ->setKey('projectPHIDs')
      ->setLabel(pht('Tags'))
      ->setEditTypeKey('projects')
      ->setAliases(array('project', 'projects', 'tag', 'tags'))
      ->setIsCopyable(true)
      ->setUseEdgeTransactions(true)
      ->setCommentActionLabel(pht('Change Project Tags'))
      ->setCommentActionOrder(8000)
      ->setDescription(pht('Select project tags for the object.'))
      ->setTransactionType($edge_type)
      ->setMetadataValue('edge:type', $project_edge_type)
      ->setValue($project_phids)
      ->setViewer($viewer);

    $projects_datasource = id(new PhorgeProjectDatasource())
      ->setViewer($viewer);

    $edit_add = $projects_field->getConduitEditType(self::EDITKEY_ADD)
      ->setConduitDescription(pht('Add project tags.'));

    $edit_set = $projects_field->getConduitEditType(self::EDITKEY_SET)
      ->setConduitDescription(
        pht('Set project tags, overwriting current value.'));

    $edit_rem = $projects_field->getConduitEditType(self::EDITKEY_REMOVE)
      ->setConduitDescription(pht('Remove project tags.'));

    $projects_field->getBulkEditType(self::EDITKEY_ADD)
      ->setBulkEditLabel(pht('Add project tags'))
      ->setDatasource($projects_datasource);

    $projects_field->getBulkEditType(self::EDITKEY_SET)
      ->setBulkEditLabel(pht('Set project tags to'))
      ->setDatasource($projects_datasource);

    $projects_field->getBulkEditType(self::EDITKEY_REMOVE)
      ->setBulkEditLabel(pht('Remove project tags'))
      ->setDatasource($projects_datasource);

    return array(
      $projects_field,
    );
  }

}
