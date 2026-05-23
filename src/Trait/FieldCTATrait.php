<?php

namespace Drupal\helperbox\Trait;

/**
 * Provides shared CTA type configuration for Views field and Field formatter plugins.
 *
 * Classes using this trait MUST have access to $this->t() (i.e. extend a class
 * that uses StringTranslationTrait or provides the method directly, such as
 * FieldPluginBase or FormatterBase).
 * 
 * This trait centralises the CTA type options and related form elements to ensure
 * consistency between the Views field and the Field formatter.
 */
trait FieldCTATrait {

    /**
     * Returns the available CTA type options.
     *
     * Centralises the option list so both the Views field plugin and the field
     * formatter always present identical choices.
     *
     * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup>
     *   An associative array keyed by machine name with translated labels.
     */
    protected function ctaTypeOptions(): array {
        return [
            'primary'   => $this->t('Primary Button'),
            'secondary' => $this->t('Secondary Button'),
            'outline'   => $this->t('Outline Button'),
            'link'      => $this->t('Link Style'),
            'card'      => $this->t('Card Style'),
            'danger'    => $this->t('Danger Button'),
            'success'   => $this->t('Success Button'),
        ];
    }
}
