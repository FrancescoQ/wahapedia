<?php

namespace Drupal\wahapedia;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Class WahapediaCSVProcessor.
 *
 * @package Drupal\wahapedia
 */
class WahapediaCSVProcessor {

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a Drupal\wahapedia\WahapediaCSVProcessor object.
   */
  public function __construct(LoggerChannelFactory $logger, LanguageManagerInterface $language_manager, MessengerInterface $messenger, FileSystemInterface $file_system, EntityTypeManagerInterface $entity_type_manager) {
    $this->logger = $logger->get('wahapedia');
    $this->languageManager = $language_manager;
    $this->messenger = $messenger;
    $this->fileSystem = $file_system;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Parse the CSV and returns an array with valid data for the given
   * entity type: no useless key => value and sanitized keys read from the CSV,
   * so we're sure that they will map with our keys.
   *
   * @param $path
   * @param $entity_type
   *
   * @return array
   */
  public function getEntityDataFromCSV($path, $entity_type) {
    $class = $this->entityTypeManager->getDefinition($entity_type)->getClass();

    $map = [];
    if (method_exists($class, 'getCSVMapping')) {
      $map = $class::getCSVMapping();
    }

    $csv = $this->parseCSV($path, $map);
    return $csv;
  }

  /**
   * Parse a CSV file and return an array.
   * This assumes that the first row is the header, and builds an associative
   * array where for each row will be present a key => value pair for all
   * the data.
   * A second optional parameter can be given with an associative array of type
   * 'csv id' => 'drupal id' of the columns/fields to cleanup the unwanted data
   * and re-map the keys that are different in Drupal from the CSV.
   *
   * @param $path string of the CSV path (can bee either remote or a
   *   filesystem path.
   *
   * @param $map array of 'csv ids' => 'drupal ids' to map the fetched header
   *   columns and keep only the needed ones.
   *
   * @return array
   */
  public function parseCSV($path, $map = []) {
    $csv = [];
    $file = file($path);
    if (!$file) {
      $this->messenger->addError(t('Unable to load file: @file', ['@file' => $path]));
      $this->logger->error(t('Unable to load file: @file', ['@file' => $path]));
      return $csv;
    }

    $rows = array_map(function($row) { return str_getcsv($row, WAHAPEDIA_CSV_SEPARATOR); }, $file);
    $header = array_shift($rows);
    foreach ($header as $header_key => $header_value) {
      $header[$header_key] = $this->filterValue($header_value);
    }

    $keys_to_unset = [];
    if ($map) {
      $allowed_keys = array_keys($map);
      $external_keys = [];
      $external = [];
      foreach ($header as $key => $value) {
        $value = $this->filterValue($value);
        if (!in_array($value, $allowed_keys)) {

          if(!empty($map['_external'])) {
            if (in_array($value, $map['_external'])) {
              $external_keys[$key] = $value;
            }
          }

          $keys_to_unset[] = $key;
          unset($header[$key]);
        }
        else {
          $header[$key] = $map[$value];
        }
      }
    }

    foreach($rows as $row) {

      if (!empty($keys_to_unset)) {
        foreach ($keys_to_unset as $key) {

          // Before unsetting check if we have to add the value to the external
          // values.
          if (!empty($external_keys[$key])) {
            $external[$external_keys[$key]] = $row[$key];
          }

          unset($row[$key]);
        }
      }

      foreach ($row as $row_key => $row_value) {
        $row[$row_key] = $this->filterValue($row_value);
      }

      $csv_row = array_combine($header, $row);
      if (!empty($external)) {
        $csv_row['_external'] = $external;
      }
      $csv[] = $csv_row;
    }

    return $csv;
  }

  /**
   * Prepare the CSV Data to be importend in Drupal.
   *
   * CSVs create relationship in the opposite way from what we want on Drupal:
   * i.e. the Keyword CSV has a field "datasheet_id" while we want the
   * Datasheet
   * to be the one who refers to the keywords, so we have only one place where
   * all the data is aggregated (the Datasheet in this example).
   *
   * So here we manipulate the CSV data as we like to easier import our
   * entities.
   *
   * @param $csv_data array of data fetched from the CSVs
   *
   * @return $data array of data ready to be imported into drupal
   */
  public function prepareData($csv_data) {
    $data = $csv_data;
    // @TODO: maybe not needed.
    return $data;
  }

  /**
   * Remove invisible characters and sanitize the string to be sure to have a
   * clean string.
   *
   * i.e. the var_dump-ing the utf8 converted "id" returns something like that
   * without the filtering:
   *
   * string 'id' (length=2)
   * string 'ï»¿id' (length=8)
   *
   * @param $value
   */
  public function filterValue($value) {
    return filter_var($value, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW|FILTER_FLAG_STRIP_HIGH);
  }

  /**
   * Reorder the importers that have to run, based on the predefined order we
   * have to respect.
   *
   * @param $data
   */
  public function reorderImporters($data) {
    $order = $this::getCSVOrder();
    uksort($data, function ($a, $b) use ($order) {
      $pos_a = array_search($a, $order);
      $pos_b = array_search($b, $order);
      return $pos_a - $pos_b;
    });
    return $data;
  }

  /**
   * Create factions based on CSV data.
   *
   * @param $entities_data
   * @param $csv_data
   */
  public function createWahapediaFactionEntities($entities_data, $csv_data, $text_format, $update) {
    return $this->createEntities($entities_data, 'wahapedia_faction', $text_format, $update);
  }

  /**
   * Create Sources based on CSV data.
   *
   * @param $entities_data
   * @param $csv_data
   */
  public function createWahapediaSourceEntities($entities_data, $csv_data, $text_format, $update) {
    return $this->createEntities($entities_data, 'wahapedia_source', $text_format, $update);
  }

  /**
   * Create Datasheets based on CSV data.
   *
   * @param $entities_data array the data fetched from the CSV to create the
   *   entities.
   * @param $csv_data array the entire set of the CSV data, in case we need it
   *   while creating the entities.
   * @param $text_format string of the text format to be used when importing
   *   text_long fields.
   * @param $update boolean value used to decide if existing entities must be
   *   updated or ignored.
   */
  public function createWahapediaDatasheetEntities($entities_data, $csv_data, $text_format, $update) {
    foreach ($entities_data as $key => $entity_data) {
      if (!empty($entity_data['faction_id'])) {
        $faction_id = $entity_data['faction_id'];
        $entities_data[$key]['faction_id'] = $this->getMappedEntity('wahapedia_faction', 'wid', $faction_id);
      }

      if (!empty($entity_data['source_id'])) {
        $source_id = $entity_data['source_id'];
        $entities_data[$key]['source_id'] = $this->getMappedEntity('wahapedia_source', 'wid', $source_id);
      }

      // Associate the Models.
      $datasheet_id = $entity_data['wid'];
      foreach ($csv_data['wahapedia_model'] as $csv_model) {
        if ($csv_model['_external']['datasheet_id'] === $datasheet_id) {
          foreach ($csv_model['_external'] as $value) {
            $unique_string_data[] = $value;
          }

          $mode_wid = $this::getCustomWIDFromString(implode('-', $unique_string_data));

        }
      }
      $r="";
    }
//    return $this->createEntities($entities_data, 'wahapedia_datasheet', $text_format, $update);
  }

  /**
   * Create Abilities based on CSV data.
   *
   * @param $entities_data array the data fetched from the CSV to create the
   *   entities.
   * @param $csv_data array the entire set of the CSV data, in case we need it
   *   while creating the entities.
   * @param $text_format string of the text format to be used when importing
   *   text_long fields.
   * @param $update boolean value used to decide if existing entities must be
   *   updated or ignored.
   */
  public function createWahapediaAbilityEntities($entities_data, $csv_data, $text_format, $update) {
    $data = [];
    foreach ($entities_data as $key => $entity_data) {
      if (!empty($entity_data['faction_id'])) {
        $faction_id = $entity_data['faction_id'];
        if ($faction_id) {
          $entities_data[$key]['faction_id'] = $this->getMappedEntity('wahapedia_faction', 'wid', $faction_id);
        }
      }

      $data[$entity_data['wid']] = $entities_data[$key];
    }
    return $this->createEntities($data, 'wahapedia_ability', $text_format, $update);
  }

  /**
   * Create Keywords based on CSV data.
   *
   * @param $entities_data array the data fetched from the CSV to create the
   *   entities.
   * @param $csv_data array the entire set of the CSV data, in case we need it
   *   while creating the entities.
   * @param $text_format string of the text format to be used when importing
   *   text_long fields.
   * @param $update boolean value used to decide if existing entities must be
   *   updated or ignored.
   */
  public function createWahapediaKeywordEntities($entities_data, $csv_data, $text_format, $update) {
    $data = [];

    foreach ($entities_data as $key => $entity_data) {
      // The CSV Relates the Keyword to the Datasheet, but we want it related
      // to the faction. The Keywords will be related to the datasheet during
      // the datasheets import.
      if (!empty($entity_data['_external']['datasheet_id'])) {
        $datasheet_id = $entity_data['_external']['datasheet_id'];
        $faction_id = $this->getFactionFromDatasheet($csv_data['wahapedia_datasheet'], $datasheet_id);
        if ($faction_id) {
          $entities_data[$key]['faction_id'] = $this->getMappedEntity('wahapedia_faction', 'wid', $faction_id);
        }
      }

      // Create a data array keyed by WID to avoid duplicates.
      $data[$this->getMD5CustomWID('wahapedia_keyword', $entity_data)] = $entities_data[$key];
    }

    return $this->createEntities($data, 'wahapedia_keyword', $text_format, $update);
  }

  /**
   * Create Stratagems based on CSV data.
   *
   * @param $entities_data array the data fetched from the CSV to create the
   *   entities.
   * @param $csv_data array the entire set of the CSV data, in case we need it
   *   while creating the entities.
   * @param $text_format string of the text format to be used when importing
   *   text_long fields.
   * @param $update boolean value used to decide if existing entities must be
   *   updated or ignored.
   */
  public function createWahapediaStratagemEntities($entities_data, $csv_data, $text_format, $update) {
    foreach ($entities_data as $key => $entity_data) {
      if (!empty($entity_data['faction_id'])) {
        $faction_id = $entity_data['faction_id'];
        $entities_data[$key]['faction_id'] = $this->getMappedEntity('wahapedia_faction', 'wid', $faction_id);
      }

      if (!empty($entity_data['source_id'])) {
        $source_id = $entity_data['source_id'];
        $entities_data[$key]['source_id'] = $this->getMappedEntity('wahapedia_source', 'wid', $source_id);
      }

      // Create a unique identified for the stratagem as a combination of name and faction.
      $entities_data[$key]['wid'] = $this::getMD5CustomWID('wahapedia_stratagem', $entity_data);
    }

    return $this->createEntities($entities_data, 'wahapedia_stratagem', $text_format, $update);
  }

  /**
   * Create Psychic Powers based on CSV data.
   *
   * @param $entities_data array the data fetched from the CSV to create the
   *   entities.
   * @param $csv_data array the entire set of the CSV data, in case we need it
   *   while creating the entities.
   * @param $text_format string of the text format to be used when importing
   *   text_long fields.
   * @param $update boolean value used to decide if existing entities must be
   *   updated or ignored.
   */
  public function createWahapediaPsychicPowerEntities($entities_data, $csv_data, $text_format, $update) {
    foreach ($entities_data as $key => $entity_data) {
      if (!empty($entity_data['faction_id'])) {
        $faction_id = $entity_data['faction_id'];
        $entities_data[$key]['faction_id'] = $this->getMappedEntity('wahapedia_faction', 'wid', $faction_id);
      }

      // Create a unique identified for the Psychic Powers as a combination of name and faction.
      $entities_data[$key]['wid'] = $this::getMD5CustomWID('wahapedia_psychic_power', $entity_data);
    }
    return $this->createEntities($entities_data, 'wahapedia_psychic_power', $text_format, $update);
  }

  /**
   * Create Wargear based on CSV data.
   *
   * @param $entities_data array the data fetched from the CSV to create the
   *   entities.
   * @param $csv_data array the entire set of the CSV data, in case we need it
   *   while creating the entities.
   * @param $text_format string of the text format to be used when importing
   *   text_long fields.
   * @param $update boolean value used to decide if existing entities must be
   *   updated or ignored.
   */
  public function createWahapediaWargearEntities($entities_data, $csv_data, $text_format, $update) {
    foreach ($entities_data as $key => $entity_data) {
      if (!empty($entity_data['faction_id'])) {
        $faction_id = $entity_data['faction_id'];
        $entities_data[$key]['faction_id'] = $this->getMappedEntity('wahapedia_faction', 'wid', $faction_id);
      }

      if (!empty($entity_data['source_id'])) {
        $source_id = $entity_data['source_id'];
        $entities_data[$key]['source_id'] = $this->getMappedEntity('wahapedia_source', 'wid', $source_id);
      }
    }
    return $this->createEntities($entities_data, 'wahapedia_wargear', $text_format, $update);
  }

  /**
   * Create Warlord Traits based on CSV data.
   *
   * @param $entities_data array the data fetched from the CSV to create the
   *   entities.
   * @param $csv_data array the entire set of the CSV data, in case we need it
   *   while creating the entities.
   * @param $text_format string of the text format to be used when importing
   *   text_long fields.
   * @param $update boolean value used to decide if existing entities must be
   *   updated or ignored.
   */
  public function createWahapediaWarlordTraitEntities($entities_data, $csv_data, $text_format, $update) {
    foreach ($entities_data as $key => $entity_data) {
      if (!empty($entity_data['faction_id'])) {
        $faction_id = $entity_data['faction_id'];
        $entities_data[$key]['faction_id'] = $this->getMappedEntity('wahapedia_faction', 'wid', $faction_id);
      }

      // Create a unique identified for the Warlord Trait as a combination of name and faction.
      $entities_data[$key]['wid'] = $this::getMD5CustomWID('wahapedia_warlord_trait', $entity_data);
    }
    return $this->createEntities($entities_data, 'wahapedia_warlord_trait', $text_format, $update);
  }

  /**
   * Create Models based on CSV data.
   *
   * @param $entities_data array the data fetched from the CSV to create the
   *   entities.
   * @param $csv_data array the entire set of the CSV data, in case we need it
   *   while creating the entities.
   * @param $text_format string of the text format to be used when importing
   *   text_long fields.
   * @param $update boolean value used to decide if existing entities must be
   *   updated or ignored.
   */
  public function createWahapediaModelEntities($entities_data, $csv_data, $text_format, $update) {
    $damages = isset($csv_data['_auxiliary_csv']['wahapedia_datasheet_damage']) ? $this->prepareDamageCSV($csv_data['_auxiliary_csv']['wahapedia_datasheet_damage']) : [];

    foreach ($entities_data as $key => $entity_data) {
      $unique_string_data = [];
      foreach ($entity_data['_external'] as $value) {
        $unique_string_data[] = $value;
      }

      // Create a unique identified for the model.
      $entities_data[$key]['wid'] = $this::getCustomWIDFromString(implode('-', $unique_string_data));

      // Associate the damage table data, if any. If one of the columns of the
      // damage data is the "model" we associate to the right model of the
      // datasheet, otherwise to the first model of the datasheet.
      $datasheet_id = $entity_data['_external']['datasheet_id'];
      $model_name = $entity_data['name'];
      $model_line = $entity_data['_external']['line'];

      if (!empty($damages[$datasheet_id])) {
        // If we have a model value, skip all the models that aren't that model.
        if (in_array('model', array_keys($damages[$datasheet_id][1])) && $damages[$datasheet_id][1]['model'] !== $model_name) {
          continue;
        }

        // If we DON'T have the model, keep processing only the first model of
        // the datasheet.
        if (!in_array('model', array_keys($damages[$datasheet_id][1])) && $model_line != 1) {
          continue;
        }

        // @todo see https://www.drupal.org/project/paragraphs/issues/2707017
        $paragraph_data = [];
        foreach ($damages[$datasheet_id] as $damage_row) {
          $wounds = $damage_row['RemainingW'];
          unset($damage_row['RemainingW']);
          foreach ($damage_row as $label => $value) {
            $paragraph_data[] = [
              'type' => 'wahapedia_datasheet_damage_row',
              'field_wh_dmg_w' => ['value' => $wounds],
              'field_wh_dmg_label' => ['value' => $label],
              'field_wh_dmg_value' => ['value' => $value]
            ];
          }
        }

        $entities_data[$key]['_paragraphs']['field_wh_model_dmg_table'] = $paragraph_data;
      }
    }

    return $this->createEntities($entities_data, 'wahapedia_model', $text_format, $update);
  }

  /**
   * Prepare the Damages CSV to be converted in paragraphs data.
   *
   * @param $damages_csv
   */
  private function prepareDamageCSV($damages_csv) {
    $datasheet_damages = [];
    $keys = [];
    foreach ($damages_csv as $key => $csv_row) {
      $line = $csv_row['line'];
      $id = $csv_row['datasheet_id'];

      // Line 0 contains the headers, other lines the actual table.
      // The CSV always have some empty columns because in certain cases we
      // have a certain amount of characteristics that degrade, some other we
      // have fewer.
      // We relies on the labels to store only the right amount of needed
      // columns: we mark as "_empty_" the ones we want to skip later when
      // saving the values.
      if ($line == 0) {
        foreach ($csv_row as $row_key => $row_value) {
          $keys[$row_key] = !empty($row_value) ? $row_value : '_empty_' . $row_key;
        }
      }
      else {
        foreach ($csv_row as $row_key => $row_value) {
          if ($row_key === 'datasheet_id' || $row_key === 'line') {
            continue;
          }

          // Skip the cells marked as "empty".
          if (strpos($keys[$row_key], '_empty_') === FALSE) {
            // Set the paragraph field structure:
            $datasheet_damages[$id][$line][$keys[$row_key]] = $row_value;
          }
        }
      }
    }

    return $datasheet_damages;
  }

  /**
   * Generic method for base use case of entity creation.
   *
   * @param $entities_data array the data fetched from the CSV to create the
   *   entities.
   * @param $entity_type string the entity type to create.
   * @param $text_format string of the text format to be used when importing
   *   text_long fields.
   * @param $update boolean value used to decide if existing entities must be
   *   updated or ignored.
   */
  private function createEntities($entities_data, $entity_type, $text_format, $update = FALSE) {
    $result = [
      'saved' => 0,
      'errors' => 0,
      'existing_not_updated' => 0
    ];

    $error_message = t('Unable to create @entity_type entity check log for additional details.', ['@entity_type' => $entity_type]);

    // Get the entity storage.
    try {
      $entity_storage = $this->entityTypeManager->getStorage($entity_type);
    }
    catch (\Exception $e) {
      $this->logger->error($e);
      $this->messenger->addError($error_message);
      return $result;
    }

    foreach ($entities_data as $entity_data) {

      // Create a custom Wahapedia ID based on unique fields.
      if (!isset($entity_data['wid'])) {
        $entity_data['wid'] = $this::getMD5CustomWID($entity_type, $entity_data);
      }

      // Check if an entity exists and eventually update it.
      $unique = [];

      // Try to load the entity by Wahapedia ID, or fallback to unique fields
      // set  at entity definition level.
      if (!empty($entity_data['wid'])) {
        $unique['wid'] = $entity_data['wid'];
      }
      else {
        $unique_fields_keys = $this->getUniqueFields($entity_type);
        foreach ($unique_fields_keys as $key) {
          $unique[$key] = $entity_data[$key];
        }
      }

      // Load existing entities.
      $existing = $entity_storage->loadByProperties($unique);

      // Unset non-field data
      foreach ($entity_data as $field_key => $field_data) {
        if (strpos($field_key, '_external') === 0) {
          unset($entity_data[$field_key]);
        }
      }

      if (!empty($existing)) {

        // Avoid any further processing if the form is not set to update existing entities.
        if (!$update) {
          $result['existing_not_updated']++;
          continue;
        }

        /** @var \Drupal\wahapedia\WahapediaEntityInterface $entity */
        foreach ($existing as $entity) {
          if (!$entity->lockedImport()) {
            $this->setEntityValues($entity, $entity_data, $text_format);
          }
          else {
            $result['existing_not_updated']++;
          }
        }
      }
      else {
        $entity = $entity_storage->create();
        $this->setEntityValues($entity, $entity_data, $text_format);
      }

      try {
        $entity->save();
        $result['saved']++;
      }
      catch (\Exception $e) {
        $result['errors']++;
        $this->logger->error(t('Unable to create @entity_type entity during import: ', ['@entity_type' => $entity_type]) . ' ' . $e->getMessage());
        $this->messenger->addError($error_message);
      }
    }

    return $result;
  }

  /**
   * Set the entity values, with optional text format for fields that need it.
   *
   * @param \Drupal\wahapedia\WahapediaEntityInterface $entity
   * @param $entity_data
   *
   * @return void
   */
  private function setEntityValues(WahapediaEntityInterface $entity, $entity_data, $text_format) {
    foreach($entity_data as $field_key => $field_value) {

      // Set all the paragraphs, than continue to the next fields.
      if ($field_key === '_paragraphs') {
        foreach ($field_value as $paragraph_field => $paragraphs_data) {
          foreach ($paragraphs_data as $paragraph_data) {
            $paragraph = Paragraph::create($paragraph_data);
            $paragraph->save();
            $entity->{$paragraph_field}->appendItem($paragraph);
          }
        }
        continue;
      }

      // Skip to the next field if this entity doesn't have this field attached.
      if (!$entity->hasField($field_key)) {
        continue;
      }

      // Set the value and optionally the text format, if it's a text long field.
      $entity->set($field_key, $field_value);
      if ($entity->getFieldDefinition($field_key)->getType() === 'text_long') {
        $entity->{$field_key}->format = $text_format;
      }
    }
  }

  /**
   * Get Entity Type unique fields.
   *
   * @param $entity_type
   *
   * @return null
   */
  private function getUniqueFields($entity_type) {
    $definitions = $this->entityTypeManager->getDefinitions();
    if (empty($definitions[$entity_type])) {
      return NULL;
    }
    /** @var \Drupal\Core\Entity\ContentEntityType $definition */
    $definition = $definitions[$entity_type];
    $class = $definition->getClass();
    return $class::getUniqueFields();
  }

  /**
   * Get the Drupal entity ID from another field value.
   *
   * @param $entity_type
   * @param $field
   * @param $value
   */
  private function getMappedEntity($entity_type, $field, $value) {
    static $entities_map;
    if (!empty($entities_map[$entity_type][$value])) {
      return $entities_map[$entity_type][$value];
    }
    else {
      $entities = $this->entityTypeManager->getStorage($entity_type)->loadByProperties([$field => $value]);
      if ($entities) {
        $entity = reset($entities);
        $entities_map[$entity_type][$value] = (int) $entity->id();
        return (int) $entity->id();
      }
    }

    return NULL;
  }

  /**
   * Get the faction of a given datasheet from the CSV data.
   */
  private function getFactionFromDatasheet($csv_datasheets, $datasheet_id) {
    static $datasheets_faction;
    if (!empty($datasheets_faction[$datasheet_id])) {
      return $datasheets_faction[$datasheet_id];
    }
    else {
      foreach ($csv_datasheets as $csv_datasheet) {
        if ($csv_datasheet['wid'] === $datasheet_id) {
          $datasheets_faction[$datasheet_id] = $csv_datasheet['faction_id'];
          return $csv_datasheet['faction_id'];
        }
      }
    }

    return NULL;
  }

  /**
   * Create an MD5 derived unique identifier for entities without a Wahapedia ID.
   * @param $pieces
   */
  public function getMD5CustomWID($entity_type, $data) {
    $id = NULL;
    $definitions = $this->entityTypeManager->getDefinitions();
    if (!empty($definitions[$entity_type])) {
      /** @var \Drupal\Core\Entity\ContentEntityType $definition */
      $definition = $definitions[$entity_type];
      /** @var \Drupal\wahapedia\WahapediaEntityInterface $class */
      $class = $definition->getClass();
      $unique_fields = $class::getUniqueFields();
      $unique_field_string = '';
      foreach ($unique_fields as $unique_field) {
        $unique_field_string .= $data[$unique_field];
      }
      $id = $this->getCustomWIDFromString($unique_field_string);
    }
    return $id;
  }

  /**
   * Actual MD5 Id creation.
   *
   * @param $string
   */
  public function getCustomWIDFromString($string) {
    return substr('MD5-' . md5($string), 0, 10);
  }

  /**
   * Get the order of the importers to be sure that all the dependencies are
   * satisfied.
   *
   * @return string[]
   */
  public static function getCSVOrder() {
    return [
      // Wahapedia entities CSVs.
      'wahapedia_source',
      'wahapedia_faction',
      'wahapedia_ability',
      'wahapedia_stratagem',
      'wahapedia_keyword',
      'wahapedia_wargear',
      'wahapedia_warlord_trait',
      'wahapedia_psychic_power',
      'wahapedia_model',
      'wahapedia_datasheet',

      // All the CSVs not directly related to the entities are processed after
      // the entities ones, so we can for example add the paragraphs data to
      // the existing entities data.
      '_auxiliary_csv'
    ];
  }
}
