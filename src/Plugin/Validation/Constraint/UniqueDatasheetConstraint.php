<?php

namespace Drupal\ascend_datasheet\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that a Datasheet's type + year combination is unique.
 *
 * @Constraint(
 *   id = "UniqueDatasheet",
 *   label = @Translation("Unique datasheet", context = "Validation"),
 *   type = "entity"
 * )
 */
class UniqueDatasheetConstraint extends Constraint {

  /**
   * The message that will be shown if the combination is not unique.
   */
  public $item_preexists = 'A datasheet already exists for this combination of type, stage and year (%type, %stage, %year).';
  public $school_item_preexists = 'A datasheet already exists for this combination of school, stage and year (%school, %stage, %year).';
}
