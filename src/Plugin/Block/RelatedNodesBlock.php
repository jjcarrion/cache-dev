<?php

namespace Drupal\cache_dev\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Session\AccountProxy;

/**
 * Provides a 'RelatedNodesBlock' block.
 *
 * @Block(
 *  id = "related_nodes_block",
 *  admin_label = @Translation("Related nodes block"),
 * )
 */
class RelatedNodesBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Routing\CurrentRouteMatch definition.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;
  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;
  /**
   * Drupal\Core\Entity\Query\QueryFactory definition.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;
  /**
   * Drupal\Core\Render\Renderer definition.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;
  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;
  /**
   * Constructs a new RelatedNodesBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        CurrentRouteMatch $current_route_match, 
        EntityTypeManager $entity_type_manager,
        QueryFactory $entity_query,
        Renderer $renderer,
        AccountProxy $current_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentRouteMatch = $current_route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityQuery = $entity_query;
    $this->renderer = $renderer;
    $this->currentUser = $current_user;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('entity.query'),
      $container->get('renderer'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
         'number_of_related_nodes' => 2,
        ] + parent::defaultConfiguration();

 }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['number_of_related_nodes'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of related nodes'),
      '#description' => $this->t(''),
      '#default_value' => $this->configuration['number_of_related_nodes'],
      '#weight' => '0',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['number_of_related_nodes'] = $form_state->getValue('number_of_related_nodes');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->currentRouteMatch->getParameter('node');
    // Without dependency injection:
    // $node = \Drupal::routeMatch()->getParameter('node');
    if (!$node) {
      $build['no_articles'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => 'There are no related articles.',
        '#cache' => [
          'keys' => ['my_custom_cache'],
          'contexts' => [
            'url.path',
          ],
        ],
      ];
      return $build;
    }

    $build = [];

    $taxonomy_from_current_node = $node->field_tags->entity->getName();
    $related_node_ids = $this->getRelatedNodes($taxonomy_from_current_node);

    $related_nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($related_node_ids);

    foreach ($related_nodes as $key => $related_node) {
      // Render as view modes.
      $build[$key] = $this->entityTypeManager
        ->getViewBuilder('node')
        ->view($related_node, 'teaser');
      $build[$key]['#cache']['contexts'][] = 'url';
    }
    //    $user = User::load($this->current_user->id());
    //    $this->renderer->addCacheableDependency($build['user'], $user);
    return $build;
  }

  private function getRelatedNodes($taxonomy_from_current_node) {
    $query = $this->entityQuery->get('node');
    $query->condition('field_tags.entity.name', $taxonomy_from_current_node, '=');
    $query->range(0, $this->configuration['number_of_related_nodes']);
    $ids = $query->execute();
    return $ids;
  }


}
