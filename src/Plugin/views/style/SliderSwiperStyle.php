<?php

namespace Drupal\helperbox\Plugin\views\style;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsStyle;
use Drupal\views\Plugin\views\style\DefaultStyle;

/**
 * Swiper Slider style plugin for Views.
 *
 */
#[ViewsStyle(
  id: "helperbox_slider_swiper",
  title: new TranslatableMarkup("Helperbox Slider Swiper"),
  help: new TranslatableMarkup("Displays rows as a Swiper slider."),
  theme: "views_view_helperbox_slider_swiper",
  display_types: ["normal"],
)]
class SliderSwiperStyle extends DefaultStyle {

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  protected $usesRowClass = TRUE;
}
