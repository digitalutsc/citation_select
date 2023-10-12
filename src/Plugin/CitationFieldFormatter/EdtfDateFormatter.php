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

    // The parser may return either an EDTF Set or an ExtDate object.
    if (method_exists($edtf_value, 'getDates')) {
      // Parser returned a Set.
      $edtf_dates = $edtf_value = $edtf_value->getDates();
      $date_parts = [
        $edtf_dates[0]->getYear(),
        $edtf_dates[0]->getMonth(),
        $edtf_dates[0]->getDay(),
      ];
    }
    else {
      // Parser returned an ExtDate object.
      $date_parts = [
        $edtf_value->getYear(),
        $edtf_value->getMonth(),
        $edtf_value->getDay(),
      ];
    }

    return [
      'date-parts' => [$date_parts],
    ];
  }

}
