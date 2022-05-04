<?php

namespace Drupal\citation_select\Plugin\CitationFieldFormatter;

use EDTF\EdtfFactory;
use Drupal\citation_select\CitationFieldFormatterBase;

/**
 * Plugin to format edtf field type.
 *
 * @CitationFieldFormatter(
 *    id = "edtf",
 *    field_type = "edtf",
 * )
 */
class EdtfDateFormatter extends CitationFieldFormatterBase {

  /**
   * {@inheritdoc}
   */
  protected function parseDate($string) {
    $parser = EdtfFactory::newParser();
    $edtf_value = $parser->parse($string)->getEdtfValue();
    $date_parts = [
      $edtf_value->getYear(),
      $edtf_value->getMonth(),
      $edtf_value->getDay(),
    ];

    return [
      'date-parts' => [$date_parts],
    ];
  }

}
