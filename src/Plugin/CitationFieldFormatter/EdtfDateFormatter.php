<?php

namespace Drupal\citation_select\Plugin\CitationFieldFormatter;

use Drupal\citation_select\CitationFieldFormatterBase;

/**
 *
 * @CitationFieldFormatter(
 *    field_type = "edtf",
 * )
 */
class EdtfDateFormatter extends CitationFieldFormatterBase {

  /**
   * {@inheritdoc}
   */
  protected function parseDate($string) {
    $parser = \EDTF\EdtfFactory::newParser();
    $edtf_value = $parser->parse($string)->getEdtfValue();
    $date_parts = [
      $edtf_value->getYear(),
      $edtf_value->getMonth(),
      $edtf_value->getDay()
    ];

    return [
      'date-parts' => [$date_parts]
    ];
  }
}
