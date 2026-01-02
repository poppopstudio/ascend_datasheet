<?php

namespace Drupal\ascend_datasheet\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\views\ViewExecutable;

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
   * Implements hook_preprocess_page_title().
   */
  #[Hook('preprocess_page_title')]
  public function preprocessPageTitle(&$variables) {
    $route_match = \Drupal::routeMatch();
    $type = 'datasheet';

    if ($route_match->getRouteName() == "entity.$type.canonical") {
      $variables['title'] = 'Datasheet: ' . $variables['title'];
    }
  }

  /**
   * Implements hook_views_pre_render().
   */
  #[Hook('views_pre_render')]
  public function viewsPreRender(ViewExecutable $view): void {
    // Add context to the datasheets view.
    if ($view->id() == 'school_datasheets') {
      $view->element['#cache']['contexts'][] = 'user';
      $view->element['#cache']['contexts'][] = 'route';
    }
  }

}
