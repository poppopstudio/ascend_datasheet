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

    // Get the field values.
    $type = $entity->get('type')->target_id;
    $year = $entity->get('year')->value;

    if ($type === 'school') {
      $school_id = $entity->get('school')->target_id;
    }

    // Skip validation if any required field is empty.
    if (!$type || !$year) { // school??
      return;
    }

    $stages = ['primary', 'secondary'];

    foreach ($stages as $stage) {
      // Query for existing datasheets with the same combination.
      $query = $this->entityTypeManager->getStorage('datasheet')->getQuery()
        ->condition('type', $type)
        ->condition('stage', $stage)
        ->condition('year', $year)
        ->accessCheck(FALSE);

      if ($type === 'school') {
        $query->condition('school', $school_id);
      }

      // If this is an existing entity (update), exclude it from the query.
      if (!$entity->isNew()) {
        $query->condition('datasheet_id', $entity->id(), '<>');
      }

      $existing_datasheets = $query->execute();

      // If we found existing Datasheets, add a violation.
      if (!empty($existing_datasheets)) {

        $violation = $constraint->item_preexists;
        if ($type === 'school') {
          $violation = $constraint->school_item_preexists;
        }

        $this->context->addViolation($violation);
        break; // Found a problem, stop here.
      }
    }
  }
}
