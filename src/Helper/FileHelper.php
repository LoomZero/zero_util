<?php

namespace Drupal\zero_util\Helper;

use Drupal;
use Drupal\Core\Entity\EntityInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;

class FileHelper {

  public static function getRemoteFileSize(string $url) {
    $headers = get_headers($url, 1);
    return $headers['Content-Length'] ?? -1;
  }

  /**
   * @param string|EntityInterface $bundle
   *
   * @return string
   */
  public static function getMediaSourceField(string|EntityInterface $bundle): string {
    if ($bundle instanceof MediaInterface) {
      $source = $bundle->getSource();
    } else if ($bundle instanceof MediaTypeInterface) {
      $source = $bundle->getSource();
    } else {
      $mediaType = Drupal::entityTypeManager()->getStorage('media_type')->load($bundle);
      $source = $mediaType->getSource();
    }

    return $source->getConfiguration()['source_field'];
  }

  public static function getMediaFile(MediaInterface $media): FileInterface {
    return $media->get(self::getMediaSourceField($media))->get(0)->get('entity')->getValue();
  }

  /**
   * @param string $content
   * @param array $options = [
   *   'path' => ''public://importer/helloworld.txt',
   * ]
   *
   * @return FileInterface
   */
  public static function createFile(string $content, array $options = [], $writeModifier = NULL): FileInterface {
    $options = array_replace_recursive([
      'path' => 'public://importer/',
    ], $options);

    /** @var Drupal\Core\File\FileSystemInterface $fs */
    $fs = Drupal::service('file_system');
    /** @var \Drupal\file\FileRepositoryInterface $fileRepository */
    $fileRepository = Drupal::service('file.repository');

    $dirname = dirname($options['path']);
    $fs->prepareDirectory($dirname, $fs::CREATE_DIRECTORY);

    return $fileRepository->writeData($content, $options['path'], $writeModifier ?? $fs::EXISTS_REPLACE);
  }

  /**
   * @param array $props = [
   *     'id' => 123,
   *     'uri' => 'public://halloworld.txt',
   * ]
   *
   * @return FileInterface|NULL
   */
  public static function findFile(array $props): ?FileInterface {
    $file_storage = Drupal::entityTypeManager()->getStorage('file');
    $loaded = $file_storage->loadByProperties($props);
    if (count($loaded)) {
      return reset($loaded);
    }
    return NULL;
  }

  /**
   * @param FileInterface|NULL $file
   *
   * @return MediaInterface|NULL
   */
  public static function loadMediaFromFile(FileInterface $file = NULL, string $bundle = NULL): ?MediaInterface {
    if ($file === NULL) return NULL;
    $result = Drupal::service('file.usage')->listUsage($file);
    if (isset($result['file']['media'])) {
      foreach ($result['file']['media'] as $id => $count) {
        /** @var MediaInterface $media */
        $media = Drupal::entityTypeManager()->getStorage('media')->load($id);
        if ($bundle === NULL || $media->bundle() === $bundle) {
          return $media;
        }
      }
    }
    return NULL;
  }

  public static function parseDrupalPath(string $url): array {
    $pos = strpos($url, '/files/');
    $path = substr($url, $pos + 7, strlen($url) - strlen(basename($url)) - ($pos + 7));
    return [
      'path' => $path,
      'basename' => basename($url),
      'fullpath' => $path . basename($url),
    ];
  }

}
