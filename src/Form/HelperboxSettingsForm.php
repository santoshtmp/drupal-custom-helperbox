<?php

namespace Drupal\helperbox\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class HelperboxSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['helperbox.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'helperbox_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('helperbox.settings');

    // Attach JSON editor library.
    $form['#attached']['library'][] = 'helperbox/helperbox_json_editor';

    // $form['enable_helperbox'] = [
    //   '#type' => 'checkbox',
    //   '#title' => $this->t('Enable Helperbox'),
    //   '#default_value' => $config->get('enable_helperbox'),
    // ];

    $form['enable_media_custom_thumbnail'] = [
      '#type' => 'select',
      '#title' => $this->t('Enable Media custom thumbnail'),
      '#options' => [
        false => $this->t('No'),
        true => $this->t('Yes'),
      ],
      '#default_value' => $config->get('enable_media_custom_thumbnail'),
      '#description' => $this->t(
        'Enable custom thumbnails for media items. When enabled, a custom thumbnail can be applied to media types that include a field named <code>field_custom_thumbnail</code>. You can add this field from <strong>Structure → Media types → Media (Remote video / Document) → Manage fields</strong>.'
      ),
    ];

    $form['enable_unique_node_per_bundle'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable unique node/item title per content bundle'),
      '#default_value' => $config->get('enable_unique_node_per_bundle'),
      '#description' => $this->t('When enabled, only one node/item title will be allowed per content type bundle.'),
    ];

    // Field Rules Configuration
    $form['field_rules'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Field Rules'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#description' => $this->t('Configure field rules for entity types and bundles. Format: JSON'),
    ];

    $form['field_rules']['field_rules_all'] = [
      '#type' => 'textarea',
      '#title' => $this->t('All Field Rules'),
      '#default_value' => $config->get('field_rules_all') ? json_encode($config->get('field_rules_all'), JSON_PRETTY_PRINT) : '',
      '#description' => [
        '#markup' => '
          <p>Rules for field access based on entity type and bundle.</p>
          <p><strong>Example:</strong></p>
          <pre>{
  "node": {
    "article": {
      "field_access_check": {
        "field_related_countries": false
      }
    }
  },
  "paragraph": {
    "content_item": {
      "field_access_check": {
        "field_list_items": false,
        "field_highlight_text": false
      }
    }
  }
}</pre>',
      ],
      '#rows' => 10,
      '#attributes' => ['class' => ['helperbox-json-editor-field']],
    ];

    $form['field_rules']['field_rules_node'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Node Field Rules'),
      '#default_value' => $config->get('field_rules_node') ? json_encode($config->get('field_rules_node'), JSON_PRETTY_PRINT) : '',
      '#description' => [
        '#markup' => '
          <p>Field rules for specific content type and node ID. This will override the rules from <strong>All Field Rules</strong>.</p>
          <p><strong>Example Structure:</strong></p>
          <pre>{
  "content_type_...": {
    "node_id_...": [
      "field_...",
      "group_...",
      {
        "field_...": true
      },
      {
        "referenceField": {
          "field_...": {
            "field_...": false
          }
        }
      }
    ]
  }
}</pre>
          <p><strong>Real Example:</strong></p>
          <pre>{
  "article": {
    "16": [
      "group_general_section",
      {
        "field_cta_action": true
      },
      {
        "referenceField": {
          "field_content_section": {
            "field_list_items": false
          }
        }
      }
    ]
  },
  "page": {
    "15": {
      "referenceField": {
        "field_content_section": {
          "field_highlight_text": true
        }
      }
    }
  }
}</pre>',
      ],
      '#rows' => 15,
      '#attributes' => ['class' => ['helperbox-json-editor-field']],
    ];

    $form['field_rules']['field_rules_form'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Form Field Rules'),
      '#default_value' => $config->get('field_rules_form') ? json_encode($config->get('field_rules_form'), JSON_PRETTY_PRINT) : '',
      '#description' => [
        '#markup' => '
          <p>Field rules for specific form IDs.</p>
          <p><strong>Example Structure:</strong></p>
          <pre>{
  "form_id_...": {
    "field_...": true|false
  }
}</pre>
          <p><strong>Real Example:</strong></p>
          <pre>{
  "search_form": {
    "advanced": false
  }
}</pre>',
      ],
      '#rows' => 10,
      '#attributes' => ['class' => ['helperbox-json-editor-field']],
    ];

    $form['field_rules']['field_rules_max_content'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Maximum Content Nodes'),
      '#default_value' => $config->get('field_rules_max_content') ? json_encode($config->get('field_rules_max_content'), JSON_PRETTY_PRINT) : '',
      '#description' => [
        '#markup' => '
          <p>Maximum allowed nodes per content type.</p>
          <p><strong>Example Structure:</strong></p>
          <pre>{
  "content_type_bundle...": NUMBER
}</pre>
          <p><strong>Real Example:</strong></p>
          <pre>{
  "article": 4
}</pre>',
      ],
      '#rows' => 5,
      '#attributes' => ['class' => ['helperbox-json-editor-field']],
    ];

    // // CDN Options
    // // libraries from CDN
    // $cdn_config = $config->get('cdn') ?: [];

    // $form['cdn'] = [
    //   '#type' => 'fieldset',
    //   '#title' => $this->t('CDN Libraries'),
    //   '#collapsible' => TRUE,
    //   '#collapsed' => FALSE,
    //   '#tree' => TRUE,
    // ];
    // $form['cdn']['cdn_select2'] = [
    //   '#type' => 'checkbox',
    //   '#title' => $this->t('Enable select2 library'),
    //   '#default_value' => $cdn_config['cdn_select2'] ?? FALSE,
    // ];
    // $form['cdn']['cdn_lightgallery'] = [
    //   '#type' => 'checkbox',
    //   '#title' => $this->t('Enable lightgallery library'),
    //   '#default_value' => $cdn_config['cdn_lightgallery'] ?? FALSE,
    // ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Parse field rules from JSON
    $field_rules_all = $form_state->getValue('field_rules_all');
    $field_rules_node = $form_state->getValue('field_rules_node');
    $field_rules_form = $form_state->getValue('field_rules_form');
    $field_rules_max_content = $form_state->getValue('field_rules_max_content');

    $this->config('helperbox.settings')
      ->set('enable_helperbox', $form_state->getValue('enable_helperbox'))
      ->set('cdn', [
        'cdn_select2' => $form_state->getValue(['cdn', 'cdn_select2']),
        'cdn_lightgallery' => $form_state->getValue(['cdn', 'cdn_lightgallery']),
      ])
      ->set('enable_media_custom_thumbnail', $form_state->getValue('enable_media_custom_thumbnail'))
      ->set('enable_unique_node_per_bundle', $form_state->getValue('enable_unique_node_per_bundle'))
      ->set('field_rules_all', $field_rules_all ? json_decode($field_rules_all, TRUE) : [])
      ->set('field_rules_node', $field_rules_node ? json_decode($field_rules_node, TRUE) : [])
      ->set('field_rules_form', $field_rules_form ? json_decode($field_rules_form, TRUE) : [])
      ->set('field_rules_max_content', $field_rules_max_content ? json_decode($field_rules_max_content, TRUE) : [])
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validate field_rules_all JSON
    $field_rules_all = trim($form_state->getValue('field_rules_all'));
    if (!empty($field_rules_all)) {
      json_decode($field_rules_all);
      if (json_last_error() !== JSON_ERROR_NONE) {
        $form_state->setErrorByName('field_rules_all', $this->t('All Field Rules must be valid JSON. Error: @error', [
          '@error' => json_last_error_msg(),
        ]));
      }
    }

    // Validate field_rules_node JSON
    $field_rules_node = trim($form_state->getValue('field_rules_node'));
    if (!empty($field_rules_node)) {
      json_decode($field_rules_node);
      if (json_last_error() !== JSON_ERROR_NONE) {
        $form_state->setErrorByName('field_rules_node', $this->t('Node Field Rules must be valid JSON. Error: @error', [
          '@error' => json_last_error_msg(),
        ]));
      }
    }

    // Validate field_rules_form JSON
    $field_rules_form = trim($form_state->getValue('field_rules_form'));
    if (!empty($field_rules_form)) {
      json_decode($field_rules_form);
      if (json_last_error() !== JSON_ERROR_NONE) {
        $form_state->setErrorByName('field_rules_form', $this->t('Form Field Rules must be valid JSON. Error: @error', [
          '@error' => json_last_error_msg(),
        ]));
      }
    }

    // Validate field_rules_max_content JSON
    $field_rules_max_content = trim($form_state->getValue('field_rules_max_content'));
    if (!empty($field_rules_max_content)) {
      json_decode($field_rules_max_content);
      if (json_last_error() !== JSON_ERROR_NONE) {
        $form_state->setErrorByName('field_rules_max_content', $this->t('Maximum Content Nodes must be valid JSON. Error: @error', [
          '@error' => json_last_error_msg(),
        ]));
      }
    }
  }
}
