<?php

namespace Drupal\wahapedia;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Defines a Base Entity class for Wahapedia Entities.
 */
abstract class WahapediaEntityBase extends ContentEntityBase implements WahapediaEntityInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   *
   * Define the field properties here.
   *
   * Field name, type and size determine the table structure.
   *
   * In addition, we can define how the field and its content can be manipulated
   * in the GUI. The behaviour of the widgets used can be determined here.
   *
   * @see
   *   https://www.drupal.org/docs/drupal-apis/entity-api/fieldtypes-fieldwidgets-and-fieldformatters
   *   https://www.drupal.org/docs/drupal-apis/entity-api/defining-and-using-content-entity-field-definitions
   *   https://fivejars.com/blog/entity-basefielddefinitions-fields-examples-drupal-8
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Wahapedia entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Wahapedia entity.'))
      ->setReadOnly(TRUE);

    // Name field for the wahapedia entity.
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of this entity.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      // Set no default value.
      ->setDefaultValue(NULL)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['avoid_update'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Avoid update'))
      ->setDescription(t('If checked this entity will NOT be updated from the CSV'))
      ->setDefaultValue(FALSE)
      ->setSettings(['on_label' => t('Update from CSV not allowed'), 'off_label' => t('Update from CSV allowed')])
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'boolean',
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    // Wahapedia ID field for the Entity: the unique Wahapedia ID for the
    // imported entities.
    $fields['wid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Wahapedia ID'))
      ->setDescription(t('The Wahapedia ID as returned from the CSV, or calculated by the unique fields configured for the entity.'))
      ->setSettings([
        'max_length' => 15,
        'text_processing' => 0,
      ])
      // Set no default value.
      ->setDefaultValue(NULL)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code of ContentEntityExample entity.'));
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getImportedFields() {
    return array_merge(['name', 'wid'], array_keys($this::getAdditionalFields()));
  }

  /**
   * Returns the Wahapedia ID if the entity has one.
   *
   * @return mixed
   */
  public function wid() {
    if ($this->hasField('wid')) {
      return $this->wid->getString();
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   *
   * Defaults as wid that usually is our unique indentifier.
   */
  public static function getUniqueFields() {
    return ['wid'];
  }

  /**
   * {@inheritdoc}
   */
  public static function getAdditionalFields() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function getCSVMapping() {
    return [
      'id' => 'wid',
      'name' => 'name',
      '_external' => []
    ];
  }

  /**
   * Check if the entity can be overridden during imports.
   *
   * @return bool
   */
  public function lockedImport() {
    if ($this->hasField('avoid_update')) {
      return $this->get('avoid_update')->getString() ? TRUE : FALSE;
    }

    return FALSE;
  }

  /**
   * Autogenerate the fields with common types.
   *
   * @param $additional_fields
   *
   * @return array
   */
  protected static function autogenerateAdditionalFields($additional_fields, &$fields) {

    foreach ($additional_fields as $key => $additional_field) {
      $field_type = $additional_field['field_type'];
      $label = $additional_field['label'];
      $description = $additional_field['description'];
      $options = isset($additional_field['field_options']) ? $additional_field['field_options'] : [];

      switch ($field_type) {
        case 'string':
          $fields[$key] = self::generateTextField($label, $description);
          break;
        case 'text_long':
          $fields[$key] = self::generateTextLongField($label, $description);
          break;
        case 'entity_reference':
          $type = $options['target_type'];

          $fields[$key] = self::generateEntityReferenceField($label, $description, $type);
          break;
        case 'boolean':
          $fields[$key] = self::generateBooleanField($label, $description);
          break;
        case 'datetime':
          $fields[$key] = self::generateDateField($label, $description);
          break;
      }
    }

    return $fields;
  }

  /**
   * Helper function to quickly generate string fields with common values.
   *
   * @param $field_name
   * @param $description
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   */
  protected static function generateTextField($field_name, $description) {
    return BaseFieldDefinition::create('string')
      ->setLabel($field_name)
      ->setDescription($description)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue(NULL)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
  }

  /**
   * Helper function to quickly generate text_long fields with common values.
   *
   * @param $field_name
   * @param $description
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   */
  protected static function generateTextLongField($field_name, $description) {
    return BaseFieldDefinition::create('text_long')
      ->setLabel($field_name)
      ->setDescription($description)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'rows' => 6,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);
  }

  /**
   * Helper function to quickly generate entity_reference fields with common values.
   *
   * @param $field_name
   * @param $description
   * @param $type string that defines the target type.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   */
  protected static function generateEntityReferenceField($field_name, $description, $type, $widget_type = NULL) {
    // Default widget as autocomplete.
    $widget = [
      'type' => 'entity_reference_autocomplete',
      'settings' => [
        'match_operator' => 'CONTAINS',
        'size' => '60',
        'autocomplete_type' => 'tags',
        'placeholder' => '',
      ],
    ];

    // Other widgets
    if ($widget_type) {
    }

    return BaseFieldDefinition::create('entity_reference')
      ->setLabel($field_name)
      ->setDescription($description)
      ->setSetting('target_type', $type)
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'settings' => [
          'link' => FALSE
        ]
      ])
      ->setDisplayOptions('form', $widget)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
  }

  /**
   * Helper function to quickly generate boolean fields with common values.
   *
   * @param $field_name
   * @param $description
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   */
  protected static function generateBooleanField($field_name, $description) {
    return BaseFieldDefinition::create('boolean')
      ->setLabel($field_name)
      ->setDescription($description)
      ->setDefaultValue(FALSE)
      ->setSettings(['on_label' => t('Yes'), 'off_label' => t('No')])
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'boolean',
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);
  }

  /**
   * Helper function to quickly generate datetime fields with common values.
   *
   * @param $field_name
   * @param $description
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   */
  protected static function generateDateField($field_name, $description) {
    return BaseFieldDefinition::create('datetime')
      ->setLabel($field_name)
      ->setDescription($description)
      ->setSettings([
        'datetime_type' => 'date',
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'datetime_default',
        'settings' => [
          'format_type' => 'medium',
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
  }
}
