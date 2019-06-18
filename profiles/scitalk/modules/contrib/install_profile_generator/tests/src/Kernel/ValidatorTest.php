<?php

namespace Drupal\Tests\install_profile_generator\Kernel {

  use Drupal\install_profile_generator\Services\Validator;
  use Drupal\KernelTests\KernelTestBase;

  /**
   * Tests the Validator service.
   *
   * @group install_profile_generator
   */
  class ValidatorTest extends KernelTestBase {

    protected static $modules = ['system'];

    /**
     * Tests a successful validation.
     */
    public function testValidate() {
      $this->installProfile('minimal');
      $validator = new Validator(\Drupal::service('app.root'), \Drupal::service('module_handler'), \Drupal::service('theme_handler'), \Drupal::service('transliteration'), 'minimal');
      $validator->validate('test', 'test');
      $this->addToAssertionCount(1);
    }

    /**
     * Tests calling validation with no name.
     */
    public function testValidateNoName() {
      $this->setExpectedException(\Exception::class, 'To generate a new profile using Drush you have to provide a name or a machine name for the new profile.');
      $this->installProfile('minimal');
      $validator = new Validator(\Drupal::service('app.root'), \Drupal::service('module_handler'), \Drupal::service('theme_handler'), \Drupal::service('transliteration'), 'minimal');
      $validator->validate('', 'test');
    }

    /**
     * Tests calling validation with no machine name.
     */
    public function testValidateNoMachineName() {
      $this->setExpectedException(\Exception::class, 'To generate a new profile using Drush you have to provide a name or a machine name for the new profile.');
      $this->installProfile('minimal');
      $validator = new Validator(\Drupal::service('app.root'), \Drupal::service('module_handler'), \Drupal::service('theme_handler'), \Drupal::service('transliteration'), 'minimal');
      $validator->validate('test', '');
    }

    /**
     * Tests calling validation with an invalid machine name.
     */
    public function testValidateInvalidMachineName() {
      $this->setExpectedException(\Exception::class, 'To generate a new profile using Drush you have to provide a valid machine name. Can only contain lowercase letters, numbers, and underscores.');
      $this->installProfile('minimal');
      $validator = new Validator(\Drupal::service('app.root'), \Drupal::service('module_handler'), \Drupal::service('theme_handler'), \Drupal::service('transliteration'), 'minimal');
      $validator->validate('test', 'test?');
    }

    /**
     * Tests install profiles with sub-modules that are enabled throw an error.
     */
    public function testValidateExtensionsInProfile() {
      $this->setExpectedException(\Exception::class, 'The current profile contains extensions. It is not possible to generate a new profile using Drush.');
      $this->installProfile('testing');
      $this->container->get('module_installer')->install(['drupal_system_listing_compatible_test']);
      $validator = new Validator(\Drupal::service('app.root'), \Drupal::service('module_handler'), \Drupal::service('theme_handler'), \Drupal::service('transliteration'), 'testing');
      $validator->validate('test', 'test');
    }

    /**
     * Tests that machine name does not clash with a module.
     */
    public function testValidateModuleNameClash() {
      $this->setExpectedException(\Exception::class, 'The machine name node already exists');
      $this->installProfile('minimal');
      $validator = new Validator(\Drupal::service('app.root'), \Drupal::service('module_handler'), \Drupal::service('theme_handler'), \Drupal::service('transliteration'), 'minimal');
      $validator->validate('test', 'node');
    }

    /**
     * Tests that machine name does not clash with a theme.
     */
    public function testValidateThemeNameClash() {
      $this->setExpectedException(\Exception::class, 'The machine name classy already exists');
      $this->installProfile('minimal');
      $validator = new Validator(\Drupal::service('app.root'), \Drupal::service('module_handler'), \Drupal::service('theme_handler'), \Drupal::service('transliteration'), 'minimal');
      $validator->validate('test', 'classy');
    }

    /**
     * Installs a install profile for testing.
     *
     * @param $name
     *   Install profile to install.
     */
    protected function installProfile($name) {
      $config = $this->config('core.extension');
      $modules = $config->get('module');
      $modules[$name] = 1000;
      $config
        ->set('profile', $name)
        ->set('module', $modules)
        ->save();
      \Drupal::service('kernel')->rebuildContainer();
    }

  }

}

namespace {

  use Drupal\Component\Render\FormattableMarkup;

  if (!function_exists('dt')) {

    /**
     * Dummy replacement for testing as Drush methods are not available.
     *
     * @param string $message
     *   A string containing placeholders. The string itself will not be escaped,
     *   any unsafe content must be in $args and inserted via placeholders.
     * @param array $arguments
     *   An array with placeholder replacements, keyed by placeholder. See
     *   \Drupal\Component\Render\FormattableMarkup::placeholderFormat() for
     *   additional information about placeholders.
     *
     * @return string
     *   The string with the placeholders replaced.
     */
    function dt($message, array $arguments = []) {
      return (string) (new FormattableMarkup($message, $arguments));
    }

  }
}