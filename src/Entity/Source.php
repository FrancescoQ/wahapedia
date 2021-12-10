<?php

namespace Drupal\wahapedia\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\wahapedia\WahapediaEntityBase;

/**
 * Defines the Source entity.
 *
 * @ContentEntityType(
 *   id = "wahapedia_source",
 *   label = @Translation("Wahapedia Source"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\wahapedia\Entity\Controller\SourceListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\wahapedia\AccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "wahapedia_source",
 *   admin_permission = "administer wahapedia",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "wid" = "wid"
 *   },
 *   links = {
 *     "canonical" = "/wahapedia/source/{wahapedia_source}",
 *     "add-form" = "/wahapedia/source/add",
 *     "edit-form" = "/wahapedia/source/{wahapedia_source}/edit",
 *     "delete-form" = "/wahapedia/source/{wahapedia_source}/delete",
 *     "collection" = "/admin/content/wahapedia/sources"
 *   },
 *   field_ui_base_route = "wahapedia.source_settings",
 * )
 *
 */
class Source extends WahapediaEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function getAdditionalFields() {
    return [
      'type' => [
        'field_type' => 'string',
        'label' => t('Type'),
        'description' => t('Add-on type ("Index", "Supplement", etc.)')
      ],
      'edition' => [
        'field_type' => 'string',
        'label' => t('Edition'),
        'description' => t('Edition number')
      ],
      'version' => [
        'field_type' => 'string',
        'label' => t('Version'),
        'description' => t('Errata version number')
      ],
      'errata_date' => [
        'field_type' => 'string',
        'label' => t('Errata Date'),
        'description' => t('Date of the latest errata (if there is no erratas, then the date of announcement / release)')
      ],
      'errata_link' => [
        'field_type' => 'string',
        'label' => t('Errata Link'),
        'description' => t('Link to errata / source on GW website')
      ]
    ];
  }

  /**
   * {@inheritdoc]
   */
  public static function getCSVMapping() {
    $map = parent::getCSVMapping();
    $additional_fields = self::getAdditionalFields();
    foreach ($additional_fields as $field => $additional_field) {
      $map[$field] = isset($additional_field['original_id']) ? $additional_field['original_id'] : $field;
    }
    return $map;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $additional_fields = self::getAdditionalFields();
    self::autogenerateAdditionalFields($additional_fields, $fields);

    return $fields;
  }
}
