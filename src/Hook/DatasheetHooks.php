<?php

namespace Drupal\ascend_datasheet\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Contains hook implementations for the Ascend datasheet module.
 */
class DatasheetHooks {

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(&$form, FormStateInterface $form_state, $form_id) {
    return;
  }

  /**
   * Implements hook_entity_bundle_info().
   */
  #[Hook('entity_bundle_info')]
  public function entityBundleInfo() {
    $bundles['datasheet'] = [
      'national' => [
        'label' => t('National'),
        'description' => t('Represents a National datasheet.')
      ],
      'local' => [
        'label' => t('Local'),
        'description' => t('Represents a Local datasheet.')
      ],
      'school' => [
        'label' => t('School'),
        'description' => t('Represents a School datasheet.')
      ],
    ];

    return $bundles;
  }

}
