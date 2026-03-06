<?php

namespace Drupal\Tests\citation_select\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Test for main module functions.
 *
 * @group citation_select
 */
class CitationConfigTests extends BrowserTestBase {

    /**
     * {@inheritdoc}
     */
    protected static $modules = ['citation_select', 'node', 'block', 'system', 'user'];

    /**
     * {@inheritdoc}
     */
    protected $defaultTheme = 'stark';

    /**
     * @var \Drupal\user\UserInterface
     */
    protected $user;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void {
        parent::setUp();

        NodeType::create([
            'type' => 'islandora_object',
            'name' => 'Repository Item',
        ])->save();

        $this->user = $this->drupalCreateUser([
            'administer site configuration',
            'create islandora_object content',
            'access content',
        ]);
    }

    public function testAllCitationStylesOnPageLoad(){
        $this->drupalLogin($this->user);

        $this->drupalPlaceBlock('citation_select_block');

        $node = Node::create([
            'type' => 'islandora_object',
            'title' => 'Test Repository Item',
        ]);
        $node->save();

        // Dynamically get citation styles from config.
        $styles = \Drupal::config('citation_select.settings')->get('styles') ?: [];

        // loop over every style
        foreach ($styles as $key => $value) {
            // set default style
            $this->config('citation_select.settings')
                ->set('default_style', $key)
                ->set('show_on_load', TRUE)
                ->save();

            // verify with config and load the page
            $this->assertEquals($key, $this->config('citation_select.settings')->get('default_style'));
            $this->drupalGet($node->toUrl()->toString());

            // check that the default citation style from config is selected in the form on the node
            $this->assertSession()
                ->fieldValueEquals('edit-citation-style', $key);
            $this->assertSession()
                ->pageTextContains($value);
        }
    }
}

?>