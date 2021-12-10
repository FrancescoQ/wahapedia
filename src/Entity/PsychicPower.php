<?php

namespace Drupal\wahapedia\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\wahapedia\WahapediaEntityBase;

/**
 * Defines the Psychic Power entity.
 *
 * @see https://www.drupal8.ovh/en/tutoriels/245/custom-views-data-handler-for-a-custom-entity-on-drupal-8
 * for custom views integration for the "views_data" key.
 *
 * @ContentEntityType(
 *   id = "wahapedia_psychic_power",
 *   label = @Translation("Wahapedia Psychic Power"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\wahapedia\Entity\Controller\WahapediaEntityListBuilder",
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
 *   base_table = "wahapedia_psychic_power",
 *   admin_permission = "administer wahapedia",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "wid" = "wid"
 *   },
 *   links = {
 *     "canonical" = "/wahapedia/psychic_power/{wahapedia_psychic_power}",
 *     "add-form" = "/wahapedia/psychic_power/add",
 *     "edit-form" = "/wahapedia/psychic_power/{wahapedia_psychic_power}/edit",
 *     "delete-form" = "/wahapedia/psychic_power/{wahapedia_psychic_power}/delete",
 *     "collection" = "/admin/content/wahapedia/psychic_powers"
 *   },
 *   field_ui_base_route = "wahapedia.psychic_power_settings",
 * )
 *
 */
class PsychicPower extends WahapediaEntityBase {
  /**
   * {@inheritdoc}
   */
  public static function getAdditionalFields() {
    return [
      'roll' => [
        'field_type' => 'string',
        'label' => t('Roll'),
        'description' => t('Dice roll required to select the psychic power')
      ],
      'type' => [
        'field_type' => 'string',
        'label' => t('Type'),
        'description' => t('Psychic Power type')
      ],
      'description' => [
        'field_type' => 'text_long',
        'label' => t('Description'),
        'description' => t('Psychic Power description (for non-profile relics or weapons with two profiles)')
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
        'description' => t('Psychic Power background')
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
