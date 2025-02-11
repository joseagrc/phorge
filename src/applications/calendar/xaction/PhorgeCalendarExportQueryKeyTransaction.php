<?php

final class PhorgeCalendarExportQueryKeyTransaction
  extends PhorgeCalendarExportTransactionType {

  const TRANSACTIONTYPE = 'calendar.export.querykey';

  public function generateOldValue($object) {
    return $object->getQueryKey();
  }

  public function applyInternalEffects($object, $value) {
    $object->setQueryKey($value);
  }

  public function getTitle() {
    return pht(
      '%s changed the query for this export.',
      $this->renderAuthor());
  }

  public function validateTransactions($object, array $xactions) {
    $actor = $this->getActor();

    $errors = array();

    foreach ($xactions as $xaction) {
      $value = $xaction->getNewValue();

      $query = id(new PhorgeSavedQueryQuery())
        ->setViewer($actor)
        ->withEngineClassNames(array('PhorgeCalendarEventSearchEngine'))
        ->withQueryKeys(array($value))
        ->executeOne();
      if ($query) {
        continue;
      }

      $builtin = id(new PhorgeCalendarEventSearchEngine())
        ->setViewer($actor)
        ->getBuiltinQueries($actor);
      if (isset($builtin[$value])) {
        continue;
      }

      $errors[] = $this->newInvalidError(
        pht(
          'Query key "%s" does not identify a valid event query.',
          $value),
        $xaction);
    }

    if ($this->isEmptyTextTransaction($object->getQueryKey(), $xactions)) {
      $errors[] = $this->newRequiredError(
        pht('Calendar exports must have a query key.'));
    }

    return $errors;
  }

}
