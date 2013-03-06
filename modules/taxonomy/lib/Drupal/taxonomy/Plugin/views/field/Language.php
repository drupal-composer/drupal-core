<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Plugin\views\field\Language.
 */

namespace Drupal\taxonomy\Plugin\views\field;

use Drupal\Component\Annotation\Plugin;

/**
 * Field handler to show the language of a taxonomy term.
 *
 * @Plugin(
 *   id = "taxonomy_term_language"
 * )
 */
class Language extends Taxonomy {

  /**
   * Overrides Drupal\taxonomy\Plugin\views\field\Taxonomy::render().
   */
  public function render($values) {
    $value = $this->get_value($values);
    $language = language_load($value);
    $value = $language ? $language->name : '';

    return $this->render_link($this->sanitizeValue($value), $values);
  }

}
