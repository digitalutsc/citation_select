<?php

namespace Drupal\Tests\citation_select\Kernel;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests translatability of dropdown option in the Select Citation Form.
 *
 * @group citation_select
 */
class CitationDropdownTest extends KernelTestBase {
  /**
   * {@inheritdoc}
   */
  protected static $modules = ['citation_select', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['citation_select']);

    // Clear default_style and show_on_load,
    // so the form does not attempt to render a citation.
    \Drupal::configFactory()
      ->getEditable('citation_select.settings')
      ->set('default_style', '')
      ->set('show_on_load', FALSE)
      ->save();
  }

  /**
   * Tests translatability of dropdown option in the Select Citation Form.
   */
  public function testDropdownOptionsAreTranslatable() {
    // Get the form.
    $form = \Drupal::formBuilder()->getForm('Drupal\citation_select\Form\SelectCitationForm');

    // Get options.
    $options = $form['container-citation']['citation-info']['citation_style']['#options'] ?? [];

    // Check that we got the options.
    $this->assertNotEmpty($options, 'Citation style options are available.');

    // All options in the dropdowns should be translatable.
    $expected_count = count($options);
    $actual_count = 0;
    // phpcs:ignore -- Unused variable $key.
    foreach ($options as $key => $label) {
      if ($label instanceof TranslatableMarkup) {
        $actual_count++;
      }
    }

    $this->assertEquals($expected_count, $actual_count);
  }

}
