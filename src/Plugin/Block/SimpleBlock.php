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
    $build['user'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => 'Hello ' . \Drupal::currentUser()->getDisplayName(),
      '#cache' => [
        'keys' => ['summer_user'],
        'contexts' => [
          'user',
        ],
      ],
    ];

    return $build;
  }

}
