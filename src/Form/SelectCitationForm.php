<?php

namespace Drupal\citation_select\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\bibcite\CitationStylerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Utility\Token;
use Drupal\citation_select\CitationProcesserServiceInterface;
use Drupal\Component\Utility\Xss;

/**
 * Provides a Citation Select form.
 */
class SelectCitationForm extends FormBase {


  /**
   * @var \Drupal\bibcite\CitationStyler
   */
  protected $styler;

  /**
   * @var \Drupal\Core\Utility\Token
   */
  protected $token_service;

  protected $citation_processor;

  public function __construct(CitationStylerInterface $styler, Token $token_service, $citation_processor) {
    $this->styler = $styler;
    $this->token_service = $token_service;
    $this->citation_processor = $citation_processor;
  }

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
      '#title' => $this->t('Select bibliography format'),
      '#options' => $csl_options,
    ];
    $form['nid'] = [
      '#type' => 'hidden',
      '#value' => $this->getNodeID(),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'button',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => '::getBibliography',
        'wrapper' => 'formatted-bibliography',
        'method' => 'html',
      ]
    ];

    $form['formatted-bibliography'] = [
      '#type' => 'item',
      '#markup' => '<div id="formatted-bibliography"></div>',
    ];

    return $form;
  }

  /**
   * Callback for getting formatted bibliography
   */
  public function getBibliography(array $form, FormStateInterface $form_state) {
    $citation_style = $form_state->getValue('citation_style');
    $citation_styler = $this->styler;
    $citation_styler->setStyleById($citation_style);

    $nid = $form_state->getValue('nid');
    $data = $this->citation_processor->getCitationArray($nid);
    $this->sanitizeArray($data);

    $citation = $citation_styler->render($data);

    $response = [
      '#children' => $citation,
    ];

    return $response;
  }

  /**
   * Recursively sanitizes all elements of array
   */
  protected function sanitizeArray(&$data) {
    foreach ($data as $delta => $item) {
      if (is_array($item)) {
        $this->sanitizeArray($item);
      } else {
        $data[$delta] = Xss::filter($item);
      }
    }
    return;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Gets nid of current page
   */
  public function getNodeID() {
    $nid = $this->token_service->replace('[current-page:url:unaliased:args:value:1]');
    return $nid;
  }

}
