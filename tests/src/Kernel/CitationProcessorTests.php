<?php

namespace Drupal\Tests\citation_select\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\citation_select\CitationProcessorService;
use Drupal\citation_select\CitationFieldFormatterPluginManager;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\bibcite\HumanNameParser;

/**
 * Tests citation processor service.
 *
 * @group citation_select
 */
class CitationProcessorTests extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'citation_select',
    'taxonomy',
    'field',
    'node',
    'filter',
    'user',
    'system',
    'text',
    'bibcite'
  ];

  protected $citation_processor;
  
  protected $config_factory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');

    $this->installConfig('citation_select');

    $vocabulary = Vocabulary::create([
      'name' => 'term1',
      'vid' => 'term1',
    ]);
    $vocabulary->save();

    $node_type = NodeType::create([
      'type' => 'repository_object',
      'name' => 'Repository object',
      'description' => "Repository object for testing.",
    ]);
    $node_type->save();

    // unlimited => true
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'text_field',
      'entity_type' => 'node',
      'type' => 'text',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_name' => 'text_field',
      'entity_type' => 'node',
      'bundle' => 'repository_object',
      'label' => 'Test text field',
    ]);
    $field->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'text_date_field',
      'entity_type' => 'node',
      'type' => 'text',
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_name' => 'text_date_field',
      'entity_type' => 'node',
      'bundle' => 'repository_object',
      'label' => 'Test date field',
    ]);
    $field->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'entity_reference_field',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'taxonomy_term',
      ],
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_name' => 'entity_reference_field',
      'entity_type' => 'node',
      'bundle' => 'repository_object',
      'label' => 'Test taxonomy field',
    ]);
    $field->save();

    $this->config_factory = $this->container->get('config.factory');
    $this->citation_processor = $this->container->get('citation_select.citation_processor');

    $human_parser_mock = $this->getMockBuilder(HumanNameParser::class)
      ->disableOriginalConstructor()
      ->getMock();
    $human_parser_mock->expects($this->any())
      ->method('parse')
      ->will($this->returnCallback(
        function($x) {
          if ($x == 'John') return ['first_name' => 'John'];
          if ($x == 'John Smith') return ['first_name' => 'John', 'last_name' => 'Smith'];
          if ($x == 'Jane Smith') return ['first_name' => 'Jane', 'last_name' => 'Smith'];
        }
      ));

    $this->container->set('bibcite.human_name_parser', $human_parser_mock);
  }

  /**
   * Test reference type is correct.
   */
  public function testReferenceType() {
    $this->config_factory->getEditable('citation_select.settings')
    ->set('csl_map', [])
    ->save();
    // no type set
    $obj = Node::create([
      'type' => 'repository_object',
      'title' => 'Title',
      'text_field' => 'book',
      'nid' => 11,
    ]);
    $obj->save();
    $citation_array = $this->citation_processor->getCitationArray(11);
    $this->assertEquals('document', $citation_array['type']);

    $this->config_factory->getEditable('citation_select.settings')
    ->set(
      'csl_map',
      [
        'text_field' => [
          'type',
        ],
      ])
    ->save();

    // no mapping: valid
    $obj = Node::create([
      'type' => 'repository_object',
      'title' => 'Title',
      'text_field' => 'book',
      'nid' => 10,
    ]);
    $obj->save();
    $citation_array = $this->citation_processor->getCitationArray(10);
    $this->assertEquals('book', $citation_array['type']);

    // tests using mapping
    $this->config_factory->getEditable('citation_select.settings')
    ->set(
      'reference_type_field_map',
      [
        'paged content' => 'book'
      ])
    ->save();  

    // type invalid
    $obj = Node::create([
      'type' => 'repository_object',
      'title' => 'Title',
      'text_field' => 'abcdef',
      'nid' => 5,
    ]);
    $obj->save();
    $citation_array = $this->citation_processor->getCitationArray(5);
    $this->assertEquals('document', $citation_array['type']);

    // type valid
    $obj = Node::create([
      'type' => 'repository_object',
      'title' => 'Title',
      'text_field' => 'book',
      'nid' => 6,
    ]);
    $obj->save();
    $citation_array = $this->citation_processor->getCitationArray(6);
    $this->assertEquals('book', $citation_array['type']);

    // map type
    $obj = Node::create([
      'type' => 'repository_object',
      'title' => 'Title',
      'text_field' => 'Paged Content',
      'nid' => 7,
    ]);
    $obj->save();
    $citation_array = $this->citation_processor->getCitationArray(7);
    $this->assertEquals('book', $citation_array['type']);
  }

  /**
   * Test formatting.
   */
  public function testFormatting() {
    $this->config_factory->getEditable('citation_select.settings')
      ->set(
        'csl_map',
        [
          'title' => [
            'title',
          ], 
          'text_field' => [
            'author',
            'publisher',
          ],
          'text_date_field' => [
            'issued',
          ],
          'entity_reference_field' => [
            'genre',
          ],
          'fake_field' => [
            'note',
          ]
        ])
      ->save();

    $term = Term::create([
      'name' => 'book',
      'vid' => 'term1',
      'tid' => 1,
    ]);
    $term->save();
    $obj = Node::create([
      'type' => 'repository_object',
      'title' => 'Title',
      'text_field' => ['John Smith', 'Jane Smith'],
      'text_date_field' => '2022/01/01',
      'entity_reference_field' => [
        ['target_id' => 1],
      ],
      'nid' => 12,
    ]);
    $obj->save();
    $citation_array = $this->citation_processor->getCitationArray(12);
    $this->assertEquals(
      [
        'author' => [
          [
            'given' => 'John',
            'family' => 'Smith',
          ],
          [
            'given' => 'Jane',
            'family' => 'Smith',
          ],
        ],
        'title' => 'Title',
        'type' => 'document',
        'issued' => [
          'date-parts' => [
            [
              2022,
              01,
              01,
            ],
          ],
        ],
        'genre' => 'book',
        'publisher' => 'John Smith',
      ],
      $citation_array
    );
  }

}
