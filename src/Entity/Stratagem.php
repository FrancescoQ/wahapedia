<?php

namespace Drupal\wahapedia\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\wahapedia\WahapediaEntityBase;

/**
 * Defines the Stratagem entity.
 *
 * @ContentEntityType(
 *   id = "wahapedia_stratagem",
 *   label = @Translation("Wahapedia Stratagem"),
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
 *     },
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "wahapedia_stratagem",
 *   admin_permission = "administer wahapedia",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "wid" = "wid"
 *   },
 *   links = {
 *     "canonical" = "/wahapedia/wahapedia_stratagem/{wahapedia_stratagem}",
 *     "add-form" = "/wahapedia/wahapedia_stratagem/add",
 *     "edit-form" = "/wahapedia/wahapedia_stratagem/{wahapedia_stratagem}/edit",
 *     "delete-form" = "/wahapedia/wahapedia_stratagem/{wahapedia_stratagem}/delete",
 *     "collection" = "/admin/content/wahapedia/stratagems"
 *   },
 *   field_ui_base_route = "wahapedia.stratagem_settings",
 * )
 *
 */
class Stratagem extends WahapediaEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function getAdditionalFields() {
    return [
      'type' => [
        'field_type' => 'string',
        'label' => t('Type'),
        'description' => t('Stratagem type ("Adeptus Custodes Stratagem", "Ryza Stratagem", etc.)')
      ],
      'cp_cost' => [
        'field_type' => 'string',
        'label' => t('CP Cost'),
        'description' => t('Stratagem command point cost')
      ],
      'legend' => [
        'field_type' => 'text_long',
        'label' => t('Legend'),
        'description' => t('Stratagem background')
      ],
      'description' => [
        'field_type' => 'text_long',
        'label' => t('Description'),
        'description' => t('Stratagem description')
      ],
      'faction_id' => [
        'field_type' => 'entity_reference',
        'field_options' => [
          'target_type' => 'wahapedia_faction'
        ],
        'label' => t('Faction ID'),
        'description' => t('Reference to the Faction')
      ],
      'source_id' => [
        'field_type' => 'entity_reference',
        'field_options' => [
          'target_type' => 'wahapedia_source'
        ],
        'label' => t('Source ID'),
        'description' => t('Reference to the Source')
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
   * Override the base method as Stratagems don't have wid, but we can use
   * name + faction to identify a stratagem as unique.
   * Name could be enough, but in the remote case that a Stratagem with the
   * same name exists in multiple factions in this way we are safe.
   */
  public static function getUniqueFields() {
    return [
      'name',
      'type',
      'faction_id'
    ];
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
