<?php

namespace Drupal\wahapedia\Entity\Controller;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\views\Entity\View;
use Drupal\views\Views;

/**
 * A list builder that uses Views for filtering, sorting and so on. The
 * generated view can be customized through Views UI.
 *
 * If a module wants to provide a default view for an entity, it should create
 * a view named ENTITY_TYPE_list. The usual way of doing this is to put the
 * exported view in a config/install/views.view.ENTITY_TYPE_list.yml file.
 *
 * @see https://www.drupal.org/node/2429699 for the entity reference filters
 * as select / autocomplete
 */
class ViewsEntityListBuilder extends EntityListBuilder {

  /**
   * The View object used to render the list.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $view;

  /**
   * {@inheritdoc}
   */
  public function load() {
    $this->getView()->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return $this->getView()->render();
  }

  /**
   * Returns an executable view to list entities.
   *
   * @return \Drupal\views\ViewExecutable
   *   A view to render a list of entities.
   */
  public function getView() {
    if (empty($this->view)) {
      // First, tries to get existing view.
      $this->view = Views::getView($this->getViewId());
      if (empty($this->view)) {
        // View does not exist. Create one.
        $this->view = $this->createView()->getExecutable();
      }
    }
    return $this->view;
  }

  /**
   * Returns the ID of a view to list entities.
   *
   * @return string
   *   The ID of the view used to render the list of entities.
   */
  protected function getViewId() {
    return $this->entityType->id() . '_list';
  }

  /**
   * Creates a view to list and administer entities.
   *
   * @return \Drupal\views\Entity\View
   *   A View entity to show a list of entities.
   */
  protected function createView() {
    $entity_type_id = $this->entityType->id();
    $base_table = $this->entityType->getBaseTable();
    $data_table = $this->entityType->getDataTable();
    $id_field = $this->entityType->getKey('id');
    $wid_field = $this->entityType->getKey('wid');
    $base_field_definitions = \Drupal::service('entity_field.manager')
      ->getBaseFieldDefinitions($entity_type_id);

    // Gets the table used by the label field.
    $label_field = $this->entityType->getKey('label');
    $label_field_definition = $base_field_definitions[$label_field] ?: NULL;
    $label_field_table = $base_table;
    if ($label_field_definition && $data_table) {
      // Translatable fields are stored on the data table.
      $label_field_table = $data_table;
    }

    $faction_field = isset($base_field_definitions['faction_id']) ? 'faction_id' : FALSE;

    // Creates the view.
    $values = [
      'id' => $this->getViewId(),
      'base_table' => $data_table ?: $base_table,
      'base_field' => $id_field,
      'label' => t('@entity_type listing', ['@entity_type' => $this->entityType->getLabel()]),
      'description' => t('Listing used by the entity administration interface.'),
    ];
    $view = View::create($values);
    $display = &$view->getDisplay('default');

    // WID field.
    $display['display_options']['fields'][$wid_field] = [
      'id' => $wid_field,
      'table' => $data_table ?: $base_table,
      'field' => $wid_field,
      'label' => t('Wahapedia ID'),
      'settings' => [
      ],
    ];

    if ($label_field) {
      // Adds a column for the label.
      $display['display_options']['fields'][$label_field] = [
        'id' => $label_field,
        'table' => $label_field_table,
        'field' => $label_field,
        'label' => t('Name'),
        'settings' => [
          'link_to_entity' => TRUE,
        ],
      ];
    }
    else {
      // Uses the entity ID if it does not have a label field.
      $display['display_options']['fields'][$id_field] = [
        'id' => $id_field,
        'table' => $data_table ?: $base_table,
        'field' => $id_field,
        'label' => t('Name'),
        'settings' => [
          'link_to_entity' => TRUE,
        ],
      ];
    }

    if ($faction_field) {
      $display['display_options']['fields']['faction_id'] = [
        'id' => $faction_field,
        'table' => $data_table ?: $base_table,
        'field' => $faction_field,
        'label' => t('Faction'),
        'settings' => [
        ],
      ];
    }

    // Adds a column for the operations.
    $display['display_options']['fields']['operations'] = [
      'id' => 'operations',
      'table' => $base_table,
      'field' => 'operations',
      'label' => t('Operations'),
    ];

    // Sets the view style to table.
    $display['display_options']['style'] = [
      'type' => 'table',
      'columns' => [],
      'options' => [
        'empty_table' => TRUE,
        'default' => 'name',
        'info' => [
          'name' => [
            'sortable' => TRUE
          ],
        ]
      ],
    ];

    if ($label_field) {
      $display['display_options']['style']['columns'][$label_field] = $label_field;
    }
    else {
      $display['display_options']['style']['columns'][$id_field] = $id_field;
    }

    $display['display_options']['style']['columns']['wid'] = 'wid';

    // Add faction if available.
    if ($faction_field) {
      $display['display_options']['style']['columns'][$faction_field] = $faction_field;

      $display['display_options']['style']['options']['info'][$faction_field] = [
        'sortable' => TRUE
      ];
    }

    $display['display_options']['style']['columns']['operations'] = 'operations';


    // Shows a message when the list is empty.
    $display['display_options']['empty'] = [
      'area' => [
        'id' => 'area',
        'table' => 'views',
        'field' => 'area',
        'content' => [
          'value' => t('There are no @entity_type yet.', ['@entity_type' => $this->entityType->getPluralLabel()]),
          'format' => 'basic_html',
        ],
      ],
    ];

    // Sorts by label (if available) and ID (for paging).
    $display['display_options']['sorts'] = [];
    if (!empty($label_field)) {
      $display['display_options']['sorts'][$label_field] = [
        'id' => $label_field,
        'table' => $label_field_table,
        'field' => $label_field,
        'order' => 'ASC',
      ];
    }
    $display['display_options']['sorts'][$id_field] = [
      'id' => $id_field,
      'table' => $data_table ?: $base_table,
      'field' => $id_field,
      'order' => 'ASC',
    ];

    // Filter by name
    $display['display_options']['filters'] = [];
    if (!empty($label_field)) {
      $display['display_options']['filters'][$label_field] = [
        'id' => $label_field,
        'table' => $label_field_table,
        'field' => $label_field,
        'operator' => 'contains',
        'exposed' => TRUE,
        'expose' => [
          'identifier' => 'name',
          'label' => t('Name')
        ]
      ];
    }

    // Filter by Wahapedia ID
    $display['display_options']['filters'][$wid_field] = [
      'id' => $wid_field,
      'table' => $data_table ?: $base_table,
      'field' => $wid_field,
      'operator' => 'contains',
      'exposed' => TRUE,
      'expose' => [
        'identifier' => 'wid',
        'label' => t('Wahapedia ID')
      ]
    ];

    // Add faction relationship and filter
    if ($faction_field) {
      // Exposed filter.
      $display['display_options']['filters']['name_1'] = [
        'id' => 'name_1',
        'table' => 'wahapedia_faction',
        'field' => 'name',
        'operator' => 'contains',
        'exposed' => TRUE,
        'relationship' => 'faction_id',
        'expose' => [
          'identifier' => 'faction_name',
          'label' => t('Faction')
        ]
      ];

      // Relationship.
      $display['display_options']['relationships']['faction_id'] = [
        'id' => 'faction_id',
        'table' => 'wahapedia_datasheet',
        'field' => 'faction_id',
        'relationship' => 'none',
        'group_type' => 'group',
        'admin_label' => t('Wahapedia Faction'),
        'required' => FALSE,
        'entity_type' => 'wahapedia_datasheet',
        'entity_field' => 'faction_id',
        'plugin_id' => 'standard',
      ];
    }

    // Uses full pager.
    $display['display_options']['pager'] = [
      'type' => 'full',
    ];

    // Saves the view.
    $view->save();
    return $view;
  }

}
