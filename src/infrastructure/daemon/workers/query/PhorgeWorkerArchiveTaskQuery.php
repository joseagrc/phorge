<?php

final class PhorgeWorkerArchiveTaskQuery
  extends PhorgeWorkerTaskQuery {

  public function execute() {
    $task_table = new PhorgeWorkerArchiveTask();

    $conn_r = $task_table->establishConnection('r');

    $rows = queryfx_all(
      $conn_r,
      'SELECT * FROM %T %Q %Q %Q',
      $task_table->getTableName(),
      $this->buildWhereClause($conn_r),
      $this->buildOrderClause($conn_r),
      $this->buildLimitClause($conn_r));

    return $task_table->loadAllFromArray($rows);
  }
}
