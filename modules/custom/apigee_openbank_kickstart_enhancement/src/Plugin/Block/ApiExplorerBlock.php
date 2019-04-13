<?php

namespace Drupal\apigee_openbank_kickstart_enhancement\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\AliasManager;
use Drupal\Core\Routing\CurrentRouteMatch;

/**
 * Provides a 'ApiExplorerBlock' block.
 *
 * @Block(
 *  id = "api_explorer_block",
 *  admin_label = @Translation("Api explorer block"),
 * )
 */
class ApiExplorerBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Path\AliasManager
   */
  protected $aliasManager;
  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Path\AliasManager
   */
  protected $routeMatch;
  /**
   * Current route.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $route;
  /**
   * Constructs a new ApiExplorerBlock object.
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
    EntityTypeManagerInterface $entity_type_manager,
    AliasManager $alias_manager,
    CurrentRouteMatch $route
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->aliasManager = $alias_manager;
    $this->route = $route;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('path.alias_manager'),
      $container->get('current_route_match')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $items = [];
    // $build['api_explorer_block']['#markup'] = 'Implement ApiExplorerBlock.';
    $api_docs = $this->entityTypeManager->getStorage('apidoc')->loadMultiple();
    $apidoc = $this->route->getParameter('apidoc');
    foreach ($api_docs as $doc) {
      $file = current($doc->get('spec')->referencedEntities());
      if ($file) {
        $uri = $file->getFileUri();
        $url = file_create_url($uri);
        $file_content = file_get_contents($url, 'r');
        $file_json = json_decode($file_content, true);
        if ($file_json) {
          $api_title = $file_json['info']['title'];
          $paths = $file_json['paths'];
          $methods = [];
          foreach($paths as $path => $api_methods) {
            foreach($api_methods as $method => $info) {
              $summary = $info['summary'];
              $description = $info['description'];
              $operation_id = $info['operationId'];
              $tags = isset($info['tags']) ? current($info['tags']) : 'default';
              $methods[] = [
                'method' => $method,
                'summary' => $summary,
                'description' => $description,
                'operation_id' => $operation_id,
                'auth_required' =>  isset($info['security']) && $info['security'] && sizeof($info['security']) ? TRUE: FALSE,
                'path' => $path,
                'tags' => $tags,
                'uri' => sprintf('#/%s/%s', $tags, $operation_id),
                'internal' => $apidoc && $apidoc->id() == $doc->id(),
              ];
            }
          }
          if (sizeof($methods)) {
            $path = sprintf('/apidoc/%s', $doc->id());
            $path_alias = $this->aliasManager->getAliasByPath($path);
            $items[$doc->id()] = [
              'label' => $doc->label(),
              'methods' => $methods,
              'uri' => $path_alias,
            ];
          }
        }
      }
    }
    return  [
      '#theme' => 'api_explorer',
      '#items' => $items,
    ];
  }

}
