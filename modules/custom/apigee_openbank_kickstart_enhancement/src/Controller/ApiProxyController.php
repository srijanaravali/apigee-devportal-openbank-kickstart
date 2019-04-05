<?php

namespace Drupal\apigee_openbank_kickstart_enhancement\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\apigee_edge\Entity\ApiProductInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Class ApiProxyController.
 */
class ApiProxyController extends ControllerBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
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
   * Constructs a new ApiProxyController object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,     QueryFactory $entityQuery,
  CacheBackendInterface $cache_service) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityQuery = $entityQuery;
    $this->cacheService = $cache_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.query'),
      $container->get('cache.data')
    );
  }

  public function getProxyNode($api_proxy_name) {
    $node = NULL;
    if ($api_proxy_name) {
      $cid = sprintf('api_proxy_node:%s', $api_proxy_name);
      if ($cache = $this->cacheService->get($cid)) {
        $node = $cache->data;
      }
      else {
        $query = $this->entityQuery->get('node');
        $query->condition('status', 1);
        $query->condition('type', 'apigee_proxy');
        $query->condition('field_machine_name', $api_proxy_name);
        $node_id = current($query->execute());
        if ($node_id) {
          $node = $this->entityTypeManager->getStorage('node')->load($node_id);
          $this->cacheService->set($cid, $node, Cache::PERMANENT, ['node:' . $node_id]);
        }
      }
    }
    return $node;
  }

  /**
   * Getcontent.
   *
   * @return string
   *   Return Hello string.
   */
  public function getContent($api_product_name, $api_proxy_name) {
    $node = $this->getProxyNode($api_proxy_name);
    if (!$node) {
      throw new NotFoundHttpException();
    }
    return $this->entityTypeManager->getViewBuilder('node')->view($node, 'full');
  }

}
