<?php

namespace Drupal\cn_vote\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\votingapi\Entity\Vote;
use Drupal\votingapi\Entity\VoteType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\vud\Plugin\VoteUpDownWidgetManager;

/**
 * Implements VotingAPI. Provides logical methods to the route endpoints.
 *
 * Class VotingApiController
 *
 * @package Drupal\vud\Controller
 */
class VotingApiController extends ControllerBase {

  /**
   * @param $entity_id
   *  EntityId of the referenced entity
   * @param $entity_type_id
   *  EntityTypeId of the referenced entity
   * @param $vote_value
   *  Value of vote to be stored.
   * @param string $context_entity_type
   *   The type of context entity.
   * @param int $context_entity_id
   *   The ID of the entity context.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function vote($entity_id, $entity_type_id, $vote_value, $context_entity_type = NULL, $context_entity_id = NULL, Request $request) {
    $entity = $this->entityTypeManager()
      ->getStorage($entity_type_id)
      ->load($entity_id);

    $vote_storage = $this->entityTypeManager()->getStorage('vote');

    $voteTypeId = \Drupal::config('vud.settings')->get('tag', 'vote');
    $voteType = VoteType::load($voteTypeId);

    $vote_storage->deleteUserVotes(
      $this->currentUser()->id(),
      $voteTypeId,
      $entity_type_id,
      $entity_id,
      NULL,
      $context_entity_type,
      $context_entity_id
    );

    $this->entityTypeManager()
      ->getViewBuilder($entity_type_id)
      ->resetCache([$entity]);

    $vote = Vote::create(['type' => $voteTypeId]);

    if ($context_entity_type && $context_entity_id) {
      $vote->setContextVotedEntityType($context_entity_type);
      $vote->setContextVotedEntityId($context_entity_id);
    }

    $vote->setVotedEntityId($entity_id);
    $vote->setVotedEntityType($entity_type_id);
    $vote->setValueType($voteType->getValueType());
    $vote->setValue($vote_value);
    $vote->save();

    $this->entityTypeManager()
      ->getViewBuilder($entity_type_id)
      ->resetCache([$entity]);

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
      $widget_wrapper_id = VoteUpDownWidgetManager::widgetWrapperId($entity_type_id, $entity_id, $context_entity_type, $context_entity_id);

      $widget_wrapper = ".{$widget_wrapper_id}";
      $response = new AjaxResponse();

      $view_wrapper = '.view-node-tags-vote';
      $build = views_embed_view('node_tags_vote', 'block_1', $context_entity_id);

      $response->addCommand(new ReplaceCommand($view_wrapper, $build));
      $response->addCommand(new InvokeCommand(NULL, 'voteUpSuccess', [$widget_wrapper]));
      return $response;
    }

    return new RedirectResponse($entity->toUrl()->toString());
  }

  /**
   * @param $entity_id
   *  EntityId of the referenced entity
   * @param $entity_type_id
   *  EntityTypeId of the referenced entity
   * @param string $context_entity_type
   *   The type of context entity.
   * @param int $context_entity_id
   *   The ID of the entity context.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function resetVote($entity_type_id, $entity_id, $context_entity_type = NULL, $context_entity_id = NULL, Request $request){
    $entity = $this->entityTypeManager()
      ->getStorage($entity_type_id)
      ->load($entity_id);

    $voteTypeId = \Drupal::config('vud.settings')->get('tag', 'vote');

    $vote_storage = $this->entityTypeManager()->getStorage('vote');

    $vote_storage->deleteUserVotes(
      $this->currentUser()->id(),
      $voteTypeId,
      $entity_type_id,
      $entity_id,
      NULL,
      $context_entity_type,
      $context_entity_id
    );

    $this->entityTypeManager()
      ->getViewBuilder($entity_type_id)
      ->resetCache([$entity]);

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
      $widget_wrapper_id = VoteUpDownWidgetManager::widgetWrapperId($entity_type_id, $entity_id, $context_entity_type, $context_entity_id);

      $widget_wrapper = ".{$widget_wrapper_id}";
      $response = new AjaxResponse();

      $view_wrapper = '.view-node-tags-vote';
      $build = views_embed_view('node_tags_vote', 'block_1', $context_entity_id);

      $response->addCommand(new ReplaceCommand($view_wrapper, $build));
      $response->addCommand(new InvokeCommand(NULL, 'voteUpSuccess', [$widget_wrapper]));
      return $response;
    }

    return new RedirectResponse($entity->toUrl()->toString());
  }

}
