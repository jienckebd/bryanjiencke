<?php

namespace Drupal\cn_vote\Plugin\VoteResultFunction;

use Drupal\votingapi\VoteResultFunctionBase;

/**
 * A sum of a set of votes.
 *
 * @VoteResultFunction(
 *   id = "rank",
 *   label = @Translation("Rank"),
 *   description = @Translation("The calculated rank.")
 * )
 */
class Rank extends VoteResultFunctionBase {

  /**
   * {@inheritdoc}
   */
  public function calculateResult($votes) {
    $total = 0;
    foreach ($votes as $vote) {
      $total += $vote->getValue();
    }
    return ($total / count($votes));
  }
}
