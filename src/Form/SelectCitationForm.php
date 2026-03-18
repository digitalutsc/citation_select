<?php

namespace Drupal\citation_select\Form;

use Drupal\citation_select\CitationStylerInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Citation Select form.
 */
class SelectCitationForm extends FormBase {


  /**
   * Citation styler service.
   *
   * @var \Drupal\citation_select\CitationStylerInterface
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
   * @var \Drupal\citation_select\CitationProcessorService
   */
  protected $citationProcessor;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public function __construct(CitationStylerInterface $styler, Token $token_service, $citation_processor, RendererInterface $renderer) {
    $this->styler = $styler;
    $this->tokenService = $token_service;
    $this->citationProcessor = $citation_processor;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('citation_select.citation_styler'),
      $container->get('token'),
      $container->get('citation_select.citation_processor'),
      $container->get('renderer')
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
    /** @var \Drupal\citation_select\CitationStylerInterface $styler */
    $config = $this->config('citation_select.settings');
    $show_on_load = $config->get('show_on_load');
    $default_style = $config->get('default_style');
    $citation_styler = $this->styler;
    $citation_styles = $citation_styler->getEnabledStyles();
    $csl_options = array_map(function ($cs) {
      return $this->t($cs->label());
    }, $citation_styles);

    $form['#attached']['library'][] = 'citation_select/citation_select_form';
    $form['container-citation'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['citation-container'],
      ],
    ];
    $form['container-citation']['citation-info'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['left-col'],
      ],
    ];

    // Determine which citation style should be selected by default.
    $user_selected_style = $form_state->getValue('citation_style');
    if ($show_on_load) {
      $citation_style = $user_selected_style ?? $default_style;
    }
    else {
      $citation_style = $user_selected_style ?? '';
    }

    $form['container-citation']['citation-info']['citation_style'] = [
      '#type' => 'select',
      '#options' => $csl_options,
      '#empty_option' => $this->t('- Select citation style -'),
      '#ajax' => [
        'callback' => '::getBibliography',
        'wrapper' => 'formatted-bibliography',
        'method' => 'html',
        'event' => 'change',
      ],
      '#attributes' => ['aria-label' => $this->t('Select style of citation')],
      '#theme_wrappers' => [],
      '#default_value' => $citation_style,
    ];

    $nid = $this->getNodeId();
    $form['container-citation']['citation-info']['nid'] = [
      '#type' => 'hidden',
      '#value' => $nid,
      '#theme_wrappers' => [],
    ];
    
    $form['container-citation']['citation-info']['formatted-bibliography'] = [
      '#type' => 'item',
      '#prefix' => '<div id="formatted-bibliography">',
      '#suffix' => '</div>',
      '#theme_wrappers' => [],
    ];

    if (!empty($citation_style)) {
      $citation_styler->setStyleById($citation_style);
      $langcode = $citation_styler->getLanguageCode();
      $data = $this->citationProcessor->getCitationArray($nid, $langcode);
      $this->sanitizeArray($data);
      $citation = $citation_styler->render($data);

      $form['container-citation']['citation-info']['formatted-bibliography']['#children'] = $citation . "<br>Review all citations for accuracy.";
    }

    $form['container-citation']['actions'] = [
      '#type' => 'actions',
      '#attributes' => [
        'class' => ['right-col'],
      ],
    ];
    $form['container-citation']['actions']['submit'] = [
      '#type' => 'button',
      '#value' => $this->t('Copy Citation'),
      '#attributes' => [
        'onclick' => 'return false;',
        'class' => ['clipboard-button'],
        'data-clipboard-target' => '#formatted-bibliography',
      ],
      '#attached' => [
        'library' => [
          'citation_select/clipboard_attach',
        ],
      ],
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
    return $form['container-citation']['citation-info']['formatted-bibliography'];
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
        if (!is_null($item)) {
          $data[$delta] = Xss::filter($item);
        }
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
