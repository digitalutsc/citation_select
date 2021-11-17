<?php

namespace Drupal\citation_select\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\field\FieldStorageConfigInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Citation Select settings for this site.
 */
class CslMapForm extends ConfigFormBase {

  /**
   * Entity field manager service.
   *
   * @var Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypePluginManager;

  /**
   * List of CSL fields.
   *
   * @var array
   */
  protected $cslFields = [
    "abstract",
    "annote",
    "archive",
    "archive_location",
    "archive-place",
    "authority",
    "call-number",
    "citation-label",
    "citation-number",
    "collection-title",
    "container-title",
    "container-title-short",
    "dimensions",
    "DOI",
    "edition",
    "event",
    "event-place",
    "first-reference-note-number",
    "genre",
    "ISBN",
    "ISSN",
    "jurisdiction",
    "keyword",
    "locator",
    "medium",
    "note",
    "original-publisher",
    "original-publisher-place",
    "original-title",
    "page",
    "page-first",
    "PMCID",
    "PMID",
    "publisher",
    "publisher-place",
    "references",
    "reviewed-title",
    "scale",
    "section",
    "source",
    "status",
    "title",
    "title-short",
    "URL",
    "version",
    "year-suffix",
    "language",
  // Dates.
    "accessed",
    "container",
    "event-date",
    "issued",
    "original-date",
    "submitted",
  // Names.
    "author",
    "collection-editor",
    "composer",
    "container-author",
    "director",
    "editor",
    "editorial-director",
    "illustrator",
    "interviewer",
    "original-author",
    "recipient",
    "reviewed-author",
    "translator",
  ];

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, FieldTypePluginManagerInterface $field_type_plugin_manager) {
    $this->entityFieldManager = $entity_field_manager;
    $this->fieldTypePluginManager = $field_type_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.field_type'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'citation_select_csl_map';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['citation_select.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['csl_map_table'] = [
      '#type' => 'table',
      '#caption' => $this->t('Field Mapping'),
      '#header' => [
        $this->t('CSL Field'),
        $this->t('Node Field'),
      ],
    ];

    $fields = $this->getFields();

    foreach ($this->cslFields as $key) {
      $form['csl_map_table'][$key]['csl_field'] = [
        '#type' => 'item',
        '#markup' => $key,
        '#value' => $key,
      ];
      $form['csl_map_table'][$key]['node_field'] = [
        '#type' => 'select',
        '#empty_option' => $this->t('- Select -'),
        '#options' => $fields,
      ];
    }

    $this->setDefaults($form, $this->config('citation_select.settings')->get('csl_map'));

    $form['reference_type_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Select field referencing reference type taxonomy'),
      '#options' => $this->getFields(),
      '#default_value' => $this->config('citation_select.settings')->get('reference_type_field'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Set defaults based on settings.
   *
   * @param array $form
   *   Form to render.
   * @param array $config_map
   *   Config map to get defaults from.
   */
  protected function setDefaults(array &$form, array $config_map) {
    foreach ($config_map as $node_field => $csl_fields) {
      foreach ($csl_fields as $csl_field) {
        $form['csl_map_table'][$csl_field]['node_field']['#default_value'] = $node_field;
      }
    }
  }

  /**
   * Gets options map from table.
   *
   * @param array $field_list
   *   List of fields.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  protected function getMapFromTable(array $field_list, FormStateInterface $form_state) {
    $map = [];
    $field_row = $form_state->getValue('csl_map_table');

    foreach ($field_list as $field) {
      $csl_field = $field_row[$field]['csl_field'];
      $node_field = $field_row[$field]['node_field'];

      if ($node_field) {
        $map[$node_field][] = $csl_field;
      }
    }
    return $map;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $csl_map = $this->getMapFromTable($this->cslFields, $form_state);

    $this->config('citation_select.settings')
      ->set('reference_type_field', $form_state->getValue('reference_type_field'))
      ->save();
    $this->config('citation_select.settings')
      ->set('csl_map', $csl_map)
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Gets node fields for mapping.
   *
   * Adapted from Drupal\field_ui\Form\FieldStorageAddForm.
   *
   * @return array
   *   Map of options.
   */
  protected function getFields() {
    $options = [];
    $options['title'] = $this->t('Custom: Title');
    $options['current url'] = $this->t('Custom: Page URL');

    // Load the field_storages and build the list of options.
    $field_types = $this->fieldTypePluginManager->getDefinitions();
    foreach ($this->entityFieldManager->getFieldStorageDefinitions('node') as $field_name => $field_storage) {
      // Do not show:
      // - non-configurable field storages,
      // - locked field storages,
      // - field storages that should not be added via user interface,.
      $field_type = $field_storage->getType();
      if ($field_storage instanceof FieldStorageConfigInterface
        && !$field_storage->isLocked()
        && empty($field_types[$field_type]['no_ui'])) {
        $options[$field_name] = $this->t('@type: @field', [
          '@type' => $field_types[$field_type]['label'],
          '@field' => $field_name,
        ]);
      }
    }
    asort($options);

    return $options;

    /*
    $entity_types_map = $this->entityFieldManager->getFieldMap();

    $data = [];
    foreach ($entity_types_map as $field_array) {
    foreach ($field_array as $field => $field_data) {
    $data[$field] = $field;
    }
    }
    return $data;
    }
     */
  }

}
