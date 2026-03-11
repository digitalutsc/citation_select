<?php
namespace Drupal\Tests\citation_select\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests translatability of dropdown option in the Select Citation Form.
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

    // clear default_style and show_on_load so the form does not attempt to render a citation
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
    // get the form
    $form = \Drupal::formBuilder()->getForm('Drupal\citation_select\Form\SelectCitationForm');

    // get options
    $options = $form['container-citation']['citation-info']['citation_style']['#options'] ?? [];

    // check that we got the options
    $this->assertNotEmpty($options, 'Citation style options are available.');

    // all options in the dropdowns should be translatable
    $expected_count = count($options);
    $actual_count = 0;
    foreach ($options as $key => $label) {
      if ($label instanceof \Drupal\Core\StringTranslation\TranslatableMarkup){
        $actual_count++;
      }
    }

    $this->assertEquals($expected_count, $actual_count);
  }
}
