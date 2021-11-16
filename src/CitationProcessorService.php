<?php

namespace Drupal\citation_select;

use Drupal\bibcite\CitationStylerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\citation_select\CitationProcessorServiceInterface;
use Drupal\Component\Utility\Xss;

/**
 * Service to format information from nodes to CSL displays
 */
class CitationProcessorService implements CitationProcessorServiceInterface {

  protected $citation_field_formatter_manager;

  public function __construct($citationFieldFormatterManager) {
    $this->citation_field_formatter_manager = $citationFieldFormatterManager;
  }

  /**
   * Gets field map from settings.
   * 
   * @return array Mapping of CSL-JSON fields to node fields for citations 
   */
  protected function getFieldMap() {
    $config = \Drupal::config('citation_select.settings');
    return $config->get('csl_map');
  }

  /**
   * {@inheritdoc}
   * @todo make default configurable
   */
  public function getCitationArray($nid) {
    $node = \Drupal\node\Entity\Node::load($nid);
    global $base_url;

    $data =['type' => $this->getCitationType($node)];

    // get plugin definitions map
    $plugin_map = [];
    $plugin_definitions = $this->citation_field_formatter_manager->getDefinitions();
    foreach ($plugin_definitions as $plugin_id => $plugin_definition) {
      $field_type = $plugin_definition['field_type'];
      $plugin_map[$field_type] = $plugin_id;
    }
    // get format array
    foreach ($this->getFieldMap() as $node_field => $csl_fields) {
      // mapping for formatter
      $csl_map = [];
      foreach ($csl_fields as $csl_field) {
        $csl_map[$csl_field] = $this->getCslType($csl_field);
      }

      $field_type = $this->getFieldType($node, $node_field);
      if (isset($plugin_map[$field_type])) {
        $plugin = $this->citation_field_formatter_manager->createInstance($plugin_map[$field_type]);
      } else { // default
        $plugin = $this->citation_field_formatter_manager->createInstance('default');
      }
      $formatted = $plugin->formatMultiple($node, $node_field, $csl_map);
      if ($formatted != array()) {
        $data = array_merge($data, $formatted);
      }
    }
    return $data;
  }

  /**
   * Gets field type of a Drupal node
   * 
   * @param $node JSON-CSL Node to get field type of
   * @param string $node_field Name of node field to get type of 
   * @return Field type of $node_field from $node
   */
  protected function getFieldType($node, $node_field) {
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
   * @param string $csl_field JSON-CSL field to get type of
   * @return string 'type' of CSL-JSON field (person, date, or standard)
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
    } else if (in_array($csl_field, $date_fields)) {
      return "date";
    } else {
      return "standard";
    }
  }

  /**
   * Gets citation type from settings
   * 
   * @param $node Node to get citation type of
   */
  protected function getCitationType($node) {
    $config = \Drupal::config('citation_select.settings');
    $field = $config->get('reference_type_field');

    if ($node->hasField($field)) {
      $reference_type = $node->get($field)->referencedEntities()[0];
      $type = $reference_type != NULL ? $reference_type->getName() : NULL;
    } else {
      $type = NULL;
    }

    return $type;
  }

  /**
   * Retrieves current time and converts it to CSL-JSON format
   * 
   * @return current date converted to CSL-JSON format
   */
  protected function getNow() {
    $accessed = date_parse(\Drupal::service('date.formatter')->format(time(), 'short'));

    $data = [
      'date-parts' => [[
        $accessed['year'],
        $accessed['month'],
        $accessed['day'],
      ]]
    ];

    return $data;
  }

}
