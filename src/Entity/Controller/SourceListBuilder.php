<?php

namespace Drupal\wahapedia\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for wahapedia faction entity.
 *
 * @ingroup wahapedia
 */
class SourceListBuilder extends EntityListBuilder {

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('url_generator')
    );
  }

  /**
   * Constructs a new FactionListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, UrlGeneratorInterface $url_generator) {
    parent::__construct($entity_type, $storage);
    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   *
   * We override ::render() so that we can add our own content above the table.
   * parent::render() is where EntityListBuilder creates the table using our
   * buildHeader() and buildRow() implementations.
   */
  public function render() {
    $build['description'] = [
      '#markup' => $this->t('List of Wahapedia Sources.'),
    ];
    $build['table'] = parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the contact list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['wid'] = $this->t('Wahapedia ID');
    $header['name'] = $this->t('Name');

    $header['type'] = $this->t('Type');
    $header['edition'] = $this->t('Edition');
    $header['version'] = $this->t('Errata version');
    $header['errata_date'] = $this->t('Errata date');
    $header['errata_link'] = $this->t('Errata link');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\wahapedia\Entity\Faction */
    $row['id'] = $entity->id();
    $row['name'] = $entity->toLink()->toString();
    $row['wid'] = $entity->wid();
    $row['type'] = $entity->get('type')->getString();
    $row['edition'] = $entity->get('edition')->getString();
    $row['version'] = $entity->get('version')->getString();
    $row['errata_date'] = $entity->get('errata_date')->getString();
    $row['errata_link'] = $entity->get('errata_link')->getString();

    return $row + parent::buildRow($entity);
  }

}
