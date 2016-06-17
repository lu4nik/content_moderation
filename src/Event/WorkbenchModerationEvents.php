<?php

namespace Drupal\content_moderation\Event;

final class ContentModerationEvents {

  /**
   * This event is fired everytime a state is changed.
   *
   * @Event
   */
  const STATE_TRANSITION = 'content_moderation.state_transition';

}
