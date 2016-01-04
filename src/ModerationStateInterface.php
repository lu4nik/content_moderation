<?php

/**
 * @file
 * Contains \Drupal\workbench_moderation\ModerationStateInterface.
 */

namespace Drupal\workbench_moderation;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Moderation state entities.
 */
interface ModerationStateInterface extends ConfigEntityInterface {

  /**
   * Determines if this state represents a published node.
   *
   * @return bool
   *   TRUE if this state deems the node published.
   */
  public function isPublishedState();

  /**
   * Determines if a revision should be made the live revision upon transition to this state.
   *
   * @return bool
   *   TRUE if content in this state should be the live revision.
   */
  public function isLiveRevisionState();

}
