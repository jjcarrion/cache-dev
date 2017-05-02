<?php

namespace Drupal\cache_dev\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'SimpleBlock' block.
 *
 * @Block(
 *  id = "simple_block",
 *  admin_label = @Translation("Simple block"),
 * )
 */
class SimpleBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['simple_block']['#markup'] = 'Hello ' . \Drupal::currentUser()->getDisplayName();

    return $build;
  }

}
