<?php

namespace Drupal\zero_util\Data;

use Drupal\Core\Database\Database;

class SimpleQuery {

  public static function getFieldExistOptions(string $table, array $columns, array $conditions = [], array $returns = NULL): array {
    $select = Database::getConnection()->select($table, 'base');
    if ($returns === NULL) $returns = ['entity_id' => 'id'];
    foreach ($returns as $column => $alias) {
      $select->addField('base', $column, $alias);
    }
    foreach ($conditions as $field => $value) {
       $select->condition($field, $value);
    }
    foreach ($columns as $column) {
      $select->groupBy($column);
    }
    $results = [];
    foreach ($select->execute()->fetchAll() as $result) {
      $results[] = (array)$result;
    }
    return $results;
  }

}
