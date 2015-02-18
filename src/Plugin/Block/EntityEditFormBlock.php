<?php

/**
 * @file
 * Contains \Drupal\entityform_block\Plugin\Block\EntityEditFormBlock.
 */

namespace Drupal\entityform_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityType;
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
      'entity_type' => '',
      'bundle' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return \Drupal::entityManager()
      ->getAccessControlHandler($this->configuration['entity_type'])
      ->createAccess($this->configuration['bundle'], $account, []);
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    // Get all entity types.
    $entity_types = \Drupal::entityManager()->getEntityTypeLabels(TRUE);
    // Get all content types from entity types.
    $content_entity_types = $entity_types['Content'];
    $options = array();
    foreach ($content_entity_types as $type_key => $type_label) {
      // Entities that do not declare a form class.
      if (!\Drupal::entityManager()->getDefinition($type_key)->hasFormClasses()) {
        continue;
      }
      // Get all bundles for current entity type.
      $entity_type_bundles = \Drupal::entityManager()->getBundleInfo($type_key);
      foreach ($entity_type_bundles as $bundle_key => $bundle_info) {
        $options[$type_label][$type_key . '.' . $bundle_key] = $bundle_info['label'];
      }
    }

    $form['entity_type_bundle'] = array(
      '#title' => $this->t('Entity Type + Bundle'),
      '#type' => 'select',
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => $this->configuration['entity_type'] . '.' . $this->configuration['bundle'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $selected_entity_type_bundle = $form_state->getValue('entity_type_bundle');
    $values = explode('.', $selected_entity_type_bundle);
    $this->configuration['entity_type'] = $values[0];
    $this->configuration['bundle'] = $values[1];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $values = array();
    // Specify selected bundle if the entity has bundles.
    if (\Drupal::entityManager()->getDefinition($this->configuration['entity_type'])->hasKey('bundle')) {
      $bundle_key = \Drupal::entityManager()->getDefinition($this->configuration['entity_type'])->getKey('bundle');
      $values = array($bundle_key => $this->configuration['bundle']);
    }

    $entity = \Drupal::entityManager()
      ->getStorage($this->configuration['entity_type'])
      ->create($values);

    if ($entity instanceof EntityOwnerInterface) {
      $entity->setOwnerId(\Drupal::currentUser()->id());
    }

    $form = \Drupal::entityManager()
      ->getFormObject($this->configuration['entity_type'], 'default')
      ->setEntity($entity);
    return \Drupal::formBuilder()->getForm($form);
  }
}
