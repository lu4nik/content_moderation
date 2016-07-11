<?php

namespace Drupal\content_moderation\Entity;

use Drupal\content_moderation\ContentModerationStateInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the content_moderation_state entity class.
 *
 * @ContentEntityType(
 *   id = "content_moderation_state",
 *   label = @Translation("Content Moderation State"),
 *   label_singular = @Translation("content moderation state"),
 *   label_plural = @Translation("content moderation states"),
 *   label_count = @PluralTranslation(
 *     singular = "@count content moderation state",
 *     plural = "@count content moderation states"
 *   ),
 *   base_table = "content_moderation_state",
 *   revision_table = "content_moderation_state_revision",
 *   data_table = "content_moderation_state_field_data",
 *   revision_data_table = "content_moderation_state_field_revision",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *     "langcode" = "langcode",
 *   }
 * )
 */
class ContentModerationState extends ContentEntityBase implements ContentModerationStateInterface{
  use EntityChangedTrait;

  /**
   * @inheritDoc
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('The username of the entity creator.'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\content_moderation\Entity\ContentModerationState::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    $fields['moderation_state'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Moderation state'))
      ->setDescription(t('The moderation state of the referenced content.'))
      ->setSetting('target_type', 'moderation_state')
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->addConstraint('ModerationState', []);

    $fields['content_entity_type_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Content entity type ID'))
      ->setDescription(t('The ID of the content entity type this moderation state is for.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE);

    $fields['content_entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Content entity ID'))
      ->setDescription(t('The ID of the content entity this moderation state is for.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE);

    // @todo Add constraint that enforces unique content_entity_type_id / content_entity_id
    // @todo Index on content_entity_type_id, content_entity_id and content_entity_revision_id

    $fields['content_entity_revision_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Content entity revision ID'))
      ->setDescription(t('The revision ID of the content entity this moderation state is for.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->getEntityKey('uid');
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * Creates an entity or updates.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The content entity to moderate.
   * @param string $moderation_state->target_id
   *   (optional) The ID of the state to give the entity.
   */
  public static function updateOrCreateFromEntity(EntityInterface $entity, $moderation_state = NULL) {
    $moderation_state = $moderation_state ?: $entity->moderation_state->target_id;
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    if (!$moderation_state) {
      $moderation_state = \Drupal::service('content_moderation.moderation_information')
        ->loadBundleEntity($entity->getEntityType()->getBundleEntityType(), $entity->bundle())
        ->getThirdPartySetting('content_moderation', 'default_moderation_state');
    }

    // @todo what if $moderation_state->target_id is null at this point?

    $entity_type_id = $entity->getEntityTypeId();
    $entity_id = $entity->id();
    $entity_revision_id = $entity->getRevisionId();
    $entity_langcode = $entity->language()->getId();

    // @todo maybe just try and get it from the computed field?
    $entities = \Drupal::entityTypeManager()
      ->getStorage('content_moderation_state')
      ->loadByProperties([
        'content_entity_type_id' => $entity_type_id,
        'content_entity_id' => $entity_id,
      ]);

    /** @var \Drupal\content_moderation\ContentModerationStateInterface $content_moderation_state */
    $content_moderation_state = reset($entities);
    if (!($content_moderation_state instanceof ContentModerationStateInterface)) {
      $content_moderation_state = ContentModerationState::create([
        'content_entity_type_id' => $entity_type_id,
        'content_entity_id' => $entity_id,
      ]);
    }
    else {
      // Create a new revision.
      $content_moderation_state->setNewRevision(TRUE);
    }

    // Sync translations.
    if (!$content_moderation_state->hasTranslation($entity_langcode)) {
      $content_moderation_state->addTranslation($entity_langcode);
    }
    if ($content_moderation_state->language()->getId() !== $entity_langcode) {
      $content_moderation_state = $content_moderation_state->getTranslation($entity_langcode);
    }

    // Create the ContentModerationState entity for the inserted entity.
    $content_moderation_state->set('content_entity_revision_id', $entity_revision_id);
    $content_moderation_state->set('moderation_state', $moderation_state);
    $content_moderation_state->save();
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return array(\Drupal::currentUser()->id());
  }

}