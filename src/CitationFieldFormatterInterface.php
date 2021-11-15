<?php

namespace Drupal\citation_select;

interface CitationFieldFormatterInterface {

  public function formatMultiple($node, $node_field, $csl_fields);
}