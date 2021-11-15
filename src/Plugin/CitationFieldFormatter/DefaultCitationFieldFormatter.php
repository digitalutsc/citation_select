<?php

namespace Drupal\citation_select\Plugin\CitationFieldFormatter;

use Drupal\citation_select\CitationFieldFormatterBase;

/**
 *
 * @CitationFieldFormatter(
 *    id = "default",
 *    field_type = "default",
 * )
 */
class DefaultCitationFieldFormatter extends CitationFieldFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function formatMultiple($node, $node_field, $csl_fields) {
    if (!in_array($node_field, ['title', 'current url']) &&
      (!$node->hasField($node_field) || $node->get($node_field)->isEmpty())) {
      return [];
    }

    $data = [];
    foreach ($csl_fields as $csl_field => $csl_type) {
      if ($csl_type == 'person') {
        $data[$csl_field] = $this->formatNames($this->getFieldValueList($node, $node_field));
      } else if ($csl_type == 'date') {
        $data[$csl_field] = $this->parseDate($this->getField($node, $node_field));
      } else {
        $data[$csl_field] = $this->getField($node, $node_field);
      }
   }
   return $data;
  }

  /**
   * {@inheritdoc}
   */
  protected function getField($node, $field)
  {
    switch ($field) {
      case 'title':
        return $node->getTitle();
        break;
      case 'current url':
        global $base_url;
        return $base_url . $node->toUrl()->toString();
        break;
      default:
        return parent::getField($node, $field);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getFieldValueList($node, $field)
  {
    switch ($field) {
      case 'title':
        return array($node->getTitle());
        break;
      case 'current url':
        global $base_url;
        return array($base_url . $node->toUrl()->toString());
        break;
      default:
        return parent::getFieldValueList($node, $field);
    }
  }
}
