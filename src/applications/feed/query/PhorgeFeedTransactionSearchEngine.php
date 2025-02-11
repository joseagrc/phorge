<?php

final class PhorgeFeedTransactionSearchEngine
  extends PhorgeApplicationSearchEngine {

  public function getResultTypeDescription() {
    return pht('Transactions');
  }

  public function getApplicationClassName() {
    return 'PhorgeFeedApplication';
  }

  public function newQuery() {
    return new PhorgeFeedTransactionQuery();
  }

  protected function buildCustomSearchFields() {
    return array(
      id(new PhorgeUsersSearchField())
        ->setLabel(pht('Authors'))
        ->setKey('authorPHIDs')
        ->setAliases(array('author', 'authors')),
      id(new PhorgeSearchDatasourceField())
        ->setLabel(pht('Object Types'))
        ->setKey('objectTypes')
        ->setAliases(array('objectType'))
        ->setDatasource(new PhorgeTransactionsObjectTypeDatasource()),
      id(new PhorgeSearchDateField())
        ->setLabel(pht('Created After'))
        ->setKey('createdStart'),
      id(new PhorgeSearchDateField())
        ->setLabel(pht('Created Before'))
        ->setKey('createdEnd'),
    );
  }

  protected function buildQueryFromParameters(array $map) {
    $query = $this->newQuery();

    if ($map['authorPHIDs']) {
      $query->withAuthorPHIDs($map['authorPHIDs']);
    }

    if ($map['objectTypes']) {
      $query->withObjectTypes($map['objectTypes']);
    }

    $created_min = $map['createdStart'];
    $created_max = $map['createdEnd'];

    if ($created_min && $created_max) {
      if ($created_min > $created_max) {
        throw new PhorgeSearchConstraintException(
          pht(
            'The specified "Created Before" date is earlier in time than the '.
            'specified "Created After" date, so this query can never match '.
            'any results.'));
      }
    }

    if ($created_min || $created_max) {
      $query->withDateCreatedBetween($created_min, $created_max);
    }

    return $query;
  }

  protected function getURI($path) {
    return '/feed/transactions/'.$path;
  }

  protected function getBuiltinQueryNames() {
    $names = array(
      'all' => pht('All Transactions'),
    );

    return $names;
  }

  public function buildSavedQueryFromBuiltin($query_key) {
    $query = $this->newSavedQuery()
      ->setQueryKey($query_key);

    switch ($query_key) {
      case 'all':
        return $query;
    }

    return parent::buildSavedQueryFromBuiltin($query_key);
  }

  protected function renderResultList(
    array $objects,
    PhorgeSavedQuery $query,
    array $handles) {
    assert_instances_of($objects, 'PhorgeApplicationTransaction');

    $viewer = $this->requireViewer();

    $handle_phids = array();
    foreach ($objects as $object) {
      $author_phid = $object->getAuthorPHID();
      if ($author_phid !== null) {
        $handle_phids[] = $author_phid;
      }
      $object_phid = $object->getObjectPHID();
      if ($object_phid !== null) {
        $handle_phids[] = $object_phid;
      }
    }

    $handles = $viewer->loadHandles($handle_phids);

    $rows = array();
    foreach ($objects as $object) {
      $author_phid = $object->getAuthorPHID();
      $object_phid = $object->getObjectPHID();

      try {
        $title = $object->getTitle();
      } catch (Exception $ex) {
        $title = null;
      }

      $rows[] = array(
        $handles[$author_phid]->renderLink(),
        $handles[$object_phid]->renderLink(),
        AphrontTableView::renderSingleDisplayLine($title),
        phorge_datetime($object->getDateCreated(), $viewer),
      );
    }

    $table = id(new AphrontTableView($rows))
      ->setHeaders(
        array(
          pht('Author'),
          pht('Object'),
          pht('Transaction'),
          pht('Date'),
        ))
      ->setColumnClasses(
        array(
          null,
          null,
          'wide',
          'right',
        ));

    return id(new PhorgeApplicationSearchResultView())
      ->setTable($table);
  }

  protected function newExportFields() {
    $fields = array(
      id(new PhorgePHIDExportField())
        ->setKey('authorPHID')
        ->setLabel(pht('Author PHID')),
      id(new PhorgeStringExportField())
        ->setKey('author')
        ->setLabel(pht('Author')),
      id(new PhorgeStringExportField())
        ->setKey('objectType')
        ->setLabel(pht('Object Type')),
      id(new PhorgePHIDExportField())
        ->setKey('objectPHID')
        ->setLabel(pht('Object PHID')),
      id(new PhorgeStringExportField())
        ->setKey('objectName')
        ->setLabel(pht('Object Name')),
      id(new PhorgeStringExportField())
        ->setKey('description')
        ->setLabel(pht('Description')),
    );

    return $fields;
  }

  protected function newExportData(array $xactions) {
    $viewer = $this->requireViewer();

    $phids = array();
    foreach ($xactions as $xaction) {
      $phids[] = $xaction->getAuthorPHID();
      $phids[] = $xaction->getObjectPHID();
    }
    $handles = $viewer->loadHandles($phids);

    $export = array();
    foreach ($xactions as $xaction) {
      $xaction_phid = $xaction->getPHID();

      $author_phid = $xaction->getAuthorPHID();
      if ($author_phid) {
        $author_name = $handles[$author_phid]->getName();
      } else {
        $author_name = null;
      }

      $object_phid = $xaction->getObjectPHID();
      if ($object_phid) {
        $object_name = $handles[$object_phid]->getName();
      } else {
        $object_name = null;
      }

      $old_target = $xaction->getRenderingTarget();
      try {
        $description = $xaction
          ->setRenderingTarget(PhorgeApplicationTransaction::TARGET_TEXT)
          ->getTitle();
      } catch (Exception $ex) {
        $description = null;
      }
      $xaction->setRenderingTarget($old_target);

      $export[] = array(
        'authorPHID' => $author_phid,
        'author' => $author_name,
        'objectType' => phid_get_subtype($xaction_phid),
        'objectPHID' => $object_phid,
        'objectName' => $object_name,
        'description' => $description,
      );
    }

    return $export;
  }

}
