<?php

namespace Drupal\ascend_datasheet\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the default form handler for the Datasheet entity.
 */
class DatasheetForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\ascend_datasheet\Entity\Datasheet $datasheet */
    $datasheet = $this->entity;

    // At the moment not clear if this needs to be restricted.

    // if (isset($form['revision'])) {
    //   // Hide the revision checkbox for restricted roles.
    //   $restricted_roles = ['',];
    //   $current_user = \Drupal::currentUser();

    //   foreach ($restricted_roles as $role) {
    //     if ($current_user->hasRole($role)) {
    //       $form['revision']['#access'] = FALSE;
    //       break;
    //     }
    //   }
    // }

    // Set form title.
    if ($this->operation == 'default') {
      $form['#title'] = $this->t('Add @type datasheet', [
        '@type' => $datasheet->bundle(),
      ]);
    }

    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('<em>Edit @type datasheet</em> @title', [
        '@type' => $datasheet->bundle(),
        '@title' => $datasheet->label(),
      ]);
    }

    // Emulates entity info behaviour similar to nodes (guess where it's from).
    $form['meta'] = [
      '#type' => 'details',
      '#group' => 'advanced',
      '#weight' => -100,
      '#title' => $this->t('Status'),
      '#attributes' => ['class' => ['entity-meta__header']],
      '#tree' => TRUE,
      '#access' => $this->currentUser()->hasPermission('update any datasheet'),
    ];
    $form['meta']['published'] = [
      '#type' => 'item',
      '#markup' => $datasheet->isPublished() ? $this->t('Published') : $this->t('Not published'),
      // This line seems redundant but the above line doesn't work anyway? Only shows published for either.
      '#access' => !$datasheet->isNew(),
      '#wrapper_attributes' => ['class' => ['entity-meta__title']],
    ];
    $form['meta']['changed'] = [
      '#type' => 'item',
      '#title' => $this->t('Last saved'),
      '#markup' => !$datasheet->isNew() ? \Drupal::service('date.formatter')->format($datasheet->getChangedTime(), 'short') : $this->t('Not saved yet'),
      '#wrapper_attributes' => ['class' => ['entity-meta__last-saved']],
    ];
    $form['meta']['author'] = [
      '#type' => 'item',
      '#title' => $this->t('Author'),
      '#markup' => $datasheet->getOwner()?->getAccountName() ?? 'n/a!',
      '#wrapper_attributes' => ['class' => ['entity-meta__author']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getNewRevisionDefault() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $saved = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->entity->toUrl('canonical'));

    return $saved;
  }

}
