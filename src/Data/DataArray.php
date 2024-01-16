<?php

namespace Drupal\zero_util\Data;

use Drupal\zero_util\Exception\DataException;
use stdClass;

class DataArray {

  public static function replaceFrom(array|DataArray $data): callable {
    if (is_array($data)) $data = new DataArray($data);
    return function(string $value, string $match, string $root) use ($data) : string {
      return $data->get($match);
    };
  }

  public static function arrayEqual(array $one, array $two): bool {
    return serialize($one) === serialize($two);
  }

  /**
   * @param string $value
   * @param callable|array|DataArray $replacer(string $value, string $match, string $root): string
   * @param bool $replaceUnknown
   *
   * @return string
   */
  public static function replace(string $value, callable|array|DataArray $replacer, bool $replaceUnknown = TRUE): string {
    if (!is_callable($replacer)) $replacer = DataArray::replaceFrom($replacer);
    $matches = [];
    preg_match_all('#{{\s*([\w.|/-@]+)\s*}}#', $value, $matches);
    foreach ($matches[1] as $index => $match) {
      $keys = explode('|', $match);
      $replacement = NULL;
      foreach ($keys as $key) {
        $replacement = $replacer($value, $key, $matches[0][$index]);
        if (is_string($replacement) || is_numeric($replacement) || is_object($replacement) && !is_array($replacement)) {
          $value = str_replace($matches[0][$index], (string)$replacement, $value);
          break;
        }
      }
      if ($replaceUnknown) {
        if (!is_string($replacement) && !is_numeric($replacement) && !is_object($replacement) || is_array($replacement)) $value = str_replace($matches[0][$index], '', $value);
      }
    }
    return $value;
  }

  public static function replaceAll(string|array $value, callable|array|DataArray $replacer, bool $replaceUnknown = TRUE) {
    if (is_string($value)) {
      return self::replace($value, $replacer, $replaceUnknown);
    } else if (is_array($value)) {
      $newArray = [];
      foreach ($value as $key => $item) {
        if (is_string($key)) {
          $key = self::replace($key, $replacer, $replaceUnknown);
        }
        $newArray[$key] = self::replaceAll($item, $replacer, $replaceUnknown);
      }
      return $newArray;
    } else if (is_object($value)) {
      $newObject = new stdClass();
      foreach (get_object_vars($value) as $key => $item) {
        if (is_string($key)) {
          $key = self::replace($key, $replacer, $replaceUnknown);
        }
        $newObject->{$key} = self::replaceAll($item, $replacer, $replaceUnknown);
      }
      return $newObject;
    } else {
      return $value;
    }
  }

  public static function hasNested($value, string $key, bool $allowNULL = FALSE): bool {
    if ($value instanceof DataArray) return $value->has($key);

    if (strlen($key)) {
      foreach (explode('.', $key) as $part) {
        if (is_array($value) && array_key_exists($part, $value)) {
          $value = $value[$part];
        } else if (is_object($value) && property_exists($value, $part)) {
          $value = $value->$part;
        } else {
          return FALSE;
        }
      }
    }
    return $value !== NULL || $allowNULL;
  }

  public static function getNested($value, string $key, $fallback = NULL) {
    if ($value instanceof DataArray) return $value->get($key, $fallback);

    if (strlen($key)) {
      foreach (explode('.', $key) as $part) {
        if (is_array($value) && array_key_exists($part, $value)) {
          $value = $value[$part];
        } else if (is_object($value) && property_exists($value, $part)) {
          $value = $value->$part;
        } else {
          return $fallback;
        }
      }
    }
    return $value;
  }

  public static function setNested($value, string $key, $item) {
    if (strlen($key) === 0) return $item;
    $subject = &$value;
    $parts = explode('.', $key);
    $last = array_pop($parts);

    // loop through value
    foreach ($parts as $delta => $part) {
      if (is_array($subject)) {
        if (!isset($subject[$part])) {
          $subject[$part] = [];
        }
        $subject = &$subject[$part];
      } else if (is_object($subject)) {
        if (!isset($subject->{$part})) {
          $subject->{$part} = [];
        }
        $subject = &$subject->{$part};
      } else {
        throw new DataException('The subject "' . implode('.', array_slice($parts, 0, $delta + 1)) . '" is not an array or an object. It can not be nested with key "' . $part . '"');
      }
    }

    // insert item
    if (is_array($subject)) {
      $subject[$last] = $item;
    } else if (is_object($subject)) {
      $subject->{$last} = $item;
    } else {
      throw new DataException('The subject "' . implode('.', $parts) . '" is not an array or an object. It can not be nested with key "' . $last . '"');
    }

    return $value;
  }

  private $value;

  public function __construct($value) {
    $this->value = $value;
  }

  public function has(string $key, bool $allowNULL = FALSE): bool {
    return self::hasNested($this->value, $key, $allowNULL);
  }

  /**
   * @param string[] $keys
   * @param $fallback
   * @param array $empty
   *
   * @return bool
   */
  public function hasAll(array $keys, $fallback = NULL, array $empty = [NULL]): bool {
    foreach ($keys as $key) {
      $value = $this->get($key, $fallback);
      if (in_array($value, $empty)) return FALSE;
    }
    return TRUE;
  }

  public function get(string $key, $fallback = NULL) {
    return self::getNested($this->value, $key, $fallback);
  }

  public function getData(string $key, $fallback = NULL): DataArray {
    return new DataArray($this->get($key, $fallback));
  }

  public function map(string $key, callable $callback): array {
    $value = $this->get($key);
    if (!is_array($value)) $value = [$value];
    return array_map(function($value) use ($callback) {
      if (!$value instanceof DataArray) $value = new DataArray($value);
      $return = $callback($value);
      if ($return instanceof DataArray) {
        return $return->get('');
      } else {
        return $return;
      }
    }, $value);
  }

  public function set(string $key, $value): self {
    $this->value = self::setNested($this->value, $key, $value);
    return $this;
  }

  public function value() {
    return $this->value;
  }

}
