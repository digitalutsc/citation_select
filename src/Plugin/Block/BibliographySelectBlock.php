<?php

namespace Drupal\citation_select\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Utility\Token;

/**
 * Provides a block to generate citations from content.
 *
 * @Block(
 *   id = "citation_select_block",
 *   admin_label = @Translation("Citation Select Block"),
 *   category = @Translation("Citation Select")
 * )
 */
class BibliographySelectBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['content'] = [
      '#markup' => $this->t('Please check citations for accuracy before including them in your work.'),
    ];
    $build['form'] = \Drupal::formBuilder()->getForm('Drupal\citation_select\Form\SelectCitationForm');
    return $build;
  }

}
