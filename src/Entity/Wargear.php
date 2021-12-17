<?php

namespace Drupal\wahapedia\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\wahapedia\WahapediaEntityBase;

/**
 * Defines the Wargear entity.
 *
 * @see https://www.drupal8.ovh/en/tutoriels/245/custom-views-data-handler-for-a-custom-entity-on-drupal-8
 * for custom views integration for the "views_data" key.
 *
 * @ContentEntityType(
 *   id = "wahapedia_wargear",
 *   label = @Translation("Wahapedia Wargear"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\wahapedia\Entity\Controller\ViewsEntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\wahapedia\AccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *      },
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "wahapedia_wargear",
 *   admin_permission = "administer wahapedia",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "wid" = "wid"
 *   },
 *   links = {
 *     "canonical" = "/wahapedia/wargear/{wahapedia_wargear}",
 *     "add-form" = "/wahapedia/wargear/add",
 *     "edit-form" = "/wahapedia/wargear/{wahapedia_wargear}/edit",
 *     "delete-form" = "/wahapedia/wargear/{wahapedia_wargear}/delete",
 *     "collection" = "/admin/content/wahapedia/wargears"
 *   },
 *   field_ui_base_route = "wahapedia.wargear_settings",
 * )
 *
 */
class Wargear extends WahapediaEntityBase {
  /**
   * {@inheritdoc}
   */
  public static function getAdditionalFields() {
    return [
      'type' => [
        'field_type' => 'string',
        'label' => t('Type'),
        'description' => t('Wargear type')
      ],
      'description' => [
        'field_type' => 'text_long',
        'label' => t('Description'),
        'description' => t('Wargear description (for non-profile relics or weapons with two profiles)')
      ],
      'source_id' => [
        'field_type' => 'entity_reference',
        'field_options' => [
          'target_type' => 'wahapedia_source'
        ],
        'label' => t('Source ID'),
        'description' => t('Reference to the Source')
      ],
      'is_relic' => [
        'field_type' => 'boolean',
        'label' => t('Relic'),
        'description' => t('This is a Relic')
      ],
      'faction_id' => [
        'field_type' => 'entity_reference',
        'field_options' => [
          'target_type' => 'wahapedia_faction'
        ],
        'label' => t('Faction ID'),
        'description' => t('Reference to the Faction')
      ],
      'legend' => [
        'field_type' => 'text_long',
        'label' => t('Legend'),
        'description' => t('Relic background')
      ],
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
