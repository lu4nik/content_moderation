<?php

namespace Drupal\workbench_moderation\Plugin\Action;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\Plugin\Action\PublishNode;
use Drupal\workbench_moderation\ModerationInformationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Alternate action plugin that knows to opt-out of modifying moderated entites.
 *
 * @see PublishNode
 */
class ModerationOptOutPublishNode extends PublishNode implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\workbench_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModerationInformationInterface $mod_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moderationInfo = $mod_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
   return new static(
     $configuration, $plugin_id, $plugin_definition,
     $container->get('workbench_moderation.moderation_information')
   );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity && $this->moderationInfo->isModeratableEntity($entity)) {
      drupal_set_message($this->t('One or more entities were skipped as they are under moderation and may not be directly published or unpublished.'));
      return;
    }

    parent::execute($entity);
  }
}
