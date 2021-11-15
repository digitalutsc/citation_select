<?php

namespace Drupal\citation_select\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * @Annotation
 */
class CitationFieldFormatter extends Plugin {

  /**
   * Field type the formatter supports.
   *
   * @var string
   */
  public $field_type;

}
