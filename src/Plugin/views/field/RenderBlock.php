<?php

namespace Drupal\helperbox\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\helperbox\Trait\RenderBlockTrait;
use Drupal\views\Attribute\ViewsField;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Views field handler that renders a block or views block per row.
 */
#[ViewsField("helperbox_renderblock")]
class RenderBlock extends FieldPluginBase {

    use RenderBlockTrait;

    /**
     * {@inheritdoc}
     */
    public function query(): void {
        // Do nothing — override parent to avoid adding a useless SQL column.
    }

    /**
     * {@inheritdoc}
     */
    public function defineOptions(): array {
        return $this->defineBlockOptions(parent::defineOptions());
    }

    /**
     * {@inheritdoc}
     */
    public function buildOptionsForm(&$form, FormStateInterface $form_state): void {
        $this->buildBlockOptionsForm($form);
        parent::buildOptionsForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function render(ResultRow $values): array|string {
        return $this->buildBlockRenderArray($this->view->args ?? []);
    }

}