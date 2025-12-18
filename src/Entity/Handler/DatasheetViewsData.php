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

    return $data;
  }

}
