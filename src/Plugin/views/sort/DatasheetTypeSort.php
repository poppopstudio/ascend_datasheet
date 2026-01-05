<?php

namespace Drupal\ascend_datasheet\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\SortPluginBase;

/**
 * Custom sort handler to order datasheets by National, Local, and School.
 *
 * @ViewsSort("datasheet_type_sort")
 */
class DatasheetTypeSort extends SortPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    $formula = "CASE
    WHEN {$this->tableAlias}.type = 'national' THEN 1
    WHEN {$this->tableAlias}.type = 'local' THEN 2
    WHEN {$this->tableAlias}.type = 'school' THEN 3
    END";

    $this->query->addOrderBy(NULL, $formula, $this->options['order'], 'datasheet_type_order');
  }
}
