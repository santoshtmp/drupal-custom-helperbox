<?php

namespace Drupal\helperbox\Plugin\views\area;

use Drupal\Core\Form\FormStateInterface;
use Drupal\helperbox\Trait\RenderBlockTrait;
use Drupal\views\Attribute\ViewsArea;
use Drupal\views\Plugin\views\area\AreaPluginBase;

/**
 * Views area handler that renders a block or views block in header/footer/empty.
 */
#[ViewsArea("helperbox_renderblock")]
class RenderBlock extends AreaPluginBase {

    use RenderBlockTrait;

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
    public function render($empty = FALSE): array|string {
        if ($empty && !$this->options['empty']) {
            return '';
        }
        return $this->buildBlockRenderArray();
    }

}