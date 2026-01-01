<?php

namespace Drupal\ascend_datasheet\Entity\Handler;

use Drupal\entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides the access handler for the Datasheet entity.
 */
class DatasheetAccess extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = [], $return_as_object = FALSE) {
    $account = $this->prepareUser($account);

    // Restrict auditors from adding sheets to schools they're not assigned to.
    if ($entity_bundle === 'school' && $account->hasRole('auditor')) {

      // Get school ID from query parameter.
      $sid = \Drupal::request()->query->get('sid');

      if (!$sid) {
        // No school context - deny access for auditors.
        return AccessResult::forbidden("Cannot create datasheet without context - you must create school datasheets via the school's page.")
          ->cachePerUser();
      }

      // Load the school entity.
      $school = \Drupal::entityTypeManager()
        ->getStorage('school')
        ->load($sid);

      if (!$school) {
        return AccessResult::forbidden('Invalid school')
          ->cachePerUser();
      }

      // Check if the auditor is assigned to this school.
      if (!$this->isAuditorAssignedToSchool($school, $account)) {
        return AccessResult::forbidden('Auditor cannot create a datasheet here, as they are not assigned to this school')
          ->cachePerUser()
          ->addCacheableDependency($school);
      }
    }

    return parent::createAccess($entity_bundle, $account, $context, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    // Handle revision operations.
    if (in_array($operation, ['view revision', 'view all revisions'])) {
      return AccessResult::allowedIfHasPermission($account, 'view datasheet revisions')
        ->cachePerPermissions();
    }

    if (in_array($operation, ['revert', 'revert revision'])) {
      return AccessResult::allowedIfHasPermission($account, 'revert datasheet revisions')
        ->cachePerPermissions();
    }

    if ($operation === 'delete revision') {
      return AccessResult::allowedIfHasPermission($account, 'delete datasheet revisions')
        ->cachePerPermissions();
    }

    // Check operations that require school-based access control.
    if ($entity->bundle() === 'school' && in_array($operation, ['view', 'update'])) {

      $permission = match ($operation) {
        'view' => 'view school datasheet',
        'update' => 'update any school datasheet',
      };

      // Global permissions first - these override school-based access.
      if ($account->hasPermission($permission)) {
        return AccessResult::allowed()->cachePerPermissions();
      }

      // Check if user is an auditor.
      if ($account->hasRole('auditor')) {

        // Get the school from the datasheet entity.
        $school = $entity->get('ascend_ds_school')->entity;

        if (!$school) {
          return AccessResult::forbidden()
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }

        // Check if auditor is assigned to the school.
        if (!$this->isAuditorAssignedToSchool($school, $account)) {
          return AccessResult::forbidden()
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }

        // Auditor has access to this school, now check "own" permissions.
        if ($operation === 'update' && $account->hasPermission('update own school datasheet')) {
          return AccessResult::allowed()
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }
      }

      return AccessResult::forbidden()->cachePerPermissions();
    }

    // For all other operations, use parent EntityAccessControlHandler logic.
    return parent::checkAccess($entity, $operation, $account);
  }

  /**
   * Check if an auditor is assigned to a school.
   * We could have used the Audit trait to do this but as this work
   * package is separate let's just keep it local.
   *
   * @param \Drupal\ascend_school\Entity\SchoolInterface $school
   *   The school entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check.
   *
   * @return bool
   *   TRUE if the auditor is assigned to the school, FALSE otherwise.
   */
  protected function isAuditorAssignedToSchool($school, AccountInterface $account): bool {
    if (empty($school)) {
      return FALSE;
    }

    $auditor_ids = array_column(
      $school->get('ascend_sch_auditor')->getValue(),
      'target_id'
    );

    return in_array($account->id(), $auditor_ids);
  }
}
