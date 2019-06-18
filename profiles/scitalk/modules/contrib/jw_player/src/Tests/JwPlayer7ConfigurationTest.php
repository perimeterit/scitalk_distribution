<?php

namespace Drupal\jw_player\Tests;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\simpletest\WebTestBase;

/**
 * Tests configuration of a jw player 7 preset and creation of jw player content.
 *
 * @group jw_player
 */
class JwPlayer7ConfigurationTest extends WebTestBase {

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
    'image',
    'link',
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
      'cloud_player_library_url' => $cloud_library = 'https://content.jwplatform.com/libraries/' . $this->randomMachineName(8) . '.js',
      'jw_player_hosting' => 'cloud',
      'jw_player_version' => 7,
    ];
    $this->drupalPostForm('admin/config/media/jw_player/settings', $edit, t('Save configuration'));
    // Create a jw player preset.
    $edit = array(
      'label' => 'Test preset',
      'id' => 'test_preset',
      'description' => 'Test preset description',
      'settings[width]' => 100,
      'settings[height]' => 100,
      'settings[advertising][client]' => 'vast',
      'settings[advertising][tag]' => 'www.example.com/vast',
      'settings[advertising][tag_post]' => 'www.example.com/vast',
      'settings[advertising][skipoffset]' => 5,
      'settings[advertising][skipmessage]' => 'Skip ad in xx',
      'settings[advertising][skiptext]' => 'Skip',
      'settings[controlbar]' => 'bottom',
      'settings[mute]' => TRUE,
      'settings[autostart]' => TRUE,
      'settings[sharing]' => TRUE,
      'settings[sharing_sites][sites][linkedin][enabled]' => TRUE,
      'settings[sharing_sites][sites][email][enabled]' => TRUE,
    );
    $this->drupalPostForm('admin/config/media/jw_player/add', $edit, t('Save'));
    $this->assertText('Saved the Test preset Preset.');
    // Make sure preset has correct values.
    $this->drupalGet('admin/config/media/jw_player/test_preset');
    $this->assertFieldByName('label', 'Test preset');
    $this->assertFieldByName('description', 'Test preset description');
    $this->assertNoField('settings[mode]');
    $this->assertFieldByName('settings[preset_source]', 'drupal');
    $this->assertFieldByName('settings[mute]', '1');
    $this->assertFieldByName('settings[sharing]', '1');
    $this->assertFieldByName('settings[skin]', NULL);
    $this->assertFieldByName('settings[advertising][client]', 'vast');
    $this->assertFieldByName('settings[advertising][tag]', 'www.example.com/vast');
    $this->assertFieldByName('settings[advertising][tag_post]', 'www.example.com/vast');
    $this->assertFieldByName('settings[advertising][skipoffset]', 5);
    $this->assertFieldByName('settings[advertising][skipmessage]', 'Skip ad in xx');
    $this->assertFieldByName('settings[advertising][skiptext]', 'Skip');
    $this->assertFieldByName('settings[controlbar]', 'bottom');
    $this->assertFieldByName('settings[mute]', TRUE);
    $this->assertFieldByName('settings[autostart]', TRUE);
    $this->assertFieldByName('settings[sharing]', TRUE);
    $this->assertFieldByName('settings[sharing_sites][sites][linkedin][enabled]', TRUE);
    $this->assertFieldByName('settings[sharing_sites][sites][email][enabled]', TRUE);

    // Create a JW player format file field in JW content type.
    static::fieldUIAddNewField('admin/structure/types/manage/jw_player', 'video', 'Video', 'file', array(), array('settings[file_extensions]' => 'mp4'));
    // Create a Image field in JW content type.
    static::fieldUIAddNewField('admin/structure/types/manage/jw_player', 'image_preview', 'image_preview', 'image', [], []);
    $this->drupalPostForm('admin/structure/types/manage/jw_player/display', array('fields[field_video][type]' => 'jwplayer_formatter'), t('Save'));
    $this->drupalPostAjaxForm(NULL, NULL, 'field_video_settings_edit');
    // Set the image field as preview of the jw player video.
    $edit = [
      'fields[field_video][settings_edit_form][settings][jwplayer_preset]' => 'test_preset',
      'fields[field_video][settings_edit_form][settings][preview_image_field]' => 'node:jw_player|field_image_preview',
      'fields[field_video][settings_edit_form][settings][preview_image_style]' => 'medium',
    ];
    $this->drupalPostForm(NULL, $edit, t('Update'));
    $this->drupalPostForm(NULL, NULL, t('Save'));
    // Make sure JW preset is correct.
    $this->assertText('Preset: Test preset');
    $this->assertText('Dimensions: 100x100, uniform');
    $this->assertText('Preview: image_preview (Medium');
    $this->assertText('Enabled options: Autostart, Mute, Sharing');
    $this->assertText('Sharing sites: Email, LinkedIn');
    // Make sure the formatter reports correct dependencies.
    /** @var \Drupal\Core\Entity\Entity\EntityViewDisplay $view_display */
    $view_display = EntityViewDisplay::load('node.jw_player.default');
    $this->assertTrue(in_array('jw_player.preset.test_preset', $view_display->getDependencies()['config']));

    // Create a 'video' file, which has .mp4 extension.
    $text = 'Trust me I\'m a video';
    file_put_contents('temporary://myVideo.mp4', $text);

    // Upload an image in the node.
    $images = $this->drupalGetTestFiles('image')[1];
    $this->drupalPostForm('node/add/jw_player', [
      'files[field_image_preview_0]' => $images->uri,
    ], t('Upload'));

    // Create video content from JW content type.
    $edit = array(
      'title[0][value]' => 'Test video',
      'files[field_video_0]' => drupal_realpath('temporary://myVideo.mp4'),
      'field_image_preview[0][alt]' => 'preview_image',
    );
    $this->drupalPostForm(NULL, $edit, t('Save and publish'));
    $this->assertText('JW content Test video has been created.');

    $value = $this->xpath('//video/@id');
    $id = (string) $value[0]['id'];

    // Get the player js.
    $player_info = (string) $this->xpath('//script[@data-drupal-selector="drupal-settings-json"]')[0];
    $decoded_info = json_decode($player_info, TRUE);

    // Assert the image and file.
    $image = file_create_url(\Drupal::token()->replace('public://styles/medium/public/[date:custom:Y]-[date:custom:m]/' . $images->filename));
    $this->assertTrue(strpos($decoded_info['jw_player']['players'][$id]['image'], $image) !== FALSE);
    $file = file_create_url(\Drupal::token()->replace('public://[date:custom:Y]-[date:custom:m]/myVideo.mp4'));
    $this->assertEqual($file, $decoded_info['jw_player']['players'][$id]['file']);

    // Make sure the hash is there.
    $this->assertTrue(preg_match('/jwplayer-[a-zA-Z0-9]{1,}$/', $id));
    // Check the library created because of cloud hosting.
    $this->assertRaw('<script src="' . $cloud_library . '"></script>');

    // Change player hosting.
    $edit = [
      'jw_player_hosting' => 'self',
      'jw_player_key' => 'this_is_my_fancy_license_key',
    ];
    $this->drupalPostForm('admin/config/media/jw_player/settings', $edit, t('Save configuration'));
    $this->drupalGet('node/1');
    $value = $this->xpath('//video/@id');
    $id = (string) $value[0]['id'];

    // Get the player js.
    $player_info = (string) $this->xpath('//script[@data-drupal-selector="drupal-settings-json"]')[0];
    $decoded_info = json_decode($player_info, TRUE);

    // Assert the json has been updated.
    $this->assertEqual('100', $decoded_info['jw_player']['players'][$id]['width']);
    $this->assertEqual('100', $decoded_info['jw_player']['players'][$id]['height']);
    $this->assertEqual(TRUE, $decoded_info['jw_player']['players'][$id]['autostart']);
    $this->assertEqual(TRUE, $decoded_info['jw_player']['players'][$id]['mute']);
    $this->assertEqual([0 => 'email', 1 => 'linkedin'], $decoded_info['jw_player']['players'][$id]['sharing']['sites']);
    $this->assertEqual('5', $decoded_info['jw_player']['players'][$id]['advertising']['skipoffset']);
    $this->assertEqual('Skip ad in xx', $decoded_info['jw_player']['players'][$id]['advertising']['skipmessage']);
    $this->assertEqual('Skip', $decoded_info['jw_player']['players'][$id]['advertising']['skiptext']);

    // Check the library created because of cloud hosting.
    $this->assertNoRaw('<script src="' . $cloud_library . '"></script>');

    // Test the formatter for a link field.
    static::fieldUIAddNewField('admin/structure/types/manage/jw_player', 'jw_link', 'JW link', 'link', [], []);
    $this->drupalPostForm('admin/structure/types/manage/jw_player/display', array('fields[field_jw_link][type]' => 'jwplayer_formatter'), t('Save'));
    // Add a new node.
    $this->drupalGet('node/add/jw_player');
    $edit = [
      'title[0][value]' => 'jw_link',
      'field_jw_link[0][uri]' => 'https://www.youtube.com/watch?v=mAAIfi0pYHw',
      'field_jw_link[0][title]' => 'Jw Player Drupal 7',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and publish'));

    $node = $this->drupalGetNodeByTitle('jw_link');
    $this->drupalGet('node/' . $node->id());
    $value = $this->xpath('//video/@id');
    $id = (string) $value[0]['id'];
    // Get the player js.
    $player_info = (string) $this->xpath('//script[@data-drupal-selector="drupal-settings-json"]')[0];
    $decoded_info = json_decode($player_info, TRUE);
    // Check the link info is in the player js.
    $this->assertEqual('https://www.youtube.com/watch?v=mAAIfi0pYHw', $decoded_info['jw_player']['players'][$id]['file']);
  }

  /**
   * Tests the jw player license configuration.
   */
  public function testLicenseConfig() {
    $admin_user = $this->drupalCreateUser(array(
      'administer site configuration',
      'administer JW Player presets',
    ));
    $this->drupalLogin($admin_user);

    $edit = [
      'jw_player_hosting' => 'self',
      'jw_player_key' => $license_key = 'this_is_my_fancy_license_key',
    ];
    $this->drupalPostForm('admin/config/media/jw_player/settings', $edit, t('Save configuration'));

    // Assert the key is saved.
    $this->drupalGet('admin/config/media/jw_player/settings');
    $this->assertFieldByName('jw_player_key', $license_key);

    $edit = [
      'jw_player_hosting' => 'cloud',
      'cloud_player_library_url' => $cloud_url = 'this_is_my_fancy_cloud_url',
    ];
    $this->drupalPostForm('admin/config/media/jw_player/settings', $edit, t('Save configuration'));

    // Assert the cloud url is saved and the license key is cleared.
    $this->drupalGet('admin/config/media/jw_player/settings');
    $this->assertNoFieldByName('jw_player_key', $license_key);
    $this->assertFieldByName('cloud_player_library_url', $cloud_url);
  }
}
