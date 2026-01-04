<?php

namespace Drupal\ascend_datasheet\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Calculates Overall (SEN Support + EHC Plan).
 *
 * @ViewsField("datasheet_overall_calculation")
 * )
 */
class DatasheetOverallCalculation extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // No query needed - we calculate from existing data.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity = $this->getEntity($values);

    if (!$entity) {
      return '';
    }

    $sen_support = $entity->get('ascend_ds_sen')->value ?? 0;
    $ehc_plan = $entity->get('ascend_ds_ehc')->value ?? 0;

    $overall = $sen_support + $ehc_plan;

    return [
      '#markup' => number_format($overall, 1) . '%',
    ];
  }
}
