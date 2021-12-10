<?php

namespace Drupal\wahapedia;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Faction entity.
 *
 * We have this interface so we can join the other interfaces it extends.
 *
 * @ingroup wahapedia
 */
interface WahapediaEntityInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Get the additional fields that will be added to the
   * WahapediaEntityBase entity.
   *
   * this is different from the getImportedFields method, as this is used to
   * build our entity definition, and not necessarily the additional fields will
   * be retrieved from wahapedia.
   *
   * @return array in the form:
   * [
   *   'key' => [
   *     'field_type' => 'entity_reference',
   *     'field_options' => ['target_type' => 'wahapedia_faction']  // Optional.
   *     'label' => t('My label'),
   *     'description' => t('My description')
   *   ],
   * ]
   */
  public static function getAdditionalFields();

  /**
   * Returns a map of CSV Columns / Drupal fields for the entity type.
   * an additional "_external" key can be provided for the data that don't belong
   * to an entity field but will be used during the import process.
   * @return array map of CSV key => Drupal key
   */
  public static function getCSVMapping();

  /**
   * Returns an array of keys that define a Wahapedia entity as unique.
   * Usually the wid is enough but sometimes (e.g. Stratagems) the wid doesn't
   * exist, so those fields will be used to create a "custom" wid during the
   * import.
   *
   * @return array of unique keys.
   */
  public static function getUniqueFields();

  /**
   * Returns an array of the field keys that are imported from Wahapedia.
   * Useful to know what fields on an entity are imported and what field are
   * Drupal only.
   *
   * @return string[]
   */
  public function getImportedFields();
}
