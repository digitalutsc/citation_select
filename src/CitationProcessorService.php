<?php

namespace Drupal\citation_select;

use Drupal\node\Entity\Node;

/**
 * Service to format information from nodes to CSL displays.
 */
class CitationProcessorService implements CitationProcessorServiceInterface {

  /**
   * Plugin manager.
   *
   * @var Drupal\citation_select\CitationFieldFormatterInterface
   */
  protected $citationFieldFormatterManager;

  /**
   * Config factory service.
   *
   * @var Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Date formatter service.
   *
   * @var Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Entity type manager service.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($citationFieldFormatterManager, $configFactory, $dateFormatter, $entityTypeManager) {
    $this->citationFieldFormatterManager = $citationFieldFormatterManager;
    $this->configFactory = $configFactory;
    $this->dateFormatter = $dateFormatter;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Gets field map from settings.
   *
   * @return array
   *   Mapping of CSL-JSON fields to node fields for citations
   */
  protected function getFieldMap() {
    $config = $this->configFactory->get('citation_select.settings');
    return $config->get('csl_map');
  }

  /**
   * {@inheritdoc}
   *
   * @todo make default configurable
   */
  public function getCitationArray($nid) {
    $node = $this->entityTypeManager->getStorage('node')->load($nid);

    $data = ['type' => $this->getCitationType($node)];

    // Get plugin definitions map.
    $plugin_map = [];
    $plugin_definitions = $this->citationFieldFormatterManager->getDefinitions();
    foreach ($plugin_definitions as $plugin_id => $plugin_definition) {
      $field_type = $plugin_definition['field_type'];
      $plugin_map[$field_type] = $plugin_id;
    }
    // Get format array.
    foreach ($this->getFieldMap() as $node_field => $csl_fields) {
      // Mapping for formatter.
      $csl_map = [];
      foreach ($csl_fields as $csl_field) {
        $csl_map[$csl_field] = $this->getCslType($csl_field);
      }

      $field_type = $this->getFieldType($node, $node_field);
      if (isset($plugin_map[$field_type])) {
        $plugin = $this->citationFieldFormatterManager->createInstance($plugin_map[$field_type]);
        // Default.
      }
      else {
        $plugin = $this->citationFieldFormatterManager->createInstance('default');
      }
      $formatted = $plugin->formatMultiple($node, $node_field, $csl_map);
      if ($formatted != []) {
        $data = array_merge($data, $formatted);
      }
    }
    return $data;
  }

  /**
   * Gets field type of a Drupal node.
   *
   * @param \Drupal\node\Entity\Node $node
   *   JSON-CSL Node to get field type of.
   * @param string $node_field
   *   Name of node field to get type of.
   *
   * @return string
   *   Field type of $node_field from $node
   */
  protected function getFieldType(Node $node, $node_field) {
    $field_type = NULL;
    if ($node->hasField($node_field)) {
      $field_definition = $node->get($node_field)->getFieldDefinition();
      if ($field_definition != NULL) {
        $field_type = $field_definition->getType();
      }
    }
    return $field_type;

  }

  /**
   * Gets 'type' of CSL-JSON field (e.g. person, date, standard)
   *
   * @param string $csl_field
   *   JSON-CSL field to get type of.
   *
   * @return string
   *   'type' of CSL-JSON field (person, date, or standard)
   */
  protected function getCslType($csl_field) {
    $person_fields = [
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
    $date_fields = [
      "accessed",
      "container",
      "event-date",
      "issued",
      "original-date",
      "submitted",
    ];

    if (in_array($csl_field, $person_fields)) {
      return "person";
    }
    elseif (in_array($csl_field, $date_fields)) {
      return "date";
    }
    else {
      return "standard";
    }
  }

  /**
   * Gets citation type from settings.
   *
   * @param \Drupal\node\Entity\Node $node
   *   Node to get citation type of.
   *
   * @return string
   *   Type of citation (e.g. book).
   */
  protected function getCitationType(Node $node) {
    $config = $this->configFactory->get('citation_select.settings');
    $field = $config->get('reference_type_field');
    $field_map = $config->get('reference_type_field_map');

    if ($node->hasField($field)) {
      $reference_type = $node->get($field)->referencedEntities()[0];
      if ($reference_type != NULL) {
        $reference_type = strtolower($reference_type->getName());
        // try to do mapping from value
        if (array_key_exists($reference_type, $field_map)) {
          return $field_map[$reference_type];
        } // if there's no map, check if it's valid and return
        else if ($this->isValidType($reference_type)) {
          return $reference_type;
        }
      }
    }
    // otherwise, set to 'document'
    return 'document';
  }

  /**
   * Retrieves current time and converts it to CSL-JSON format.
   *
   * @return array
   *   current date converted to CSL-JSON format
   */
  protected function getNow() {
    $accessed = date_parse($this->dateFormatter->format(time(), 'short'));

    $data = [
      'date-parts' => [[
        $accessed['year'],
        $accessed['month'],
        $accessed['day'],
      ],
      ],
    ];

    return $data;
  }

}
