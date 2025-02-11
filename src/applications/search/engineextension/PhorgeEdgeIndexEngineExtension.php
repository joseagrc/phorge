<?php

abstract class PhorgeEdgeIndexEngineExtension
  extends PhorgeIndexEngineExtension {

  abstract protected function getIndexEdgeType();
  abstract protected function getIndexDestinationPHIDs($object);

  final public function indexObject(
    PhorgeIndexEngine $engine,
    $object) {

    $edge_type = $this->getIndexEdgeType();

    $old_edges = PhorgeEdgeQuery::loadDestinationPHIDs(
      $object->getPHID(),
      $edge_type);
    $old_edges = array_fuse($old_edges);

    $new_edges = $this->getIndexDestinationPHIDs($object);
    $new_edges = array_fuse($new_edges);

    $add_edges = array_diff_key($new_edges, $old_edges);
    $rem_edges = array_diff_key($old_edges, $new_edges);

    if (!$add_edges && !$rem_edges) {
      return;
    }

    $editor = new PhorgeEdgeEditor();

    foreach ($add_edges as $phid) {
      $editor->addEdge($object->getPHID(), $edge_type, $phid);
    }

    foreach ($rem_edges as $phid) {
      $editor->removeEdge($object->getPHID(), $edge_type, $phid);
    }

    $editor->save();
  }

  final public function getIndexVersion($object) {
    $phids = $this->getIndexDestinationPHIDs($object);
    sort($phids);
    $phids = implode(':', $phids);
    return PhorgeHash::digestForIndex($phids);
  }

}
