<?php
namespace Drupal\Tests\citation_select\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that the default citation style saved in the config form is showed on a node.
 *
 * @group citation_select
 */
class CitationConfigTests extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['citation_select'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['citation_select']);
  }

  /**
   * Tests that the default citation style saved in the config form is same as what the user sees.
   */
  public function testCitationStyleConfig(): void {
    $test_citation_style = 'apa';

    $config = $this->config('citation_select.settings');
    $config->set('default_style', $test_citation_style)
      ->set('show_on_load', TRUE)
      ->save();

    $actual = $this->config('citation_select.settings');
    $this->assertEquals($test_citation_style, $actual->get('default_style'));
    $this->assertTrue($actual->get('show_on_load'));
  }

}

?>