<?php

namespace Drupal\ascend_datasheet\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Validates the UniqueDatasheet constraint.
 */
class UniqueDatasheetConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new UniqueDatasheetConstraintValidator instance.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (!isset($entity)) {
      return;
    }

    $type = $entity->bundle();
    $year = $entity->get('year')->value;
    $stage = $entity->get('stage')->value;

    // For school datasheets, get the school_id.
    $school_id = NULL;
    if ($type === 'school' && !$entity->get('ascend_ds_school')->isEmpty()) {
      $school_id = $entity->get('ascend_ds_school')->target_id;
    }

    // Skip validation if any required field is empty.
    if (!$type || !$year || !$stage) {
      return;
    }

    // For school type, we also need a school_id.
    if ($type === 'school' && !$school_id) {
      return;
    }

    // Query for existing datasheets with the same combination.
    $query = $this->entityTypeManager->getStorage('datasheet')->getQuery()
      ->condition('type', $type)
      ->condition('stage', $stage)
      ->condition('year', $year)
      ->accessCheck(FALSE);

    // For school datasheets, also check against school_id.
    if ($type === 'school') {
      $query->condition('ascend_ds_school', $school_id);
    }

    // If this is an existing entity (update), exclude it from the query.
    if (!$entity->isNew()) {
      $query->condition('datasheet_id', $entity->id(), '<>');
    }

    $existing_datasheets = $query->execute();

    // If we found existing datasheets, add a violation.
    if (!empty($existing_datasheets)) {
      $violation = $type === 'school'
        ? $constraint->school_item_preexists
        : $constraint->item_preexists;

      $this->context->addViolation($violation);
    }
  }
}
