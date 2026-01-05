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

    if ($entity_bundle === 'school' && $account->hasRole('auditor')) {

      /**
       * This lot determines the school ID by one of two ways depending on
       * the page context. School ID is either in ?sid=ID or the url.
       */
      $route_match = \Drupal::routeMatch();
      $school = $route_match->getParameter('school');

      if (!$school) {
        $sid = \Drupal::request()->query->get('sid');
        if ($sid) {
          $school = \Drupal::entityTypeManager()
            ->getStorage('school')
            ->load($sid);
        }
      }

      /**
       * Return values here are janky because the VAB contrib module doesn't
       * respect returning access objects rather than booleans.
       */
      if (!$school) {
        $result = AccessResult::forbidden("Cannot create datasheet without context")
          ->cachePerUser();
        return $return_as_object ? $result : $result->isAllowed();
      }

      if (!$this->isAuditorAssignedToSchool($school, $account)) {
        $result = AccessResult::forbidden('Auditor not assigned')
          ->cachePerUser()
          ->addCacheableDependency($school)
          ->addCacheContexts(['route']);
        return $return_as_object ? $result : $result->isAllowed();
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
        'view' => 'view datasheet', // Not 'school' because ds' are not hidden.
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
          // Check if datasheet is from the current academic year.
          $working_year = \Drupal::service('Drupal\ascend_audit\Services\AuditYearService')->getWorkingYear();
          $datasheet_year = $entity->get('year')->value;

          // No access if the sheet is not from this academic year.
          if ($datasheet_year != $working_year) {
            return AccessResult::forbidden('Cannot update datasheets from previous academic years')
              ->cachePerPermissions()
              ->cachePerUser()
              ->addCacheableDependency($entity);
          }

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

    $auditor_ids = array_column(
      $school->get('ascend_sch_auditor')->getValue(),
      'target_id'
    );

    return in_array($account->id(), $auditor_ids);
  }
}
