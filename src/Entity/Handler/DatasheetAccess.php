<?php

namespace Drupal\ascend_datasheet\Entity\Handler;

use Drupal\ascend_audit\Entity\Handler\AuditorSchoolLinkTrait;
use Drupal\ascend_school\Entity\SchoolInterface;
use Drupal\entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides the access handler for the Datasheet entity.
 */
class DatasheetAccess extends EntityAccessControlHandler {

  use AuditorSchoolLinkTrait;

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
      $auditor_ids = array_column(
        $school->get('ascend_sch_auditor')->getValue(),
        'target_id'
      );

      if (!in_array($account->id(), $auditor_ids)) {
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
    if (in_array($operation, ['view', 'update'])) {

      $permission = match($operation) {
        'view' => 'view datasheet',
        'update' => 'update any datasheet',
      };

      // Global permissions first.
      if ($account->hasPermission($permission)) {
        return AccessResult::allowed()->cachePerPermissions();
      }

      // Check if user is an auditor.
      if (in_array('auditor', $account->getRoles())) {

        // Check (below) if user has working access to the school.
        $auditor_linked = $this->isAuditorSchoolLink($entity, $account); //don't use this fn

        if (!$auditor_linked) {
          return AccessResult::forbidden()
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }

        // For view operation, check "view own" permission.
        if ($operation === 'view' && $account->hasPermission('view own datasheet')) {
          return AccessResult::allowed()
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }

        // For update operation, check "update own" permission.
        if ($operation === 'update' && $account->hasPermission('update own datasheet')) {
          return AccessResult::allowed()
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }
      }

      return AccessResult::forbidden()->cachePerPermissions();
    }

    // For all other operations, use parent EntityAccessControlHandler logic
    return parent::checkAccess($entity, $operation, $account);
  }
}
