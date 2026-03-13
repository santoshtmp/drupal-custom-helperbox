<?php

namespace Drupal\helperbox\Plugin\views\field;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\Attribute\ViewsField;
use Drupal\node\Entity\Node;
use Drupal\Component\Utility\Crypt;

/**
 * Provides a custom Views field for rendering Call-To-Action (CTA) buttons.
 *
 * This field plugin allows site builders to add configurable CTA buttons to
 * Views. It supports both internal and external links, matching the functionality
 * of Drupal core's Link field type.
 *
 * Features:
 * - Internal paths (e.g., /about, /node/1, ?query=1, #fragment)
 * - External URLs (e.g., https://example.com, http://..., mailto:, tel:)
 * - Special values: <front>, <nolink>, <button>, <current_node>
 * - Entity autocomplete for nodes (displays as "Node Title (nid)")
 * - Configurable button styles (primary/secondary)
 *
 * Usage:
 * @code
 * // In Views UI, add the "CTA Button" field to your view.
 * // Configure the label, URL, and button type.
 * @endcode
 *
 * @see \Drupal\Core\Field\Plugin\Field\FieldType\LinkItem
 * @see \Drupal\Core\Field\Plugin\Field\FieldWidget\LinkWidget
 *
 * @ingroup views_field_handlers
 */
#[ViewsField("helperbox_add_cta")]
class AddCTA extends FieldPluginBase {

  /**
   * {@inheritdoc}
   *
   * This method is intentionally empty because the CTA field does not require
   * any additional database columns. All data is stored in the view's
   * configuration.
   */
  public function query() {
    // No query alteration needed.
  }

  /**
   * {@inheritdoc}
   *
   * Defines the available options for the CTA field. These options are stored
   * in the view configuration and can be configured through the Views UI.
   *
   * @return array
   *   An associative array of option definitions with the following structure:
   *   - cta_label: string The text to display on the CTA button.
   *   - cta_url: string The URI or URL for the CTA button link.
   *   - cta_type: string The button style (primary or secondary).
   *   - cta_target: string The link target (_self, _blank, _parent, _top).
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    $options['cta_type'] = ['default' => 'primary'];
    $options['use_link_field'] = ['default' => FALSE];
    $options['link_field'] = ['default' => ''];
    $options['cta_label'] = [
      'default' => '',
      'translatable' => TRUE,
    ];
    $options['cta_url'] = ['default' => ''];
    $options['cta_target'] = ['default' => ''];
    $options['cta_enable_extra_query_params'] = ['default' => FALSE];
    $options['cta_query_params'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   *
   * Builds the configuration form for the CTA field in Views UI. This form
   * allows site builders to configure the CTA button's label, URL, and style.
   *
   * The URL field supports multiple input formats:
   * - Node autocomplete: Type a node title to get suggestions
   * - Internal paths: /about, /contact, /node/1
   * - Query strings: ?query=1&param=2
   * - Fragments: #section-id
   * - External URLs: https://example.com
   * - Special values: <front>, <nolink>, <button>
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\Core\Entity\Element\EntityAutocomplete
   * @see \Drupal\Core\Field\Plugin\Field\FieldWidget\LinkWidget
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Create a fieldset to group all CTA settings.
    $form['cta_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('CTA Settings'),
      '#open' => TRUE,
      '#weight' => 0,
    ];

    // CTA Type selection (primary or secondary button style).
    $form['cta_type'] = [
      '#type' => 'select',
      '#title' => $this->t('CTA Type'),
      '#default_value' => $this->options['cta_type'],
      '#options' => [
        'primary' => $this->t('Primary Button'),
        'secondary' => $this->t('Secondary Button'),
      ],
      '#description' => $this->t('Select the button style.'),
      '#fieldset' => 'cta_settings',
    ];

    // Option to use entity's link field.
    $form['use_link_field'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use existing link field'),
      '#default_value' => $this->options['use_link_field'],
      '#description' => $this->t('Check this to use a link field value from the current entity.'),
      '#fieldset' => 'cta_settings',
    ];

    // Link field selector (shown when use_link_field is enabled).
    $bundle_info = $this->getViewBundleInfo();
    $form['link_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link field'),
      '#default_value' => $this->options['link_field'],
      '#description' => $this->t('Enter the link field name from the current entity to use for the CTA URL (e.g., field_cta_link). <br>This will only take first link value.<br>') . ($bundle_info ? ' <br>' . $bundle_info : ''),
      '#fieldset' => 'cta_settings',
      '#states' => [
        'visible' => [
          ':input[name="options[use_link_field]"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="options[use_link_field]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // CTA Label text input.
    $form['cta_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CTA Label'),
      '#default_value' => $this->options['cta_label'],
      '#description' => $this->t('Text to display on the CTA button.'),
      '#fieldset' => 'cta_settings',
      '#states' => [
        'visible' => [
          ':input[name="options[use_link_field]"]' => ['checked' => FALSE],
        ],
        'required' => [
          ':input[name="options[use_link_field]"]' => ['checked' => FALSE],
        ],
      ]
    ];

    // Convert stored URI to user-friendly display value.
    // This matches the behavior of Drupal core's LinkWidget.
    $default_input = $this->getDisplayUriFromValue($this->options['cta_url'] ?? '');

    // Generate a secure key for entity autocomplete settings.
    // This follows the same pattern as core's EntityAutocompleteElement.
    $selection_settings = [];
    $selection_settings_key = Crypt::hmacBase64(
      serialize($selection_settings) . 'node' . 'default:node',
      \Drupal::service('settings')->getHashSalt()
    );

    // Store selection settings in key-value store for the autocomplete controller.
    // The autocomplete controller retrieves settings using this key.
    \Drupal::keyValue('entity_autocomplete')->set($selection_settings_key, $selection_settings);

    // CTA URL input with entity autocomplete support.
    $form['cta_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CTA Link'),
      '#description' => $this->t('Start typing to search content (autocomplete for nodes). Or enter: internal path (%add-node, /about), external URL (%url), or special: %front (homepage), %nolink (text only), %button (styled text only), %current_node (current entity URL).', [
        '%front' => '<front>',
        '%add-node' => '/node/add',
        '%url' => 'https://example.com',
        '%nolink' => '<nolink>',
        '%button' => '<button>',
        '%current_node' => '<current_node>',
      ]),
      '#default_value' => $default_input,
      '#element_validate' => [[static::class, 'validateCtaUrl']],
      '#autocomplete_route_name' => 'system.entity_autocomplete',
      '#autocomplete_route_parameters' => [
        'target_type' => 'node',
        'selection_handler' => 'default:node',
        'selection_settings_key' => $selection_settings_key,
      ],
      '#attributes' => [
        // Disable autocomplete when input starts with /, #, or ? to allow
        // manual path entry.
        'data-autocomplete-first-character-denylist' => '/#?',
      ],
      '#fieldset' => 'cta_settings',
      '#states' => [
        'visible' => [
          ':input[name="options[use_link_field]"]' => ['checked' => FALSE],
        ],
        'required' => [
          ':input[name="options[use_link_field]"]' => ['checked' => FALSE],
        ],
      ]
    ];

    // CTA Link Target selection.
    $form['cta_target'] = [
      '#type' => 'select',
      '#title' => $this->t('Link Target'),
      '#default_value' => $this->options['cta_target'],
      '#options' => [
        '' => $this->t('- None -'),
        '_self' => $this->t('Same window/tab (_self)'),
        '_blank' => $this->t('New window/tab (_blank)'),
        '_parent' => $this->t('Parent frame (_parent)'),
        '_top' => $this->t('Full body window (_top)'),
      ],
      '#description' => $this->t('Select where to open the link.'),
      '#fieldset' => 'cta_settings',
    ];

    // Enable Extra Query Parameters checkbox.
    $form['cta_enable_extra_query_params'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Extra Query Parameters'),
      '#default_value' => $this->options['cta_enable_extra_query_params'],
      '#description' => $this->t('Check this to enable additional query parameters for the CTA link.'),
      '#fieldset' => 'cta_settings',
    ];

    // Query params textarea (key=value pairs, one per line).
    $form['cta_query_params'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Query Parameters'),
      '#default_value' => $this->options['cta_query_params'],
      '#description' => $this->t('Enter query parameters, one per line in the format: key=value. For example:<br>filter=active<br>sort=date<br>limit=10<br><br>Available placeholders for dynamic values:<br>{id} - Entity ID<br>{bundle} - Entity bundle/type<br>{entity_type} - Entity type<br>{title} - Entity title'),
      '#fieldset' => 'cta_settings',
      '#rows' => 5,
      '#attributes' => [
        'autocomplete' => 'off',
        'autocorrect' => 'off',
        'autocapitalize' => 'off',
        'spellcheck' => 'false',
      ],
      '#states' => [
        'visible' => [
          // ':input[name="options[use_link_field]"]' => ['checked' => FALSE],
          ':input[name="options[cta_enable_extra_query_params]"]' => ['checked' => TRUE],
        ],
      ],
    ];
  }

  /**
   * Gets bundle information for the current view.
   *
   * @return string
   *   A formatted string with bundle information, or empty string.
   */
  protected function getViewBundleInfo() {
    // Get entity type.
    $entity_type = $this->view->getBaseEntityType();
    if (!$entity_type) {
      return '';
    }

    $entity_type_id = $entity_type->id();
    $entity_type_label = $entity_type->getLabel();

    // Get the bundle filter handler.
    $bundle_filter = $this->view->display_handler->getHandler('filter', 'type');
    $bundles = [];

    // If filter exists and has values, use filtered bundles.
    if ($bundle_filter && !empty($bundle_filter->value)) {
      $bundles = is_array($bundle_filter->value) ? $bundle_filter->value : [$bundle_filter->value];
    } else {
      // No filter: Get all available bundles for this entity type.
      $bundle_storage_map = [
        'node' => 'node_type',
        'block_content' => 'block_content_type',
        'taxonomy_term' => 'vocabulary',
        'media' => 'media_type',
      ];

      if (isset($bundle_storage_map[$entity_type_id])) {
        $bundle_entities = \Drupal::entityTypeManager()
          ->getStorage($bundle_storage_map[$entity_type_id])
          ->loadMultiple();
        $bundles = array_keys($bundle_entities);
      }
    }

    if (empty($bundles)) {
      return '';
    }

    // Load bundle labels.
    $bundle_storage_map = [
      'node' => 'node_type',
      'block_content' => 'block_content_type',
      'taxonomy_term' => 'vocabulary',
      'media' => 'media_type',
    ];

    if (isset($bundle_storage_map[$entity_type_id])) {
      $bundle_entities = \Drupal::entityTypeManager()
        ->getStorage($bundle_storage_map[$entity_type_id])
        ->loadMultiple($bundles);
      $bundle_labels = array_map(fn($b) => $b->label(), $bundle_entities);
    } else {
      $bundle_labels = $bundles;
    }

    if (empty($bundle_labels)) {
      return '';
    }

    // Indicate if all bundles are included.
    $prefix = $bundle_filter && !empty($bundle_filter->value) ? '' : '(All) ';

    return $this->t('@prefix@entity_type bundle: @bundles', [
      '@prefix' => $prefix,
      '@entity_type' => $entity_type_label,
      '@bundles' => implode(', ', $bundle_labels),
    ]);
  }

  /**
   * Builds the textarea value from query params array.
   *
   * @param array $query_params
   *   The query parameters array.
   *
   * @return string
   *   The textarea value with key=value pairs, one per line.
   */
  protected function buildQueryParamsTextarea(array $query_params) {
    if (empty($query_params)) {
      return '';
    }

    $lines = [];
    foreach ($query_params as $param) {
      if (is_array($param) && isset($param['key']) && isset($param['value'])) {
        $lines[] = $param['key'] . '=' . $param['value'];
      }
    }

    return implode("\n", $lines);
  }

  /**
   * Validates and normalizes the CTA URL input to a proper URI format.
   *
   * This validation callback is triggered when the form is submitted. It
   * converts the user-entered string into a standardized URI format that
   * can be stored and later processed.
   *
   * Validation rules:
   * - Entity autocomplete selections are converted to entity:node/NID
   * - Special values (<nolink>, <button>) are converted to route: scheme
   * - Internal paths are prefixed with internal: scheme
   * - External URLs are kept as-is
   * - Internal paths must start with /, ?, #, or be a special value
   *
   * @param array $element
   *   The form element being validated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $form
   *   The complete form structure.
   *
   * @see \Drupal\Core\Entity\Element\EntityAutocomplete::validateEntityAutocomplete()
   * @see \Drupal\Core\Field\Plugin\Field\FieldWidget\LinkWidget::validateUriElement()
   */
  public static function validateCtaUrl(&$element, FormStateInterface $form_state, $form) {
    // Convert user input to standardized URI format.
    $uri = static::getUserEnteredStringAsUri($element['#value']);
    $form_state->setValueForElement($element, $uri);

    // Validate that entity autocomplete selections are valid.
    // If the input contains parentheses (e.g., "Home (1)"), ensure we can
    // extract a valid entity ID.
    if (strpos($element['#value'], '(') !== FALSE && strpos($element['#value'], ')') !== FALSE) {
      $extracted_id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($element['#value']);
      if ($extracted_id === NULL) {
        $form_state->setError($element, t('Invalid selection. Select from suggestions or enter a valid path/URL.'));
        return;
      }
    }

    // Enforce path-like input for internal URIs.
    // Internal paths must start with /, ?, or #, or be a special value.
    if (
      parse_url($uri, PHP_URL_SCHEME) === 'internal'
      && !in_array($element['#value'][0] ?? '', ['/', '?', '#'], TRUE)
      && !str_starts_with($element['#value'], '<front>')
      && !in_array($element['#value'], ['<nolink>', '<button>', '<current_node>'], TRUE)
    ) {
      $form_state->setError($element, t('Internal paths must start with /, ?, #, or be a special value like <front>, <nolink>, <button>, <current_node>.'));
    }
  }

  /**
   * Converts a stored URI to a user-friendly display string.
   *
   * This method is the inverse of getUserEnteredStringAsUri(). It transforms
   * internal URI representations back into human-readable strings for display
   * in the form input field.
   *
   * Transformations:
   * - internal:/about → /about
   * - internal:/ → <front>
   * - entity:node/1 → "Node Title (1)"
   * - route:<nolink> → <nolink>
   * - route:<button> → <button>
   * - route:<current_node> → <current_node>
   * - https://example.com → https://example.com (unchanged)
   *
   * @param string $uri
   *   The URI to convert. May have schemes like internal:, entity:, or route:.
   *
   * @return string
   *   The user-friendly display string. Returns the original URI if no
   *   transformation is applicable.
   *
   * @see \Drupal\Core\Field\Plugin\Field\FieldWidget\LinkWidget::getUriAsDisplayableString()
   */
  protected function getDisplayUriFromValue(string $uri): string {
    if (empty($uri)) {
      return '';
    }

    // Extract the URI scheme to determine the type of link.
    $scheme = parse_url($uri, PHP_URL_SCHEME);

    // By default, return the URI as-is (for external URLs or unknown schemes).
    $displayable_string = $uri;

    // Handle 'internal:' scheme - strip the scheme for display.
    if ($scheme === 'internal') {
      $uri_reference = explode(':', $uri, 2)[1];

      // Special case: front page is displayed as <front>.
      $path = parse_url($uri, PHP_URL_PATH);
      if ($path === '/') {
        $uri_reference = '<front>' . substr($uri_reference, 1);
      }

      $displayable_string = $uri_reference;
    }
    // Handle 'entity:' scheme - display as "Entity Label (ID)".
    elseif ($scheme === 'entity') {
      [$entity_type, $entity_id] = explode('/', substr($uri, 7), 2);
      if ($entity_type == 'node' && $node = Node::load($entity_id)) {
        $displayable_string = $node->label() . ' (' . $entity_id . ')';
      }
    }
    // Handle 'route:' scheme - strip the scheme for display.
    elseif ($scheme === 'route') {
      $displayable_string = ltrim($uri, 'route:');
    }

    return $displayable_string;
  }

  /**
   * Converts a user-entered string to a standardized URI format.
   *
   * This method is the inverse of getDisplayUriFromValue(). It transforms
   * user input from the form into a standardized URI format that can be
   * stored in the configuration.
   *
   * Transformations:
   * - "Node Title (1)" → entity:node/1
   * - "/about" → internal:/about
   * - "<front>" → internal:/
   * - "<nolink>" → route:<nolink>
   * - "<button>" → route:<button>
   * - "<current_node>" → route:<current_node>
   * - "https://example.com" → https://example.com (unchanged)
   *
   * @param string $string
   *   The user-entered string from the form input.
   *
   * @return string
   *   The standardized URI with appropriate scheme.
   *
   * @see \Drupal\Core\Field\Plugin\Field\FieldWidget\LinkWidget::getUserEnteredStringAsUri()
   * @see \Drupal\Core\Entity\Element\EntityAutocomplete::extractEntityIdFromAutocompleteInput()
   */
  protected static function getUserEnteredStringAsUri($string) {
    // By default, assume the entered string is already a URI.
    $uri = trim($string);

    // Check if the input is an entity autocomplete string (e.g., "Home (1)").
    // If so, extract the entity ID and create an entity: URI.
    $entity_id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($string);
    if ($entity_id !== NULL) {
      $uri = 'entity:node/' . $entity_id;
    }
    // Handle special route values: <nolink>, <none>, <button>, <current_node>.
    // These are stored with the route: scheme.
    elseif (in_array($string, ['<nolink>', '<none>', '<button>', '<current_node>'], TRUE)) {
      $uri = 'route:' . $string;
    }
    // Handle schemeless strings (internal paths).
    // These are stored with the internal: scheme.
    elseif (!empty($string) && parse_url($string, PHP_URL_SCHEME) === NULL) {
      // Special case: <front> is converted to internal:/.
      if (str_starts_with($string, '<front>')) {
        $string = '/' . substr($string, strlen('<front>'));
      }
      $uri = 'internal:' . $string;
    }

    return $uri;
  }

  /**
   * {@inheritdoc}
   *
   * Renders the CTA button for a single row in the view.
   *
   * The render output depends on the configured values:
   * - Empty label: Returns empty array (nothing rendered)
   * - <nolink> or <button>: Returns markup with text only (no link)
   * - Valid URL: Returns a link render array with appropriate classes
   *
   * @param \Drupal\views\ResultRow $values
   *   The row of values being rendered.
   *
   * @return array
   *   A render array with one of the following structures:
   *   - Empty array [] if no content should be rendered
   *   - ['#theme' => 'helperbox_add_cta', ...] for template rendering
   *
   * @see \Drupal\Core\Render\Element\Link
   * @see helperbox-add-cta.html.twig
   */
  public function render(ResultRow $values) {
    // Extract configuration options.
    $cta_type = $this->options['cta_type'] ?? 'primary';
    $cta_label = trim($this->options['cta_label'] ?? '');
    $cta_url = $this->options['cta_url'] ?? '';
    $cta_target = $this->options['cta_target'] ?? '';
    $use_link_field = $this->options['use_link_field'] ?? FALSE;
    $link_field = $this->options['link_field'] ?? '';
    $enable_extra_query_params = $this->options['cta_enable_extra_query_params'] ?? FALSE;
    $cta_query_params = $this->options['cta_query_params'] ?? '';

    // Get the entity.
    $entity = $this->getEntity($values);

    // If using entity's link field, get the URL from the field.
    if ($use_link_field && !empty($link_field)) {
      if ($entity && $entity instanceof \Drupal\Core\Entity\FieldableEntityInterface && $entity->hasField($link_field)) {
        $field_item = $entity->get($link_field)->first();
        if (!$field_item->isEmpty()) {
          $cta_url = $field_item->getValue()['uri'] ?? '';
          $cta_label = $field_item->getValue()['title'] ?? '';
        }
      }
    }

    // Determine URL type and link status.
    $url_type = $this->getUrlType($cta_url);
    $is_no_link = in_array($url_type, ['nolink', 'button', 'none'], TRUE);
    $is_external = $url_type === 'external';
    $is_front = $url_type === 'front';
    $is_current_node = $url_type === 'current_node';

    // Handle special cases: <nolink> and <button> display text only.
    // These are used when you want button styling without a link.
    if ($is_no_link) {
      return [
        '#theme' => 'helperbox_add_cta',
        '#cta_url' => '',
        '#cta_label' => (string) $cta_label,
        '#cta_type' => $cta_type,
        '#cta_target' => '',
        '#attributes' => [],
        '#url_type' => $url_type,
        '#is_external' => FALSE,
        '#is_no_link' => TRUE,
      ];
    }

    // Handle <current_node>: get URL from the current entity.
    if ($is_current_node && $entity) {
      $url_string = $entity->toUrl()->toString();
      // Optionally use entity title as label if not configured.
      if (empty($cta_label)) {
        $cta_label = $entity->label();
      }
    }
    else {
      // Attempt to create a Url object from the URI.
      $cta_url_object = $this->getUrl($cta_url);
      if (!$cta_url_object) {
        return [];
      }

      // Get the string URL for the template.
      $url_string = $cta_url_object->toString();

      // For front page, ensure we have the correct URL.
      if ($is_front) {
        $url_string = Url::fromRoute('<front>')->toString();
      }
    }


    // Process extra query parameters if enabled.
    if ($enable_extra_query_params && !empty($cta_query_params) && $entity) {
      $query_params = $this->parseQueryParams($cta_query_params, $entity);
      if (!empty($query_params)) {
        $url_string = $this->appendQueryParams($url_string, $query_params);
      }
    }

    // Build attributes array.
    $attributes = [];

    return [
      '#theme' => 'helperbox_add_cta',
      '#cta_url' => $url_string,
      '#cta_label' => (string) $cta_label,
      '#cta_type' => $cta_type,
      '#cta_target' => $cta_target,
      '#attributes' => $attributes,
      '#url_type' => $url_type,
      '#is_external' => $is_external,
      '#is_no_link' => FALSE,
    ];
  }

  /**
   * Parses query parameters from textarea input.
   *
   * @param string $params_text
   *   The textarea value with key=value pairs, one per line.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object for placeholder replacement.
   *
   * @return array
   *   An associative array of query parameters.
   */
  protected function parseQueryParams($params_text, $entity) {
    $query_params = [];
    $lines = explode("\n", trim($params_text));

    foreach ($lines as $line) {
      $line = trim($line);
      if (empty($line) || strpos($line, '=') === FALSE) {
        continue;
      }

      list($key, $value) = explode('=', $line, 2);
      $key = trim($key);
      $value = trim($value);

      if (!empty($key)) {
        // Replace placeholders in the value.
        $value = $this->replacePlaceholders($value, $entity);
        $query_params[$key] = $value;
      }
    }

    return $query_params;
  }

  /**
   * Replaces placeholders in a string with entity values.
   *
   * @param string $value
   *   The string containing placeholders.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return string
   *   The string with placeholders replaced.
   */
  protected function replacePlaceholders($value, $entity) {
    $replacements = [
      '{id}' => $entity->id(),
      '{entity_type}' => $entity->getEntityTypeId(),
      '{bundle}' => $entity->bundle(),
      '{title}' => $entity->label(),
      '{url}' => $entity->toUrl()->toString(),
    ];

    return str_replace(array_keys($replacements), array_values($replacements), $value);
  }

  /**
   * Appends query parameters to a URL.
   *
   * @param string $url
   *   The original URL.
   * @param array $query_params
   *   An associative array of query parameters.
   *
   * @return string
   *   The URL with query parameters appended.
   */
  protected function appendQueryParams($url, array $query_params) {
    if (empty($query_params)) {
      return $url;
    }

    // Handle Drupal internal URLs (entity:, internal:, route:).
    if (strpos($url, '://') !== FALSE && strpos($url, 'http') !== 0) {
      // For internal Drupal URLs, append query params directly.
      $query_string = http_build_query($query_params);
      return $url . (strpos($url, '?') !== FALSE ? '&' : '?') . $query_string;
    }

    // Parse the URL to separate existing query string.
    $parsed = parse_url($url);
    
    // Handle relative paths (e.g., /node/1, /about).
    if (!isset($parsed['host'])) {
      $path = $parsed['path'] ?? $url;
      $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';
      
      // Remove fragment from path for query string processing.
      if ($fragment) {
        $path = str_replace($fragment, '', $path);
      }
      
      // Merge with existing query parameters.
      $existing_params = [];
      if (isset($parsed['query'])) {
        parse_str($parsed['query'], $existing_params);
      }
      $query_params = array_merge($existing_params, $query_params);
      
      // Build the query string.
      $query_string = http_build_query($query_params);
      return $path . '?' . $query_string . $fragment;
    }

    // Handle absolute URLs (http/https).
    $scheme = $parsed['scheme'] ?? '';
    $host = $parsed['host'] ?? '';
    $path = $parsed['path'] ?? '';
    $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';
    $base_url = $scheme . '://' . $host . $path;

    // Merge with existing query parameters.
    $existing_params = [];
    if (isset($parsed['query'])) {
      parse_str($parsed['query'], $existing_params);
    }
    $query_params = array_merge($existing_params, $query_params);

    // Build the query string.
    $query_string = http_build_query($query_params);
    return $base_url . '?' . $query_string . $fragment;
  }

  /**
   * Determines the type of URL from the stored URI string.
   *
   * This method analyzes the URI and returns a string indicating the type
   * of link, which can be used by the template for appropriate rendering.
   *
   * @param string $uri
   *   The URI string to analyze.
   *
   * @return string
   *   The URL type. One of:
   *   - 'internal': Internal path (e.g., /about, /node/1)
   *   - 'external': External URL (e.g., https://example.com)
   *   - 'front': Front page link (<front> or internal:/)
   *   - 'nolink': No link, text only (<nolink>)
   *   - 'button': Button-style text only (<button>)
   *   - 'current_node': Current entity being rendered
   *   - 'none': Empty or invalid URI
   *   - 'entity': Entity reference (e.g., entity:node/1)
   *   - 'route': Route-based link
   */
  protected function getUrlType(string $uri): string {
    $uri = trim($uri);

    // Empty URI.
    if (empty($uri)) {
      return 'none';
    }

    // Check for route: scheme (nolink, button, front, current_node).
    if (str_starts_with($uri, 'route:')) {
      $route_name = substr($uri, 6);
      if ($route_name === '<nolink>') {
        return 'nolink';
      }
      if ($route_name === '<button>') {
        return 'button';
      }
      if ($route_name === '<front>') {
        return 'front';
      }
      if ($route_name === '<current_node>') {
        return 'current_node';
      }
      return 'route';
    }

    // Check for explicit <front> or internal:/.
    if ($uri === '<front>' || $uri === 'internal:/') {
      return 'front';
    }

    // Check for entity: scheme.
    if (str_starts_with($uri, 'entity:')) {
      return 'entity';
    }

    // Check for internal: scheme.
    if (str_starts_with($uri, 'internal:')) {
      return 'internal';
    }

    // Check for external URL schemes.
    $scheme = parse_url($uri, PHP_URL_SCHEME);
    if (in_array($scheme, ['http', 'https', 'mailto', 'tel', 'ftp'], TRUE)) {
      return 'external';
    }

    // Default to internal for unknown schemes.
    return 'internal';
  }

  /**
   * Converts a stored URI string to a Drupal Url object.
   *
   * This method handles all supported URI schemes and special values,
   * returning a valid Url object that can be used in render arrays.
   *
   * Supported formats:
   * - internal:/path → Internal path URL
   * - internal:/ → Front page URL
   * - entity:node/NID → Node canonical URL
   * - route:<front> → Front page URL
   * - route:<nolink> → NULL (no link)
   * - route:<button> → NULL (no link)
   * - route:<current_node> → Current entity URL (handled in render())
   * - https://... → External URL
   *
   * @param string $uri
   *   The URI string to convert. May include schemes like internal:, entity:,
   *   route:, or standard URL schemes.
   *
   * @return \Drupal\Core\Url|null
   *   A Url object if the URI is valid and should produce a link, or NULL if:
   *   - The URI is empty
   *   - The URI is a no-link value (<nolink>, <button>)
   *   - The URI is invalid or cannot be parsed
   *
   * @see \Drupal\Core\Url::fromUri()
   * @see \Drupal\Core\Field\Plugin\Field\FieldType\LinkItem::getUrl()
   */
  protected function getUrl(string $uri): ?Url {
    $uri = trim($uri);

    // Return NULL for empty URIs.
    if (empty($uri)) {
      return NULL;
    }

    // Handle route: scheme for special values.
    if (str_starts_with($uri, 'route:')) {
      $route_name = substr($uri, 6);

      // <nolink> and <button> should not produce a link.
      if (in_array($route_name, ['<nolink>', '<button>'], TRUE)) {
        return NULL;
      }

      // <front> links to the site's front page.
      if ($route_name === '<front>') {
        return Url::fromRoute('<front>');
      }

      // <current_node> is handled in render() method with entity context.
      if ($route_name === '<current_node>') {
        return NULL;
      }
    }

    // Handle explicit <front> value or internal:/ (front page).
    if ($uri === 'internal:/' || $uri === '<front>') {
      return Url::fromRoute('<front>');
    }

    // Attempt to create a Url object from the URI.
    // This handles all standard schemes: internal:, entity:, https:, etc.
    try {
      return Url::fromUri($uri);
    } catch (\InvalidArgumentException $e) {
      // Invalid URI format.
      return NULL;
    }
  }
}
