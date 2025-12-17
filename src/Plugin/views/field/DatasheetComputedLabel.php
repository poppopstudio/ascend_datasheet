<?php

namespace Drupal\ascend_datasheet\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler for computed Datasheet label.
 *
 * @ViewsField("datasheet_computed_label")
 */
class DatasheetComputedLabel extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // No query needed since we compute on the fly.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity = $this->getEntity($values);
    return ['#markup' => $entity->label()];
  }
}
