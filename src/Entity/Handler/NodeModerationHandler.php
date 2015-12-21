<?php
/**
 * @file
 * Contains Drupal\moderation_state\Entity\Handler\NodeCustomizations.
 */

namespace Drupal\moderation_state\Entity\Handler;


use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Customizations for node entities.
 */
class NodeModerationHandler extends ModerationHandler {

  /**
   * {@inheritdoc}
   */
  public function onPresave(ContentEntityInterface $entity, $published_state) {
    parent::onPresave($entity, $published_state);

    // Only nodes have a concept of published.
    /** @var $entity Node */
    $entity->setPublished($published_state);
  }

  /**
   * {@inheritdoc}
   */
  public function enforceRevisionsEntityFormAlter(array &$form, FormStateInterface $form_state, $form_id) {
    $form['revision']['#disabled'] = TRUE;
    $form['revision']['#default_value'] = TRUE;
    $form['revision']['#description'] = $this->t('Revisions are required.');
  }

  /**
   * {@inheritdoc}
   */
  public function enforceRevisionsBundleFormAlter(array &$form, FormStateInterface $form_state, $form_id) {
    // Force the revision checkbox on.
    // @todo Also make it impossible to uncheck that checkbox. Not sure how
    // to do that for a single checkbox value in a checkboxes set.
    $form['workflow']['options']['#default_value']['revision'] = 'revision';
  }

}
