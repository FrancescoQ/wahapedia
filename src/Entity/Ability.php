<?php

namespace Drupal\wahapedia\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\wahapedia\WahapediaEntityBase;

/**
 * Defines the Ability entity.
 *
 * @see https://www.drupal8.ovh/en/tutoriels/245/custom-views-data-handler-for-a-custom-entity-on-drupal-8
 * for custom views integration for the "views_data" key.
 *
 * @ContentEntityType(
 *   id = "wahapedia_ability",
 *   label = @Translation("Wahapedia Ability"),
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
 *   base_table = "wahapedia_ability",
 *   admin_permission = "administer wahapedia",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "wid" = "wid"
 *   },
 *   links = {
 *     "canonical" = "/wahapedia/ability/{wahapedia_ability}",
 *     "add-form" = "/wahapedia/ability/add",
 *     "edit-form" = "/wahapedia/ability/{wahapedia_ability}/edit",
 *     "delete-form" = "/wahapedia/ability/{wahapedia_ability}/delete",
 *     "collection" = "/admin/content/wahapedia/abilities"
 *   },
 *   field_ui_base_route = "wahapedia.ability_settings",
 * )
 *
 */
class Ability extends WahapediaEntityBase {
  /**
   * {@inheritdoc}
   */
  public static function getAdditionalFields() {
    return [
      'type' => [
        'field_type' => 'string',
        'label' => t('Type'),
        'description' => t('Ability type')
      ],

      // The faction is not present in the CSV for the abilities,
      // but we will add this during the import.
      'faction_id' => [
        'field_type' => 'entity_reference',
        'field_options' => [
          'target_type' => 'wahapedia_faction'
        ],
        'label' => t('Faction ID'),
        'description' => t('Reference to the Faction')
      ],

      'description' => [
        'field_type' => 'text_long',
        'label' => t('Description'),
        'description' => t('Ability description')
      ],
      'legend' => [
        'field_type' => 'text_long',
        'label' => t('Legend'),
        'description' => t('Ability legend')
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
