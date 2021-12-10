<?php

namespace Drupal\wahapedia_rest_api\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\wahapedia\Entity\Faction;
use Psr\Log\LoggerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
/**
 * Provides a resource to get view modes by entity and bundle.
 * @RestResource(
 *   id = "wahapedia_rest_factions",
 *   label = @Translation("Wahapedia Factions"),
 *   uri_paths = {
 *     "canonical" = "/waharest/factions"
 *   }
 * )
 */
class Factions extends ResourceBase {

  /**
   * A current user instance which is logged in the session.
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $loggedUser;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $config
   *   A configuration array which contains the information about the plugin
   *   instance.
   * @param string $module_id
   *   The module_id for the plugin instance.
   * @param mixed $module_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A currently logged user instance.
   */
  public function __construct(
    array $config,
          $module_id,
          $module_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user) {
    parent::__construct($config, $module_id, $module_definition, $serializer_formats, $logger);

    $this->loggedUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $config, $module_id, $module_definition) {
    return new static(
      $config,
      $module_id,
      $module_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('sample_rest_resource'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to GET request.
   */
  public function get() {
    $factions_result = [];

    $factions =\Drupal::entityTypeManager()->getStorage('wahapedia_faction')->loadMultiple();
    /** @var Faction $faction */
    foreach ($factions as $faction) {
      $factions_result[] = array(
        'id' => $faction->wid(),
        'name' => $faction->label(),
//        'link' => $faction->get('link')->getString(),
      );
    }

    $response = new ResourceResponse($factions_result);
    $response->addCacheableDependency($factions_result);
    return $response;
  }

}
