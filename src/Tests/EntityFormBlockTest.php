<?php
/**
 * @file
 * Contains \Drupal\entityform_block\EntityFormBlockTests.
 */

namespace Drupal\entityform_block\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the entity form blocks.
 *
 * @group entityform_block
 */
class EntityFormBlockTest extends WebTestBase {

  /**
   * Disabled config schema checking temporarily until all errors are resolved.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'block', 'entityform_block');

  /**
   * Tests the entity form blocks.
   */
  public function testEntityFormBlock() {
    // Create article content type.
    $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));

    $admin_user = $this->drupalCreateUser(array(
      'administer blocks',
      'administer nodes',
      'administer site configuration',
      'create article content',
    ));
    $this->drupalLogin($admin_user);

    // Add a content block with an entity form.
    $this->drupalGet('admin/structure/block');
    $this->clickLink(t('Entity form'));
    $edit = array(
      'settings[bundle]' => 'article',
      'region' => 'content',
    );
    $this->drupalPostForm(NULL, $edit, t('Save block'));

    $this->drupalGet('<front>');

    // Make sure the entity form is available.
    $this->assertText('Entity form');
    $this->assertField('title[0][value]');
    $this->assertField('body[0][value]');
    $this->assertField('revision');
  }

}
