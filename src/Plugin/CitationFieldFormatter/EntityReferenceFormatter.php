<?php

namespace Drupal\citation_select\Plugin\CitationFieldFormatter;

use Drupal\citation_select\CitationFieldFormatterBase;

/**
 *
 * @CitationFieldFormatter(
 *    field_type = "entity_reference",
 * )
 */
class EntityReferenceFormatter extends CitationFieldFormatterBase {

  /**
   * {@inheritdoc}
   */
  protected function getField($node, $field)
  {
    return $node->get($field)->referencedEntities()[0]->getName();
  }

  /**
   * {@inheritdoc}
   */
  protected function getFieldValueList($node, $field) {
    $data = array_map(
      function ($n) {
        return $n->getName();
      }, $node->get($field)->referencedEntities()
    );
    return $data;
  }
}
