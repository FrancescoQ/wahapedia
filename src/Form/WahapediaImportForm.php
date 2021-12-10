<?php

namespace Drupal\wahapedia\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\wahapedia\WahapediaCSVProcessor;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class WahapediaImportForm.
 *
 * @ingroup wahapedia
 */
class WahapediaImportForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'wahapedia.importer_config',
    ];
  }

  /**
   * @var \Drupal\wahapedia\WahapediaCSVProcessor
   */
  protected $wahapediaCSVProcessor;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, WahapediaCSVProcessor $csv_processor, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->wahapediaCSVProcessor = $csv_processor;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('wahapedia.csv_processor'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'wahapedia.import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['entity_settings']['#markup'] = 'Import the data from Wahapedia CSVs source.';

    $wahapedia_base_url = 'https://wahapedia.ru/wh40k9ed/';
    $wahapedia_base_url = 'https://w40k.ddev.site/modules/custom/wahapedia/docs/csv/';

    $form['#tree'] = TRUE;

    $form['options'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Importers options'),
    ];

    $form['options']['update'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Update existing entities'),
      '#description' => $this->t('Check to update exsting entities, otherwise they will be skipepd.'),
      '#default_value' => $this->getDefaultValue('update', FALSE),
    ];

    $form['options']['chunk_size'] = [
      '#type' => 'number',
      '#step' => 1,
      '#min' => 1,
      '#title' => 'Chunk size',
      '#default_value' => $this->getDefaultValue('chunk_size', 500),
    ];

    $formats = filter_formats();
    $available_formats = [];
    /** @var \Drupal\filter\Entity\FilterFormat $format */
    foreach ($formats as $format) {
      $available_formats[$format->id()] = $format->label();
    }

    $default_format = $available_formats['basic_html'] ?? 'plain_text';
    $form['options']['text_format'] = [
      '#type' => 'select',
      '#options' => $available_formats,
      '#title' => $this->t('Text format'),
      '#description' => $this->t('The text format used when saving long text fields'),
      '#default_value' => $this->getDefaultValue('text_format', $default_format),
    ];

    $form['enable_imports'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Enabled importers'),
    ];

    $form['enable_imports']['entity_wahapedia_faction_import'] = [
      '#type' => 'checkbox',
      '#title' => 'Import Factions CSV',
      '#default_value' => $this->getDefaultValue('entity_wahapedia_faction_import', TRUE),
    ];

    $form['enable_imports']['entity_wahapedia_source_import'] = [
      '#type' => 'checkbox',
      '#title' => 'Import Sources CSV',
      '#default_value' => $this->getDefaultValue('entity_wahapedia_source_import', TRUE),
    ];

    $form['enable_imports']['entity_wahapedia_stratagem_import'] = [
      '#type' => 'checkbox',
      '#title' => 'Import Stratagems CSV',
      '#default_value' => $this->getDefaultValue('entity_wahapedia_stratagem_import', TRUE),
    ];

    $form['enable_imports']['entity_wahapedia_ability_import'] = [
      '#type' => 'checkbox',
      '#title' => 'Import Abilities CSV',
      '#default_value' => $this->getDefaultValue('entity_wahapedia_ability_import', TRUE),
    ];

    $form['enable_imports']['entity_wahapedia_wargear_import'] = [
      '#type' => 'checkbox',
      '#title' => 'Import Wargear CSV',
      '#default_value' => $this->getDefaultValue('entity_wahapedia_wargear_import', TRUE),
    ];

//    $form['enable_imports']['wahapedia_wargear_list_import'] = [
//      '#type' => 'checkbox',
//      '#title' => 'Import Wargear list CSV',
//      '#default_value' => $this->getDefaultValue('wahapedia_wargear_list_import', TRUE),
//    ];

    $form['enable_imports']['entity_wahapedia_warlord_trait_import'] = [
      '#type' => 'checkbox',
      '#title' => 'Import Warlord Traits CSV',
      '#default_value' => $this->getDefaultValue('entity_wahapedia_warlord_trait_import', TRUE),
    ];

    $form['enable_imports']['entity_wahapedia_psychic_power_import'] = [
      '#type' => 'checkbox',
      '#title' => 'Import Psychic Powers CSV',
      '#default_value' => $this->getDefaultValue('entity_wahapedia_psychic_power_import', TRUE),
    ];

    $form['enable_imports']['entity_wahapedia_datasheet_import'] = [
      '#type' => 'checkbox',
      '#title' => 'Import Datasheet CSV',
      '#default_value' => $this->getDefaultValue('entity_wahapedia_datasheet_import', TRUE),
    ];

//    $form['enable_imports']['wahapedia_datasheet_wargear_import'] = [
//      '#type' => 'checkbox',
//      '#title' => 'Import Datasheet Wargear CSV',
//      '#default_value' => $this->getDefaultValue('wahapedia_datasheet_wargear_import', TRUE),
//    ];

//    $form['enable_imports']['wahapedia_datasheet_abilities_import'] = [
//      '#type' => 'checkbox',
//      '#title' => 'Import Datasheet Abilities CSV',
//      '#default_value' => $this->getDefaultValue('wahapedia_datasheet_abilities_import', TRUE),
//    ];

//    $form['enable_imports']['wahapedia_datasheet_damage_import'] = [
//      '#type' => 'checkbox',
//      '#title' => 'Import Datasheet Damage CSV',
//      '#default_value' => $this->getDefaultValue('wahapedia_datasheet_damage_import', TRUE),
//    ];

    $form['enable_imports']['entity_wahapedia_keyword_import'] = [
      '#type' => 'checkbox',
      '#title' => 'Import Datasheet Keywords CSV',
      '#default_value' => $this->getDefaultValue('entity_wahapedia_keyword_import', TRUE),
    ];

    $form['enable_imports']['entity_wahapedia_model_import'] = [
      '#type' => 'checkbox',
      '#title' => 'Import Datasheet Models CSV',
      '#default_value' => $this->getDefaultValue('entity_wahapedia_model_import', TRUE),
    ];

//    $form['enable_imports']['wahapedia_datasheet_options_import'] = [
//      '#type' => 'checkbox',
//      '#title' => 'Import Datasheet Wargear Options CSV',
//      '#default_value' => $this->getDefaultValue('wahapedia_datasheet_options_import', TRUE),
//    ];

    $form['csv'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('CSV Files path'),
    ];

    $form['csv']['entity_wahapedia_faction'] = [
      '#type' => 'textfield',
      '#title' => 'Factions CSV',
      '#default_value' => $this->getDefaultValue('entity_wahapedia_faction', $wahapedia_base_url . 'Factions.csv'),
    ];

    $form['csv']['entity_wahapedia_source'] = [
      '#type' => 'textfield',
      '#title' => 'Sources CSV',
      '#default_value' => $this->getDefaultValue('entity_wahapedia_source', $wahapedia_base_url . 'Source.csv'),
    ];

    $form['csv']['entity_wahapedia_stratagem'] = [
      '#type' => 'textfield',
      '#title' => 'Stratagems CSV',
      '#default_value' => $this->getDefaultValue('entity_wahapedia_stratagem', $wahapedia_base_url . 'Stratagems.csv'),
    ];

    $form['csv']['entity_wahapedia_ability'] = [
      '#type' => 'textfield',
      '#title' => 'Abilities CSV',
      '#default_value' => $this->getDefaultValue('entity_wahapedia_ability', $wahapedia_base_url . 'Abilities.csv'),
    ];

    $form['csv']['entity_wahapedia_wargear'] = [
      '#type' => 'textfield',
      '#title' => 'Wargear CSV',
      '#default_value' => $this->getDefaultValue('entity_wahapedia_wargear', $wahapedia_base_url . 'Wargear.csv'),
    ];

    $form['csv']['entity_wahapedia_warlord_trait'] = [
      '#type' => 'textfield',
      '#title' => 'Wargear CSV',
      '#default_value' => $this->getDefaultValue('entity_wahapedia_warlord_trait', $wahapedia_base_url . 'Warlord_traits.csv'),
    ];

    $form['csv']['entity_wahapedia_psychic_power'] = [
      '#type' => 'textfield',
      '#title' => 'Psychic Powers CSV',
      '#default_value' => $this->getDefaultValue('entity_wahapedia_psychic_power', $wahapedia_base_url . 'PsychicPowers.csv'),
    ];

    $form['csv']['entity_wahapedia_datasheet'] = [
      '#type' => 'textfield',
      '#title' => 'Datasheet CSV',
      '#default_value' => $this->getDefaultValue('entity_wahapedia_datasheet', $wahapedia_base_url . 'Datasheets.csv'),
    ];

    $form['csv']['entity_wahapedia_keyword'] = [
      '#type' => 'textfield',
      '#title' => 'Keywords CSV',
      '#default_value' => $this->getDefaultValue('entity_wahapedia_keyword', $wahapedia_base_url . 'Datasheets_keywords.csv'),
    ];

    $form['csv']['entity_wahapedia_model'] = [
      '#type' => 'textfield',
      '#title' => 'Models CSV',
      '#default_value' => $this->getDefaultValue('entity_wahapedia_model', $wahapedia_base_url . 'Datasheets_models.csv'),
    ];

    $form['csv']['wahapedia_wargear_list'] = [
      '#type' => 'textfield',
      '#title' => 'Wargear list CSV (Will be merged in the Wargear CSV data)',
      '#default_value' => $this->getDefaultValue('wahapedia_wargear_list', $wahapedia_base_url . 'Wargear_list.csv'),
    ];

    $form['csv']['wahapedia_datasheet_wargear'] = [
      '#type' => 'textfield',
      '#title' => 'Datasheet Wargear CSV (Will be merged in the Datasheet CSV data)',
      '#default_value' => $this->getDefaultValue('wahapedia_datasheet_wargear', $wahapedia_base_url . 'Datasheets_wargear.csv'),
    ];

    $form['csv']['wahapedia_datasheet_abilities'] = [
      '#type' => 'textfield',
      '#title' => 'Datasheet Abilities CSV (Will be merged in the Datasheet CSV data)',
      '#default_value' => $this->getDefaultValue('wahapedia_datasheet_abilities', $wahapedia_base_url . 'Datasheets_abilities.csv'),
    ];

    $form['csv']['wahapedia_datasheet_damage'] = [
      '#type' => 'textfield',
      '#title' => 'Datasheet Damage CSV (Will be merged in the Datasheet CSV data)',
      '#default_value' => $this->getDefaultValue('wahapedia_datasheet_damage', $wahapedia_base_url . 'Datasheets_damage.csv'),
    ];

    $form['csv']['wahapedia_datasheet_options'] = [
      '#type' => 'textfield',
      '#title' => 'Datasheet Wargear Options CSV (Will be merged in the Datasheet CSV data)',
      '#default_value' => $this->getDefaultValue('wahapedia_datasheet_options', $wahapedia_base_url . 'Datasheets_options.csv'),
    ];

    $form['delete'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('Delete wahapedia content'),
    ];

    $entity_types = wahapedia_get_wahapedia_entity_types(TRUE);

    foreach ($entity_types as $entity_type => $entity_data) {
      $label = $entity_data['label'];
      $count = $entity_data['count'];
      $form['delete'][$entity_type . '_delete'] = [
        '#type' => 'checkbox',
        '#title' => t('Delete @count @entity', ['@count' => $count, '@entity' => $label]),
      ];
    }

    $form['delete']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
      '#submit' => [[$this, 'submitDeleteEntities']],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['run'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Run the importers with the current configuration'),
      '#description' => $this->t('Uncheck this to save the configuration without running the importers.'),
      '#default_value' => FALSE,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * Submit callback to delete Wahapedia Entities.
   */
  public function submitDeleteEntities(array &$form, FormStateInterface $form_state) {
    $delete_values = $form_state->getValue('delete');
    unset($delete_values['submit']);

    // Collect the entity types to delete.
    $entity_types = [];
    foreach ($delete_values as $key => $value) {
      if ($value) {
        $entity_type = str_replace('_delete', '', $key);
        $entity_types[] = $entity_type;
      }
    }

    if (empty($entity_types)) {
      return;
    }

    // Set the main batch operations for each entity type.
    // Each entity type will create its own batch with chunks of entities
    // created each time.
    $batch = array(
      'title' => t('Deleting Wahapedia Entities...'),
      'operations' => [],
      'init_message'     => t('Start deleting'),
      'progress_message' => t('Processed @current out of @total.'),
      'error_message'    => t('An error occurred during processing'),
      'finished' => [$this, 'finishedDelete'],
    );
    foreach ($entity_types as $entity_type) {
      $operation_details = [
        'type' => $entity_type,
      ];
      $batch['operations'][] = [[$this, 'deleteEntities'], [$operation_details]];
    }

    batch_set($batch);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->submitUpdateConfig($form_state);

    if ($form_state->getValue(['actions', 'run'])) {
      $this->submitImportEntities($form_state);
    }
  }

  /**
   * Update the stored configuration.
   */
  private function submitUpdateConfig(FormStateInterface $form_state){
    $config = $this->config('wahapedia.importer_config');
    $settings = [];
    $options_settings = [
      ['options', 'update'],
      ['options', 'chunk_size'],
      ['options', 'text_format']
    ];

    foreach ($options_settings as $options_setting) {
      $settings[$options_setting[1]] = $form_state->getValue($options_setting);
    }

    $enable_imports = $form_state->getValue('enable_imports');
    foreach ($enable_imports as $key => $status) {
      $settings[$key] = $status;
    }


    $csv_paths = $form_state->getValue('csv');
    foreach ($csv_paths as $key => $path) {
      $settings[$key] = $path;
    }

    foreach ($settings as $setting_key => $setting_value) {
      $config->set($setting_key, $setting_value);
    }

    $config->save();
  }

  /**
   * Import entities
   */
  private function submitImportEntities(FormStateInterface $form_state) {
    $update = $form_state->getValue(['options', 'update']);
    $chunk_size = $form_state->getValue(['options', 'chunk_size']);
    $text_format = $form_state->getValue(['options', 'text_format']);

    // Process CSVs to get an array of the retrieved data.
    $csv_paths = $form_state->getValue('csv');
    $csv_data = [];
    foreach ($csv_paths as $form_item => $csv_path) {
      if (!empty($csv_path)) {
        // Some of the CSVs are directly connected with Drupal entities, while
        // the others will create some additional data for the entities.
        $entity_type = NULL;
        if (strpos($form_item, 'entity_') !== FALSE) {
          $entity_type = str_replace('entity_', '', $form_item);
          $definitions = \Drupal::entityTypeManager()->getDefinitions();
          if (!isset($definitions[$entity_type])) {
            $entity_type = NULL;
          }
        }

        if ($entity_type) {
          $csv_data[$entity_type] = $this->wahapediaCSVProcessor->getEntityDataFromCSV($csv_path, $entity_type);
        }
        else {
          $csv_data['_auxiliary_csv'][$form_item] = $this->wahapediaCSVProcessor->parseCSV($csv_path);
        }
      }
    }

    // Reorder the importers to be sure that all the dependencies are satisfied.
    $csv_data = $this->wahapediaCSVProcessor->reorderImporters($csv_data);

    // Set the main batch operations for each entity type.
    // Each entity type will create its own batch with chunks of entities
    // created each time.
    $batch = array(
      'title' => t('Importing Wahapedia Entities...'),
      'operations' => [],
      'init_message'     => t('Start importing'),
      'progress_message' => t('Processed @current out of @total.'),
      'error_message'    => t('An error occurred during processing'),
      'finished' => [$this, 'finishedImport'],
    );
    foreach ($csv_data as $entity_type => $entities_data) {
      // Don't add the batch operation if it's not enabled.
      if (!$form_state->getValue(['enable_imports', 'entity_' . $entity_type . '_import'])) {
        continue;
      }

      $operation_details = [
        'type' => $entity_type,
        'data' => $entities_data,
        'csv_data' => $csv_data,
        'update' => $update,
        'chunk_size' => $chunk_size,
        'text_format' => $text_format
      ];
      $batch['operations'][] = [[$this, 'importEntities'], [$operation_details]];
    }

    batch_set($batch);
  }


  /**
   * Get the default value taken from the existing configuration with a fallback
   * if the config is not set.
   */
  private function getDefaultValue($config_name, $fallback = NULL) {
    $config = $this->config('wahapedia.importer_config');
    return $config->get($config_name) !== NULL ? $config->get($config_name) : $fallback;
  }


  /**
   *
   * Beginning of batches methods.
   *
   */


  /**
   * Main batch process.
   *
   * @param $operations_details
   * @param $context
   */
  public static function importEntities($operation_details, &$context) {
    $entity_type = $operation_details['type'];
    $entities_data = $operation_details['data'];

    // Prepare results count for entity type.
    $context['results']['types'][] = $entity_type;

    // Optional message displayed under the progressbar.
    $context['message'] = t('Running Batch "@id"',
      ['@id' => $entity_type]
    );

    $batch = array(
      'title' => t('Importing Wahapedia Entities of type @type...', ['@type' => $entity_type]),
      'operations' => [],
      'init_message'     => t('Start importing @type', ['@type' => $entity_type]),
      'progress_message' => t('Importing @type entities.', ['@type' => $entity_type]),
      'error_message'    => t('An error occurred during processing'),
      'finished' => [__CLASS__, 'finishedEntityTypeImport'],
    );

    // Single operation with sandbox processing.
    $chunk_size = $operation_details['chunk_size'] ?? 100;
    $chunks = array_chunk($entities_data, $chunk_size);
    $sub_operation_details = $operation_details;
    unset($sub_operation_details['data']);
    $sub_operation_details['chunks'] = $chunks;
    $batch['operations'][] = [[__CLASS__, 'importEntityTypeEntities'], [$sub_operation_details]];

    batch_set($batch);
  }

  /**
   * Finished batch.
   *
   * @param $success
   * @param $results
   * @param $operations
   */
  public static function finishedImport($success, $results, $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      // Here we could do something meaningful with the results.
      // We just display the number of nodes we processed...
      $types = isset($results['types']) ? $results['types'] : [];
      $messenger->addMessage(t('@count entity types processed: @types.', ['@count' => count($types), '@types' => implode(', ', $types)]));
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $messenger->addMessage(
        t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        )
      );
    }
  }

  public static function importEntityTypeEntities($operation_details, &$context) {
    $entity_type = $operation_details['type'];
    $chunks = $operation_details['chunks'];
    $text_format = $operation_details['text_format'];

    // Use the $context['sandbox'] at your convenience to store the
    // information needed to track progression between successive calls.
    if (empty($context['sandbox'])) {
      $context['sandbox'] = [];
      $context['sandbox']['progress'] = 0;
      $context['results']['types'][$entity_type]['chunks'] = 0;
      $context['results']['types'][$entity_type]['entities'] = 0;
      $context['results']['types'][$entity_type]['saved'] = 0;
      $context['results']['types'][$entity_type]['errors'] = 0;
      $context['results']['types'][$entity_type]['existing_not_updated'] = 0;

      // Save the count for the termination message.
      $context['sandbox']['total'] = count($chunks);
    }

    // Get the current chunk to process.
    $chunk = $chunks[$context['sandbox']['progress']];

    /** @var WahapediaCSVProcessor $processor */
    $processor = \Drupal::service('wahapedia.csv_processor');
    $type = str_replace('_', '', ucwords($entity_type, '_'));
    $method = 'create' . $type . 'Entities';
    if (method_exists($processor, $method)) {
      $context['results']['types'][$entity_type]['chunks'] ++;
      $context['results']['types'][$entity_type]['entities'] += count($chunk);

      // Process the entities.
      $csv_data = $operation_details['csv_data'];
      $update = $operation_details['update'];
      $result = $processor->{$method}($chunk, $csv_data, $text_format, $update);

      $context['results']['types'][$entity_type]['saved'] += $result['saved'];
      $context['results']['types'][$entity_type]['errors'] += $result['errors'];
      $context['results']['types'][$entity_type]['existing_not_updated'] += $result['existing_not_updated'];
    }
    else {
      $context['results']['types'][$entity_type]['errors'] = count($chunk);
    }

    // Update our progress information.
    $context['sandbox']['progress']++;
    $context['message'] = t('Running Batch to import chunk @number of @total for entity type "@id"',
      [
        '@number' => $context['sandbox']['progress'] + 1,
        '@total' => count($chunks),
        '@id' => $entity_type,
      ]
    );

    // Inform the batch engine that we are not finished,
    // and provide an estimation of the completion level we reached.
    if ($context['sandbox']['progress'] != $context['sandbox']['total']) {
      $context['finished'] = ($context['sandbox']['progress'] >= $context['sandbox']['total']);
    }
  }

  /**
   * Finished batch.
   *
   * @param $success
   * @param $results
   * @param $operations
   */
  public static function finishedEntityTypeImport($success, $results, $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      foreach ($results['types'] as $type => $processed) {
        $messenger->addMessage(t('Processed @entities entities in @chunks chunks of @type entity type.', [
          '@chunks' => $processed['chunks'],
          '@type' => $type,
          '@entities' => $processed['entities'],
        ]));
        if ($processed['saved']) {
          $messenger->addMessage(t('Saved @saved @type entities', [
            '@saved' => $processed['saved'],
            '@type' => $type,
          ]));
        }
        if ($processed['errors']) {
          $messenger->addError(t('@errors errors for @type entities', [
            '@errors' => $processed['errors'],
            '@type' => $type,
          ]));
        }
        if ($processed['existing_not_updated']) {
          $messenger->addWarning(t('@existing @type entities exists and have not being updated.', [
            '@existing' => $processed['existing_not_updated'],
            '@type' => $type,
          ]));
        }
      }
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $messenger->addMessage(
        t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        )
      );
    }
  }

  /**
   * Batch to delete entities.
   *
   * @param $operation_details
   * @param $context
   */
  public static function deleteEntities($operation_details, &$context) {
    $entity_type = $operation_details['type'];

    // Prepare results count for entity type.
    $context['results']['types'][] = $entity_type;

    // Optional message displayed under the progressbar.
    $context['message'] = t('Running Batch "@id"',
      ['@id' => $entity_type]
    );

    $storage_handler = \Drupal::entityTypeManager()->getStorage($entity_type);
    $entities = $storage_handler->loadMultiple();
    $storage_handler->delete($entities);
  }

  /**
   * Finished batch.
   *
   * @param $success
   * @param $results
   * @param $operations
   */
  public static function finishedDelete($success, $results, $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      // Here we could do something meaningful with the results.
      // We just display the number of nodes we processed...
      $types = isset($results['types']) ? $results['types'] : [];
      $messenger->addMessage(t('@count entity types deleted: @types.', ['@count' => count($types), '@types' => implode(', ', $types)]));
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $messenger->addMessage(
        t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        )
      );
    }
  }
}
