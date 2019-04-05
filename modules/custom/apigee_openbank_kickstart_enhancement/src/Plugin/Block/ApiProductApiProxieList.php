<?php

namespace Drupal\apigee_openbank_kickstart_enhancement\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\webprofiler\Entity\EntityManagerWrapper;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Provides a 'ApiProductApiProxieList' block.
 *
 * @Block(
 *  id = "api_product_api_proxie_list",
 *  admin_label = @Translation("Api product api proxie list"),
 * )
 */
class ApiProductApiProxieList extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\webprofiler\Entity\EntityManagerWrapper definition.
   *
   * @var \Drupal\webprofiler\Entity\EntityManagerWrapper
   */
  protected $entityTypeManager;
  /**
   * Drupal\Core\Routing\CurrentRouteMatch definition.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;
  /**
   * Drupal\Core\Entity\Query\QueryFactory definition.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;
  /**
   * Drupal\Core\Cache\CacheBackendInterface definition.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheService;
  /**
   * Constructs a new ApiProductApiProxieList object.
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
    EntityManagerWrapper $entity_type_manager, 
    CurrentRouteMatch $current_route_match,
    QueryFactory $entityQuery,
    CacheBackendInterface $cache_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentRouteMatch = $current_route_match;
    $this->entityQuery = $entityQuery;
    $this->cacheService = $cache_service;
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
      $container->get('current_route_match'),
      $container->get('entity.query'),
      $container->get('cache.data')
    );
  }
  /**
   * Get All Api Proxie Nodes
   */
  private function getApiProxies() {
    $cid = 'apigee_api_proxies';
    if ($cache = $this->cacheService->get($cid)) {
      $apidocs = $cache->data;
    }
    else {
      // Get All Api Proxie Nodes
      $query = $this->entityQuery->get('node');
      $query->condition('status', 1);
      $query->condition('type', 'apigee_proxy');
      $node_ids = $query->execute();
      $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($node_ids);
      $apidocs = [];
      foreach ($nodes as $nid => $node) {
        $machine_name = $node->get('field_machine_name')->getValue()[0]['value'];
        $alias = sprintf('/%s', $machine_name);
        $apidocs[$machine_name] = [
          'name' => $node->label(),
          'id' => $node->id(),
          'machine_name' => $machine_name,
          'alias' => $alias,
        ];
      }
      $this->cacheService->set($cid, $apidocs, Cache::PERMANENT, ['node_list']);
    }
    return $apidocs;
  }
  /**
   * Get Api Products Items for Build
   */
  private function getApiProducts() {
    $cid = 'apigee_api_products';
    if ($cache = $this->cacheService->get($cid)) {
      $build = $cache->data;
    }
    else {
      $build = [];
      $proxie_nodes = $this->getApiProxies();
      // Get All Api Products
      $apidocs = $this->entityTypeManager->getStorage('apidoc')->loadMultiple();
      // Build Array For Blocks
      foreach ($apidocs as $apidoc) {
        $api_product = current($apidoc->get('api_product')->referencedEntities());
        if ($api_product) {
          $proxie_names = $api_product->getProxies();
          $proxies = [];
          foreach($proxie_names as $name) {
            if (isset($proxie_nodes[$name])) {
              $proxies[$name] = $proxie_nodes[$name];
            }
          }
          $apidoc_uri = sprintf('/apidoc/%s', $apidoc->id());
          $alias = \Drupal::service('path.alias_manager')->getAliasByPath($apidoc_uri);
          $build[$api_product->id()] = [
            'name' => $api_product->label(),
            'proxies' => $proxies,
            'alias' => $alias,
            'id' => $this->getApiProductAliasId($apidoc),
          ];
        }
      }
    }
    return $build;
  }
  private function getApiProductAliasId($apidoc) {
    $apidoc_uri = sprintf('/apidoc/%s', $apidoc->id());
    $alias = \Drupal::service('path.alias_manager')->getAliasByPath($apidoc_uri);
    $alias_id = substr($alias, 9);
    return $alias_id;
  }
  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $apidoc = $this->currentRouteMatch->getParameter('apidoc');
    if ($apidoc) {
      $api_product = current($apidoc->get('api_product')->referencedEntities());
      $active_product_id = NULL;
      if ($api_product) {
        $active_product_id = $this->getApiProductAliasId($apidoc);
      }
      $items = $this->getApiProducts();
      $build = [
        '#theme' => 'api_product_list',
        '#items' => $items,
        '#active_product_id' => $active_product_id,
      ];
      return $build;
    }
    $api_product_name = $this->currentRouteMatch->getParameter('api_product_name');
    $api_proxy_name = $this->currentRouteMatch->getParameter('api_proxy_name');
    if ($api_product_name && $api_proxy_name) {
      $items = $this->getApiProducts();
      $build = [
        '#theme' => 'api_product_list',
        '#items' => $items,
        '#active_product_id' => $api_product_name,
        '#active_proxy_id' => $api_proxy_name,
        '#api_proxy_page' => TRUE,
      ];
      return $build;
    }
  }

    /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['node_list', 'api_doc_list']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

}
