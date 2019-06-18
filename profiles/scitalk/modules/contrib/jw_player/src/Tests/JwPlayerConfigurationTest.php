<?php

namespace Drupal\jw_player\Tests;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\simpletest\WebTestBase;

/**
 * Tests configuration of a jw player preset and creation of jw player content.
 *
 * @group jw_player
 */
class JwPlayerConfigurationTest extends WebTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'node',
    'jw_player',
    'libraries',
    'file',
    'field',
    'field_ui',
    'block',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create jw_player content type.
    $this->drupalCreateContentType(array('type' => 'jw_player', 'name' => 'JW content'));
    // Place the breadcrumb, tested in fieldUIAddNewField().
    $this->drupalPlaceBlock('system_breadcrumb_block');
  }

  /**
   * Tests the jw player creation.
   */
  public function testJwPlayerCreation() {

    $admin_user = $this->drupalCreateUser(array(
      'administer site configuration',
      'administer JW Player presets',
      'administer nodes',
      'create jw_player content',
      'administer content types',
      'administer node fields',
      'administer node display',
      'access administration pages',
    ));
    $this->drupalLogin($admin_user);

    // Add a random "Cloud-Hosted Account Token".
    $edit = [
      'jw_player_hosting' => 'cloud',
      'cloud_player_library_url' => $cloud_library = 'https://content.jwplatform.com/libraries/' . $this->randomMachineName(8) . '.js',
    ];
    $this->drupalPostForm('admin/config/media/jw_player/settings', $edit, t('Save configuration'));
    // Create a jw player preset.
    $edit = array(
      'label' => 'Test preset',
      'id' => 'test_preset',
      'description' => 'Test preset description',
      'settings[skin]' => 'bekle',
      'settings[mode]' => 'html5',
      'settings[width]' => 100,
      'settings[height]' => 100,
      'settings[advertising][client]' => 'vast',
      'settings[advertising][tag]' => 'www.example.com/vast',
      'settings[controlbar]' => 'bottom',
      'settings[mute]' => TRUE,
      'settings[autostart]' => TRUE,
    );
    $this->drupalPostForm('admin/config/media/jw_player/add', $edit, t('Save'));
    $this->assertText('Saved the Test preset Preset.');
    // Make sure preset has correct values.
    $this->drupalGet('admin/config/media/jw_player/test_preset');
    $this->assertFieldByName('label', 'Test preset');
    $this->assertFieldByName('description', 'Test preset description');
    $this->assertFieldByName('settings[mode]', 'html5');
    $this->assertFieldByName('settings[skin]', 'bekle');
    $this->assertFieldByName('settings[advertising][client]', 'vast');
    $this->assertFieldByName('settings[advertising][tag]', 'www.example.com/vast');
    $this->assertFieldByName('settings[controlbar]', 'bottom');
    $this->assertFieldByName('settings[mute]', TRUE);
    $this->assertFieldByName('settings[autostart]', TRUE);
    $this->assertNoFieldByName('settings[sharing]');

    // Create a JW player format file field in JW content type.
    static::fieldUIAddNewField('admin/structure/types/manage/jw_player', 'video', 'Video', 'file', array(), array('settings[file_extensions]' => 'mp4'));
    $this->drupalPostForm('admin/structure/types/manage/jw_player/display', array('fields[field_video][type]' => 'jwplayer_formatter'), t('Save'));
    $this->drupalPostAjaxForm(NULL, NULL, 'field_video_settings_edit');
    $edit = [
      'fields[field_video][settings_edit_form][settings][jwplayer_preset]' => 'test_preset',
    ];
    $this->drupalPostForm(NULL, $edit, t('Update'));
    $this->drupalPostForm(NULL, NULL, t('Save'));
    // Make sure JW preset is correct.
    $this->assertText('Preset: Test preset');
    $this->assertText('Dimensions: 100x100, uniform');
    $this->assertText('Skin: bekle');
    $this->assertText('Enabled options: Autostart, Mute');
    // Make sure the formatter reports correct dependencies.
    /** @var \Drupal\Core\Entity\Entity\EntityViewDisplay $view_display */
    $view_display = EntityViewDisplay::load('node.jw_player.default');
    $this->assertTrue(in_array('jw_player.preset.test_preset', $view_display->getDependencies()['config']));

    // Create a 'video' file, which has .mp4 extension.
    $text = 'Trust me I\'m a video';
    file_put_contents('temporary://myVideo.mp4', $text);
    // Create video content from JW content type.
    $edit = array(
      'title[0][value]' => 'Test video',
      'files[field_video_0]' => drupal_realpath('temporary://myVideo.mp4'),
    );
    $this->drupalPostForm('node/add/jw_player', $edit, t('Save and publish'));
    $this->assertText('JW content Test video has been created.');

    $value = $this->xpath('//video/@id');
    $id = (string) $value[0]['id'];
    // Check the jw_player js, since the cloud player library url is set the
    // preset config is not applied.
    $this->assertRaw('jw_player":{"players":{"' . $id . '":{"file":"' . str_replace('/', '\/', (file_create_url(\Drupal::token()->replace('public://[date:custom:Y]-[date:custom:m]/myVideo.mp4')))) . '"');
    // Make sure the hash is there.
    $this->assertTrue(preg_match('/jwplayer-[a-zA-Z0-9]{1,}$/', $id));
    // Check the library created because of cloud hosting.
    $this->assertRaw('<script src="' . $cloud_library . '"></script>');
    // @todo Add test for advertising.
  }

  /**
   * Tests the UI for deletion of a preset.
   */
  public function testDelete() {
    $admin_user = $this->drupalCreateUser(array(
      'administer site configuration',
    ));
    $this->drupalLogin($admin_user);

    // Create a preset.
    $this->drupalPostForm('admin/config/media/jw_player/add', [
      'label' => 'Test preset',
      'id' => 'test_preset',
      'settings[width]' => 100,
      'settings[height]' => 100,
    ], t('Save'));

    $this->clickLink(t('Delete'));
    $this->drupalPostForm(NULL, [], t('Delete'));
    $this->assertRaw(t('The @entity-type %label has been deleted.', ['@entity-type' => 'jw player preset', '%label' => 'Test preset']));
    $this->assertEqual([], $this->xpath('//td[text()=@label]', ['@label' => 'Test preset']));
  }

}
