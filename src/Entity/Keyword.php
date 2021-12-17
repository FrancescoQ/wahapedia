<?php

namespace Drupal\wahapedia\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\wahapedia\WahapediaEntityBase;

/**
 * Defines the Keyword entity.
 *
 * @see https://www.drupal8.ovh/en/tutoriels/245/custom-views-data-handler-for-a-custom-entity-on-drupal-8
 * for custom views integration for the "views_data" key.
 *
 * @ContentEntityType(
 *   id = "wahapedia_keyword",
 *   label = @Translation("Wahapedia Keyword"),
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
 *   base_table = "wahapedia_keyword",
 *   admin_permission = "administer wahapedia",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "wid" = "wid"
 *   },
 *   links = {
 *     "canonical" = "/wahapedia/keyword/{wahapedia_keyword}",
 *     "add-form" = "/wahapedia/keyword/add",
 *     "edit-form" = "/wahapedia/keyword/{wahapedia_keyword}/edit",
 *     "delete-form" = "/wahapedia/keyword/{wahapedia_keyword}/delete",
 *     "collection" = "/admin/content/wahapedia/keywords"
 *   },
 *   field_ui_base_route = "wahapedia.keyword_settings",
 * )
 *
 */
class Keyword extends WahapediaEntityBase {
  /**
   * {@inheritdoc}
   */
  public static function getAdditionalFields() {
    return [
      'model' => [
        'field_type' => 'string',
        'label' => t('Model'),
        'description' => t('Belonging of this keyword to a specific model of the datasheet')
      ],
      'faction_id' => [
        'field_type' => 'entity_reference',
        'field_options' => [
          'target_type' => 'wahapedia_faction'
        ],
        'label' => t('Faction ID'),
        'description' => t('Reference to the Faction')
      ],
      'is_faction_keyword' => [
        'field_type' => 'boolean',
        'label' => t('Faction keyword'),
        'description' => t('This is a Faction Keyword')
      ]
    ];
  }

  /**
   * {@inheritdoc]
   */
  public static function getCSVMapping() {
    // We don't use any default like other entities because the keywords are
    // remapped and with some additional fields:
    // - the datasheet_id is stored only to create the reference from the
    //   datasheet to the keyword
    // - the faction_id is a field that doesn't exists in the CSV but could be
    //   useful to identify keywords with the same name across factions.
    return [
      'keyword' => 'name',
      'model' => 'model',
      'is_faction_keyword' => 'is_faction_keyword',
      '_external' => [
        'datasheet_id'
      ]
    ];
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
