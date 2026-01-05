<?php

namespace Drupal\ascend_datasheet\Entity\Handler;

use Drupal\views\EntityViewsData;

/**
 * Provides the Views data handler for the Datasheet entity.
 */
class DatasheetViewsData extends EntityViewsData {
  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Fetch a computed value for the title/label/whatever.
    $data['datasheet']['computed_label'] = [
      'title' => $this->t('Datasheet Label'),
      'help' => $this->t('The computed label'),
      'field' => [
        'id' => 'datasheet_computed_label',
      ],
    ];

    // Register the Overall Calculation field.
    $data['datasheet']['overall_calculation'] = [
      'title' => $this->t('Datasheet overall calculation'),
      'help' => $this->t('Calculates SEN Support + EHC Plan'),
      'field' => [
        'id' => 'datasheet_overall_calculation',
      ],
    ];

    // Add a custom sort field for datasheet type ordering.
    $data['datasheet']['type_sort_order'] = [
      'title' => $this->t('Type Sort Order'),
      'help' => $this->t('Sorts by National, Local, School order'),
      'sort' => [
        'id' => 'datasheet_type_sort',
      ],
    ];

    return $data;
  }

}
