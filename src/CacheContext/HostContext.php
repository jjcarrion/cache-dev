<?php

namespace Drupal\cache_dev\CacheContext;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;

/**
* Class HostContext.
*
* @package Drupal\cache_dev
*/
class HostContext implements CacheContextInterface {


  /**
   * Constructs a new UserAgentContext object.
   */
  public function __construct() {
  
  }

  /**
  * {@inheritdoc}
  */
  public static function getLabel() {
    drupal_set_message('Label of cache context');
  }

  /**
  * {@inheritdoc}
  */
  public function getContext() {
    // Actual logic of context variation will lie here.
    return $_SERVER['HTTP_HOST'];
  }

  /**
  * {@inheritdoc}
  */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
