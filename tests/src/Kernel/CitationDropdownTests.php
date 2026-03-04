<?php
namespace Drupal\Tests\citation_select\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests translatability of dropdown option in the Select Citation Form.
 */
class CitationDropdownTests extends KernelTestBase {
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

    // clear default_style so the form does not attempt to render a citation
    // (need a real node and is failing in this kernel test)
    // next steps: migrate to a Functional test and implement more robust testing
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

    // alternate approach to do same thing as above (requires multiple assertions in best case)
    foreach ($options as $key => $label) {
      $this->assertTrue(
        $label instanceof \Drupal\Core\StringTranslation\TranslatableMarkup, 
        "'$label' is NOT translatable."
      );
    }
  }
}
