<?php

namespace Drupal\zero_util\Service;

use Drupal\zero_util\Data\DataArray;

class ZeroUtil {

  public function isAssoc(array $array): bool {
    if (count($array) === 0) return FALSE;
    return array_keys($array) !== range(0, count($array) - 1);
  }

  public function checkFullRequirements(array $definition, DataArray|array $object): array {
    if (is_array($object)) $object = new DataArray($object);
    if ($this->isAssoc($definition)) {
      $result = $this->checkRequirements($definition, $object);
    } else {
      $result = ['result' => TRUE];
      foreach ($definition as $def) {
        $result = $this->checkRequirements($def, $object);
        if ($result['result']) break;
      }
    }
    return $result;
  }

  /**
   * @param array $definition
   * @param DataArray $object
   *
   * @return array = [
   *     'result' = TRUE,
   *     'failed_types' = [0 => 'field_one'],
   *     'failed_exists' = [0 => 'field_one'],
   * ]
   */
  public function checkRequirements(array $definition, DataArray $object): array {
    $result = [
      'result' => TRUE,
      'failed_types' => [],
      'failed_exists' => [],
    ];
    foreach ($definition as $name => $type) {
      if ($object->has($name)) {
        if (!$this->isFromType($type, $object->get($name))) {
          $result['result'] = FALSE;
          $result['failed_types'][] = $name;
        }
      } else {
        $result['result'] = FALSE;
        $result['failed_exists'][] = $name;
      }
    }
    return $result;
  }

  public function isFromType(string $type, mixed $object): bool {
    $types = explode('|', $type);
    $found = gettype($object);
    if (in_array($found, $types)) return TRUE;
    if ($found === 'integer' && in_array('double', $types)) return TRUE;
    if ($found === 'object') {
      $class = get_class($object);
      $associated = $this->getAssociatedClasses($class);
      foreach ($types as $value) {
        if (in_array($value, $associated) || in_array(substr($value, 1), $associated)) return TRUE;
      }
    }
    return FALSE;
  }

  public function getAssociatedClasses(string $class): array {
    $classes = [ $class => $class ];
    $classes += class_parents($class);
    $classes += class_implements($class);
    return $classes;
  }

}
