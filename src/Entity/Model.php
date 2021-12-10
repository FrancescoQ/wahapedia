<?php

namespace Drupal\wahapedia\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\wahapedia\WahapediaEntityBase;

/**
 * Defines the Model entity.
 *
 * @ContentEntityType(
 *   id = "wahapedia_model",
 *   label = @Translation("Wahapedia Model"),
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
 *     },
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "wahapedia_model",
 *   admin_permission = "administer wahapedia",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "wid" = "wid"
 *   },
 *   links = {
 *     "canonical" = "/wahapedia/model/{wahapedia_model}",
 *     "add-form" = "/wahapedia/model/add",
 *     "edit-form" = "/wahapedia/model/{wahapedia_model}/edit",
 *     "delete-form" = "/wahapedia/model/{wahapedia_model}/delete",
 *     "collection" = "/admin/content/wahapedia/models"
 *   },
 *   field_ui_base_route = "wahapedia.model_settings",
 * )
 *
 */
class Model extends WahapediaEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function getAdditionalFields() {
    return [
      'M' => [
        'field_type' => 'string',
        'label' => t('M'),
        'description' => t('Move chatacteristic')
      ],
      'WS' => [
        'field_type' => 'string',
        'label' => t('WS'),
        'description' => t('Weapon Skill chatacteristic')
      ],
      'BS' => [
        'field_type' => 'string',
        'label' => t('BS'),
        'description' => t('Ballistic Skill chatacteristic')
      ],
      'S' => [
        'field_type' => 'string',
        'label' => t('S'),
        'description' => t('Strength chatacteristic')
      ],
      'T' => [
        'field_type' => 'string',
        'label' => t('T'),
        'description' => t('Toughness chatacteristic')
      ],
      'W' => [
        'field_type' => 'string',
        'label' => t('W'),
        'description' => t('Wounds chatacteristic')
      ],
      'A' => [
        'field_type' => 'string',
        'label' => t('A'),
        'description' => t('Attacks chatacteristic')
      ],
      'Ld' => [
        'field_type' => 'string',
        'label' => t('Ld'),
        'description' => t('Leadersheep chatacteristic')
      ],
      'Sv' => [
        'field_type' => 'string',
        'label' => t('Sv'),
        'description' => t('Save chatacteristic')
      ],
      'cost' => [
        'field_type' => 'string',
        'label' => t('Cost'),
        'description' => t('Model points cost')
      ],
      'cost_description' => [
        'field_type' => 'string',
        'label' => t('Cost Description'),
        'description' => t('Model points cost comment (default "Does not include wargear")')
      ],
      'models_per_unit' => [
        'field_type' => 'string',
        'label' => t('Models per unit'),
        'description' => t('Number of models')
      ],
      'cost_including_wargear' => [
        'field_type' => 'string',
        'label' => t('Cost including wargear'),
        'description' => t('Model points cost includes wargear')
      ],

      // The damage table is handled with a Paragraph, installed when the module
      // is enabled.
      // For an example of a single model of the datasheet that degrade O'Vesa in:
      // https://wahapedia.ru/wh40k9ed/factions/t-au-empire/The-Eight
      //
      // For a custom ability that degrade see:
      // https://wahapedia.ru/wh40k9ed/factions/thousand-sons/Mutalith-Vortex-Beast
      //
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

    // Used to create the unique identifier.
    $map['_external'][] = 'datasheet_id';
    $map['_external'][] = 'line';

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
