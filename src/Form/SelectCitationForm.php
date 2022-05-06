<?php

namespace Drupal\citation_select\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\bibcite\CitationStylerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Utility\Token;
use Drupal\Component\Utility\Xss;

/**
 * Provides a Citation Select form.
 */
class SelectCitationForm extends FormBase {


  /**
   * Citation styler service.
   *
   * @var \Drupal\bibcite\CitationStyler
   */
  protected $styler;

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenService;

  /**
   * Citation processor service.
   *
   * @var Drupal\citation_select\CitationProcessorService
   */
  protected $citationProcessor;

  /**
   * {@inheritdoc}
   */
  public function __construct(CitationStylerInterface $styler, Token $token_service, $citation_processor) {
    $this->styler = $styler;
    $this->tokenService = $token_service;
    $this->citationProcessor = $citation_processor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('bibcite.citation_styler'),
      $container->get('token'),
      $container->get('citation_select.citation_processor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'citation_select_select_citation';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\bibcite\CitationStylerInterface $styler */
    $citation_styler = $this->styler;
    $citation_styles = $citation_styler->getAvailableStyles();
    $csl_options = array_map(function ($cs) {
      return $cs->label();
    }, $citation_styles);

    $form['citation_style'] = [
      '#type' => 'select',
      '#options' => $csl_options,
    ];
    $form['nid'] = [
      '#type' => 'hidden',
      '#value' => $this->getNodeId(),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'button',
      '#ajax' => [
        'callback' => '::getBibliography',
        'wrapper' => 'formatted-bibliography',
        'method' => 'html',
      ],
    ];

    $form['formatted-bibliography'] = [
      '#type' => 'item',
      '#markup' => '<div id="formatted-bibliography"></div>',
    ];

    return $form;
  }

  /**
   * Callback for getting formatted bibliography.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Render array.
   */
  public function getBibliography(array $form, FormStateInterface $form_state) {
    $citation_style = $form_state->getValue('citation_style');
    $citation_styler = $this->styler;
    $citation_styler->setStyleById($citation_style);

    $nid = $form_state->getValue('nid');
    $data = $this->citationProcessor->getCitationArray($nid);
    $this->sanitizeArray($data);

    $citation = $citation_styler->render($data);

    $response = [
      '#children' => $citation,
    ];

    return $response;
  }

  /**
   * Recursively sanitizes all elements of array.
   *
   * @param array $data
   *   Array to sanitize.
   */
  protected function sanitizeArray(array &$data) {
    foreach ($data as $delta => $item) {
      if (is_array($item)) {
        $this->sanitizeArray($item);
      }
      else {
        $data[$delta] = Xss::filter($item);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Gets nid of current page.
   *
   * @return string
   *   Node id of current page.
   */
  public function getNodeId() {
    $nid = $this->tokenService->replace('[current-page:url:unaliased:args:value:1]');
    return $nid;
  }

}
