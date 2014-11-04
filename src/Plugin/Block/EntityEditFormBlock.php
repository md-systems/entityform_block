<?php

/**
 * @file
 * Contains \Drupal\entityform_block\Plugin\Block\EntityEditFormBlock.
 */

namespace Drupal\entityform_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\NodeType;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides a block for creating a new content entity.
 *
 * @Block(
 *   id = "entityform_block",
 *   admin_label = @Translation("Entity form"),
 *   category = @Translation("Forms")
 * )
 */
class EntityEditFormBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'entity_type' => 'node',
      'bundle' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $type_id = $this->configuration['bundle'];
    return $account->hasPermission("create $type_id content");
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $form['entity_type'] = array(
      // @todo Make selectable.
      '#title' => $this->t('Entity type'),
      '#type' => 'value',
      '#value' => $this->configuration['entity_type'],
    );

    $form['bundle'] = array(
      '#title' => $this->t('Node type'),
      '#type' => 'select',
      '#options' => array_map(function(NodeType $node_type) {
        return $node_type->label();
      }, NodeType::loadMultiple()),
      '#default_value' => $this->configuration['bundle'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['entity_type'] = $form_state->getValue('entity_type');
    $this->configuration['bundle'] = $form_state->getValue('bundle');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $entity = \Drupal::entityManager()
      ->getStorage($this->configuration['entity_type'])
      ->create(array('type' => $this->configuration['bundle']));

    if ($entity instanceof EntityOwnerInterface) {
      $entity->setOwnerId(\Drupal::currentUser()->id());
    }

    $form = \Drupal::entityManager()
      ->getFormObject($this->configuration['entity_type'], 'default')
      ->setEntity($entity);
    return \Drupal::formBuilder()->getForm($form);
  }
}
