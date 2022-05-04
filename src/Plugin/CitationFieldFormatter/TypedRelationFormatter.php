<?php

namespace Drupal\citation_select\Plugin\CitationFieldFormatter;

use Drupal\citation_select\CitationFieldFormatterBase;

/**
 * Plugin to format typed relation field type.
 *
 * @CitationFieldFormatter(
 *    id = "typed_relation",
 *    field_type = "typed_relation",
 * )
 */
class TypedRelationFormatter extends CitationFieldFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function formatMultiple($node, $node_field, $csl_fields) {
    $iterator = $node->get($node_field)->getIterator();

    $data = [];
    while ($iterator->valid()) {
      $current = $iterator->current();

      $rel_type = $current->get('rel_type')->getString();
      $rel_name = $this->getTypedRelationsMap($node_field)[$rel_type];

      $entity = $current->get('entity')->getTarget()->getValue();
      $value = $entity->getName();

      if (isset($rel_name) && in_array($rel_name, array_keys($csl_fields))) {
        switch ($csl_fields[$rel_name]) {
          case 'person':
            // islandora-specifific linked agents.
            if ($node_field == 'field_linked_agent') {
              $name_type = $entity->bundle();
              $data[$rel_name][] = ($name_type == 'person') ? $this->convertName($value) : $value;
            }
            else {
              $data[$rel_name][] = $this->convertName($value);
            }
            break;

          default:
            $data[$rel_name] = $value;
            break;
        }
      }
      $iterator->next();
    }
    return $data;
  }

  /**
   * Gets map of typed relation.
   *
   * @param string $node_field
   *   Node field to retrieve map from.
   *
   * @return array
   *   Mapping of typed relation name to corresponding CSL-JSON field.
   *
   * @todo make configurable?
   */
  protected function getTypedRelationsMap($node_field) {
    return [
      'relators:aut' => 'author',
      'relators:ctb' => 'contributor',
      'relators:edt' => 'editor',
      'relators:pbl' => 'publisher',
    ];
  }

}
