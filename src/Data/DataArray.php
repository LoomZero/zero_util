<?php

namespace Drupal\zero_util\Data;

class DataArray {

  public static function hasNested($value, string $key): bool {
    if ($value instanceof DataArray) return $value->has($key);

    foreach (explode('.', $key) as $part) {
      if (is_array($value) && array_key_exists($part, $value)) {
        $value = $value[$part];
      } else if (is_object($value) && property_exists($value, $part)) {
        $value = $value->$part;
      } else {
        return FALSE;
      }
    }
    return TRUE;
  }

  public static function getNested($value, string $key, $fallback = NULL) {
    if ($value instanceof DataArray) return $value->get($key, $fallback);

    foreach (explode('.', $key) as $part) {
      if (is_array($value) && array_key_exists($part, $value)) {
        $value = $value[$part];
      } else if (is_object($value) && property_exists($value, $part)) {
        $value = $value->$part;
      } else {
        return $fallback;
      }
    }
    return $value;
  }

  private $value;

  public function __construct($value) {
    $this->value = $value;
  }

  public function has(string $key): bool {
    return self::hasNested($this->value, $key);
  }

  public function get(string $key, $fallback = NULL) {
    return self::getNested($this->value, $key, $fallback);
  }

  public function set(string $key, $value): self {
    $this->value[$key] = $value;
    return $this;
  }

  public function value() {
    return $this->value;
  }

}
