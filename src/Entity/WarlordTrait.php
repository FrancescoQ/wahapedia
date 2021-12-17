<?php

namespace Drupal\wahapedia\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\wahapedia\WahapediaEntityBase;

/**
 * Defines the Warlord Trait entity.
 *
 * @see https://www.drupal8.ovh/en/tutoriels/245/custom-views-data-handler-for-a-custom-entity-on-drupal-8
 * for custom views integration for the "views_data" key.
 *
 * @ContentEntityType(
 *   id = "wahapedia_warlord_trait",
 *   label = @Translation("Wahapedia Warlord Trait"),
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
 *   base_table = "wahapedia_warlord_trait",
 *   admin_permission = "administer wahapedia",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "wid" = "wid"
 *   },
 *   links = {
 *     "canonical" = "/wahapedia/warlord_trait/{wahapedia_warlord_trait}",
 *     "add-form" = "/wahapedia/warlord_trait/add",
 *     "edit-form" = "/wahapedia/warlord_trait/{wahapedia_warlord_trait}/edit",
 *     "delete-form" = "/wahapedia/warlord_trait/{wahapedia_warlord_trait}/delete",
 *     "collection" = "/admin/content/wahapedia/warlord_trait"
 *   },
 *   field_ui_base_route = "wahapedia.warlord_trait_settings",
 * )
 *
 */
class WarlordTrait extends WahapediaEntityBase {
  /**
   * {@inheritdoc}
   */
  public static function getAdditionalFields() {
    return [
      'faction_id' => [
        'field_type' => 'entity_reference',
        'field_options' => [
          'target_type' => 'wahapedia_faction'
        ],
        'label' => t('Faction ID'),
        'description' => t('Reference to the Faction')
      ],
      'type' => [
        'field_type' => 'string',
        'label' => t('Type'),
        'description' => t('Warlord Trait header')
      ],
      'legend' => [
        'field_type' => 'text_long',
        'label' => t('Legend'),
        'description' => t('Warlord Trait background')
      ],
      'description' => [
        'field_type' => 'text_long',
        'label' => t('Description'),
        'description' => t('Warlord Trait description')
      ],
      'roll' => [
        'field_type' => 'string',
        'label' => t('Roll'),
        'description' => t('Dice roll required to select the warlord trait or his sub-faction')
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
  public static function getUniqueFields() {
    return ['name', 'faction_id'];
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
