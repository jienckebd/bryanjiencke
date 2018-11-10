<?php

namespace Drupal\cn_core\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted value is a unique integer.
 *
 * @Constraint(
 *   id = "UniqueEntityLabel",
 *   label = @Translation("Unique Entity Label", context = "Validation"),
 *   type = "string"
 * )
 */
class UniqueEntityLabel extends Constraint {

  // The message that will be shown if the value is not an integer.
  public $notInteger = '%value is not an integer';

  // The message that will be shown if the value is not unique.
  public $notUnique = 'The label %value is already used by another %entity_type_label. Please use another label.';

}
