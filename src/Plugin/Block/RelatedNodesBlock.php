<?php

namespace Drupal\cache_dev\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\Entity\Node;
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

    $build = [
      '#lazy_builder' => [
        '\Drupal\cache_dev\Plugin\Block\RelatedNodesBlock::lazy_builder',
        [],
      ],
      '#create_placeholder' => TRUE,
    ];

    return $build;
  }

  /**
   * #lazy_builder callback.
   */
  static public function lazy_builder() {
    // Simulate that we have a very "expensive" call to show how big pipe works.
    sleep(2);
    $node = \Drupal::routeMatch()->getParameter('node');
    $taxonomy_from_current_node = $node->field_tags->entity->getName();

    $query = \Drupal::entityQuery('node');
    $query->condition('field_tags.entity.name', $taxonomy_from_current_node, '=');
    $query->range(0, 2);
    $related_node_ids = $query->execute();

    $related_nodes = Node::loadMultiple($related_node_ids);
    $build = [];
    foreach ($related_nodes as $key => $related_node) {
      // Render as view modes.
      $build[$key] = \Drupal::entityTypeManager()
        ->getViewBuilder('node')
        ->view($related_node, 'teaser');
      $build[$key]['#cache']['contexts'][] = 'url';
    }
    return $build;
  }
}
