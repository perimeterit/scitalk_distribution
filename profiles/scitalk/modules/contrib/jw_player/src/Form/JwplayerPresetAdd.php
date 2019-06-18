<?php

namespace Drupal\jw_player\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
/**
 * Configure search settings for this site.
 */
class JwplayerPresetAdd extends EntityForm {

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\jw_player\Jw_playerInterface
   */
  protected $entity;

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Construct a new PageFormBase.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query factory.
   */
  public function __construct(QueryFactory $entity_query) {
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $preset = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#size' => 20,
      '#maxlength' => 255,
      '#title' => $this->t('Preset name'),
      '#description' => $this->t('Enter name for the preset.'),
      '#default_value' => $preset->label(),
      '#required' => TRUE,
      '#weight' => 0,
    );

    $form['id'] = array(
      '#title' => t('Machine name'),
      '#type' => 'machine_name',
      '#default_value' => $preset->id(),
      '#machine_name' => array(
        'exists' => array($this, 'exists'),
      ),
      '#weight' => 1,
      '#description' => t('Enter the machine name for the preset. It must be unique and contain only alphanumeric characters and underscores.'),
    );

    $form['description'] = array(
      '#type' => 'textarea',
      '#size' => 10,
      '#title' => t('Description'),
      '#description' => t('Summary for the preset.'),
      '#default_value' => $preset->description,
      '#weight' => 2,
    );

    $form['settings'] = array(
      '#type' => 'fieldset',
      '#title' => 'Settings',
      '#tree' => TRUE,
      '#weight' => 5,
    );

    if (jw_player_use_legacy()) {
      $disabled = TRUE;
      $desc = t('When creating JW Player presets, select if the source should be defined on Drupal, or by using definitions set within your JWPlayer.com account. <strong>This option is only available for sites using JW Player 7 or above.</strong>');
    }
    else {
      $disabled = FALSE;
      $desc = t('When creating JW Player presets, select if the source should be defined on Drupal, or by using definitions set within your JWPlayer.com account.');
    }
    $form['settings']['preset_source'] = [
      '#type' => 'radios',
      '#title' => t('Preset source'),
      '#disabled' => $disabled,
      '#options' => [
        'drupal' => t('Use Drupal-defined presets'),
        'jwplayer' => t('Use presets defined on JWPlayer.com'),
      ],
      '#default_value' => $preset->getSetting('preset_source') ? $preset->getSetting('preset_source') : 'drupal',
      '#description' => $desc,
      '#weight' => 0,
    ];

    $skin_options = [];
    // Some settings are suitable for legacy versions only.
    if (jw_player_use_legacy()) {
      $form['settings']['mode'] = [
        '#type' => 'radios',
        '#title' => t('Embed mode'),
        '#description' => t('Select your primary embed mode. Choosing HTML5 primary means that modern browsers that also support flash will use the HTML5 player first where possible. While this is desirable, the Flash based player supports more features and is generally more reliable.'),
        '#options' => array(
          'flash' => t('Flash primary, HTML5 failover'),
          'html5' => t('HTML5 primary, Flash failover'),
        ),
        '#default_value' => $preset->getSetting('mode') ? $preset->getSetting('mode') : 'html5',
        '#weight' => 1,
        '#states' => [
          'visible' => [
            ':input[name="settings[preset_source]"]' => ['value' => 'drupal'],
          ],
        ],
      ];

      // Legacy Skins.
      $skin_options = [
        'bekle' => 'Bekle*',
        'modieus' => 'Modieus*',
        'glow' => 'Glow*',
        'five' => 'Five*',
        'beelden' => 'Beelden*',
        'stormtrooper' => 'Stormtrooper*',
        'vapor' => 'Vapor*',
        'roundster' => 'Roundster*'
      ];
      $skin_desc = t('Select a player skin. Some skins (*) require a premium license.');
    }
    else {
      // JW Player 7 Skins if cloud-hosting. If self-hosting, jw_player_skins()
      // function retrieves all possible custom and library skins.
      if ($preset->getSetting('jw_player_hosting') == 'cloud') {
        $skin_options = array(
          'beelden' => 'Beelden',
          'bekle' => 'Bekle',
          'five' => 'Five',
          'glow' => 'Glow',
          'roundster' => 'Roundster',
          'seven' => 'Seven',
          'six' => 'Six',
          'stormtrooper' => 'Stormtrooper',
          'vapor' => 'Vapor',
        );
      }
      $skin_url = 'https://support.jwplayer.com/customer/portal/articles/1406968-using-jw-player-skins';
      $skin_desc = t('Select a player skin. <a href="@url" target="_blank">Click here</a> to see skins provided by JW Player.', [
        '@url' => $skin_url,
      ]);
    }

    // For legacy, add custom skins. For newer, add only custom skins if
    // cloud-hosting, or add custom and library skins for self-hosting.
    foreach (jw_player_skins() as $skin) {
      $skin_options[$skin->name] = Unicode::ucfirst($skin->name);
    }

    $form['settings']['skin'] = [
      '#title' => t('Skin'),
      '#description' => $skin_desc,
      '#type' => 'select',
      '#default_value' => $preset->getSetting('skin') ? $preset->getSetting('skin') : FALSE,
      '#empty_option' => t('None (default skin)'),
      '#options' => $skin_options,
      '#weight' => 2,
      '#states' => [
        'visible' => [
          ':input[name="settings[preset_source]"]' => ['value' => 'drupal'],
        ],
      ],
    ];

    $form['settings']['stretching'] = array(
      '#title' => t('Stretching'),
      '#description' => t('How to resize the poster and video to fit the display.'),
      '#type' => 'select',
      '#default_value' => $preset->getSetting('stretching') ? $preset->getSetting('stretching') : 'uniform',
      '#weight' => 3,
      '#options' => array(
        'none' => t('None (keep original dimensions)'),
        'exactfit' => t('Exact Fit (stretch disproportionally)'),
        'uniform' => t('Uniform (stretch proportionally; black borders)'),
        'fill' => t('Fill (stretch proportionally; parts cut off)'),
      ),
      '#states' => [
        'visible' => [
          ':input[name="settings[preset_source]"]' => ['value' => 'drupal'],
        ],
      ],
    );

    $form['settings']['responsive'] = array(
      '#type' => 'checkbox',
      '#title' => 'Use a Responsive Design',
      '#description' => t('Enable Responsive Design using a percentage based width and an aspect ratio.'),
      '#default_value' => $preset->getSetting('responsive') ? $preset->getSetting('responsive') : FALSE,
      '#weight' => 4,
      '#states' => [
        'visible' => [
          ':input[name="settings[preset_source]"]' => ['value' => 'drupal'],
        ],
      ],
    );

    $form['settings']['width'] = array(
      '#type' => 'number',
      '#min' => 1,
      '#size' => 10,
      '#title' => t('Width'),
      '#description' => t('Enter the width for this player.'),
      '#field_suffix' => ' ' . t('pixels'),
      '#default_value' => $preset->getSetting('width') ? $preset->getSetting('width') : NULL,
      '#required' => TRUE,
      '#weight' => 5,
      '#states' => [
        'required' => [
          ':input[name="settings[preset_source]"]' => ['value' => 'drupal'],
        ],
        'visible' => [
          ':input[name="settings[preset_source]"]' => ['value' => 'drupal'],
        ],
      ],
    );

    $form['settings']['height'] = array(
      '#type' => 'number',
      '#min' => 1,
      '#size' => 10,
      '#title' => t('Height'),
      '#description' => t('Enter the height for this player.'),
      '#field_suffix' => ' ' . t('pixels'),
      '#default_value' => $preset->getSetting('height') ? $preset->getSetting('height') : NULL,
      '#weight' => 6,
      '#states' => array(
        'required' => array(
          [
            ':input[name="settings[preset_source]"]' => ['value' => 'drupal'],
            ':input[name="settings[responsive]"]' => ['checked' => FALSE],
          ]
        ),
        'visible' => array(
          ':input[name="settings[responsive]"]' => array('checked' => FALSE),
          ':input[name="settings[preset_source]"]' => ['value' => 'drupal'],
        ),
      ),
    );

    $form['settings']['aspectratio'] = array(
      '#type' => 'textfield',
      '#size' => 7,
      '#title' => t('Aspect ratio'),
      '#description' => t('Enter the aspect ratio for this player.'),
      '#default_value' => $preset->getSetting('aspectratio') ? $preset->getSetting('aspectratio') : NULL,
      '#weight' => 6,
      '#states' => array(
        'required' => array(
          [
            ':input[name="settings[responsive]"]' => ['checked' => TRUE],
            ':input[name="settings[preset_source]"]' => ['value' => 'drupal'],
          ]
        ),
        'visible' => array(
          ':input[name="settings[responsive]"]' => array('checked' => TRUE),
          ':input[name="settings[preset_source]"]' => ['value' => 'drupal'],
        ),
      ),
    );

    $form['settings']['autostart'] = [
      '#title' => t('Autostart'),
      '#type' => 'checkbox',
      '#description' => t('Automatically start playing the video on page load. Can be true or false (default). Autostart does not work on mobile devices like iOS and Android.'),
      '#default_value' => $preset->getSetting('autostart') ? $preset->getSetting('autostart') : 'false',
      '#weight' => 7,
      '#states' => [
        'visible' => [
          ':input[name="settings[preset_source]"]' => ['value' => 'drupal'],
        ],
      ],
    ];

    $form['settings']['mute'] = [
      '#type' => 'checkbox',
      '#title' => t('Mute'),
      '#description' => t('Mute the player by default during playback. This can be useful when combined with the autostart option. Cannot control settings of external sources such as YouTube.'),
      '#default_value' => $preset->getSetting('mute') ? $preset->getSetting('mute') : FALSE,
      '#weight' => 8,
      '#states' => [
        'visible' => [
          ':input[name="settings[preset_source]"]' => ['value' => 'drupal'],
        ],
      ],
    ];

    // Social media sharing.
    if (!jw_player_use_legacy()) {
      $form['settings']['sharing'] = [
        '#type' => 'checkbox',
        '#title' => t('Sharing'),
        '#description' => t('Enable the social sharing overlay. If no sharing options are selected, the page URL link with default sharing sites will be shown.'),
        '#default_value' => $preset->getSetting('sharing') ? $preset->getSetting('sharing') : FALSE,
        '#weight' => 9,
        '#states' => [
          'visible' => [
            ':input[name="settings[preset_source]"]' => ['value' => 'drupal'],
          ],
        ],
      ];
      $form['settings']['sharing_heading'] = [
        '#type' => 'textfield',
        '#title' => t('Sharing heading text'),
        '#description' => t('Short, instructive text to display at the top of the sharing screen.'),
        '#default_value' => $preset->getSetting('sharing_heading') ? $preset->getSetting('sharing_heading') : t('Share Video'),
        '#weight' => 10,
        '#states' => [
          'visible' => [
            ':input[name="settings[sharing]"]' => ['checked' => TRUE],
            ':input[name="settings[preset_source]"]' => ['value' => 'drupal'],
          ],
        ],
      ];

      $sites = jw_player_sharing_sites();
      $weight = count($sites) + 1;
      $sharing_sites_order = [];
      foreach ($sites as $site_id => $sharing_site) {
        $sharing_sites_order[$site_id] = [
          'label' => $sharing_site,
          'enabled' => $preset->getSetting('sharing_sites')['sites'][$site_id]['enabled'] ?: FALSE,
          'weight' => $preset->getSetting('sharing_sites')['sites'][$site_id]['weight'] ?: $weight,
        ];
        $weight++;
      }

      $form['settings']['sharing_sites'] = [
        '#type' => 'item',
        '#weight' => 11,
      ];
      $form['settings']['sharing_sites']['sites'] = [
        '#type' => 'table',
        '#header' => [t('Label'), t('Weight')],
        '#empty' => t('There are no items yet. Add an item.'),
        '#suffix' => '<div class="description">' . $this->t('The social networks sites that are enabled for sharing. Select none to allow all sharing sites.') .'</div>',
        '#tabledrag' => [
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => 'jw-player-sharing-sites-order-weight',
          ],
        ],
        '#states' => [
          'visible' => [
            ':input[name="settings[sharing]"]' => ['checked' => TRUE],
            ':input[name="settings[preset_source]"]' => ['value' => 'drupal'],
          ],
        ],
      ];

      uasort($sharing_sites_order, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

      foreach ($sharing_sites_order as $site_id => $sharing_site) {
        $form['settings']['sharing_sites']['sites'][$site_id]['#attributes']['class'][] = 'draggable';
        $form['settings']['sharing_sites']['sites'][$site_id]['enabled'] = [
          '#type' => 'checkbox',
          '#title' => $sharing_site['label'],
          '#title_display' => 'after',
          '#default_value' => $sharing_site['enabled'],
        ];
        $form['settings']['sharing_sites']['sites'][$site_id]['weight'] = [
          '#type' => 'weight',
          '#title' => t('Weight for @title', ['@title' => $sharing_site['label']]),
          '#title_display' => 'invisible',
          '#delta' => 50,
          '#default_value' => (int) $sharing_site['weight'],
          '#array_parents' => [
            'settings',
            'sites',
            $site_id
          ],
          '#attributes' => ['class' => ['jw-player-sharing-sites-order-weight']],
        ];
      }
    }

    $form['settings']['controlbar'] = array(
      '#title' => t('Controlbar Position'),
      '#type' => 'select',
      '#description' => t('Where the controlbar should be positioned.'),
      '#default_value' => $preset->getSetting('controlbar') ? $preset->getSetting('controlbar') : 'none',
      '#options' => array(
        'none' => t('None'),
        'bottom' => t('Bottom'),
        'top' => t('Top'),
        'over' => t('Over'),
      ),
      '#weight' => 12,
      '#states' => [
        'visible' => [
          ':input[name="settings[preset_source]"]' => ['value' => 'drupal'],
        ],
      ],
    );

    // Add preset plugin settings.
    foreach (jw_player_preset_plugins() as $plugin => $info) {
      $form['settings']['plugins']['#weight'] = 13;

      // Fieldset per plugin.
      $form['settings']['plugins'][$plugin] = array(
        '#type' => 'fieldset',
        '#title' => SafeMarkup::checkPlain($info['name']),
        '#description' => SafeMarkup::checkPlain($info['description']),
        '#tree' => TRUE,
        '#weight' => 10,
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
        '#states' => [
          'visible' => [
            ':input[name="settings[preset_source]"]' => ['value' => 'drupal'],
          ],
        ],
      );

      // Enable/disable plugin setting.
      $form['settings']['plugins'][$plugin]['enable'] = array(
        '#type' => 'checkbox',
        '#title' => t('Enable'),
        '#description' => SafeMarkup::checkPlain($info['description']),
      );

      // Add each config option specified in the plugin. Config options should
      // be in FAPI structure.
      if (is_array($info['config options']) and !empty($info['config options'])) {
        foreach ($info['config options'] as $option => $element) {
          // Note: Each config option must be a complete FAPI element, except
          // for the #title which is optional. If the #title is not provided, we
          // use the name of the config option as the title.
          if (!isset($element['#title'])) {
            $element['#title'] = Unicode::ucfirst($option);
          }
          // Alter the default value if a setting has been saved previously.
          $element['#default_value'] = !empty($preset['plugins'][$plugin][$option]) ? $preset['plugins'][$plugin][$option] : $element['#default_value'];
          // Make the whole element visible only if the plugin is checked
          // (enabled).
          $element['#states'] = array(
            'visible' => array(
              'input[name="settings[plugins][' . $plugin . '][enable]"]' => array('checked' => TRUE),
            ),
          );
          // Add the element to the FAPI structure.
          $form['settings']['plugins'][$plugin][$option] = $element;
        }
      }
    }

    $form['settings']['advertising'] = array(
      '#type' => 'details',
      '#title' => $this->t('Advertising'),
      '#description' => $this->t('This requires an enterprise license. See the <a href="@jwplayer_documentation_url">JW Player documentation</a> about preroll ads for more information'),
      '#weight' => 14,
      '#states' => [
        'visible' => [
          ':input[name="settings[preset_source]"]' => ['value' => 'drupal'],
        ],
      ],
    );

    $form['settings']['advertising']['client'] = array(
      '#type' => 'select',
      '#title' => $this->t('Client'),
      '#options' => array(
        'vast' => $this->t('VAST/VPAID'),
        'googima' => t('Google IMA Preroll'),
      ),
      '#empty_option' => $this->t('No advertising'),
      '#default_value' => !empty($preset->getSetting('advertising')['client']) ? $preset->getSetting('advertising')['client'] : NULL,
    );

    $form['settings']['advertising']['tag'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Pre Tag'),
      '#maxlength' => 1024,
      '#description' => $this->t('Set this to the URL of the ad tag that contains the pre-roll ad.'),
      '#default_value' => !empty($preset->getSetting(['advertising', 'tag'])) ? $preset->getSetting(['advertising', 'tag']) : NULL,
      '#states' => array(
        'invisible' => array(
          ':input[name="settings[advertising][client]"]' => array('value' => ''),
        ),
      ),
    );

    $form['settings']['advertising']['tag_post'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Post Tag'),
      '#maxlength' => 1024,
      '#description' => $this->t('Set this to the URL of the ad tag that contains the post-roll ad.'),
      '#default_value' => !empty($preset->getSetting(['advertising', 'tag_post'])) ? $preset->getSetting(['advertising', 'tag_post']) : NULL,
      '#states' => [
        'invisible' => [
          ':input[name="settings[advertising][client]"]' => ['value' => ''],
        ],
      ],
    ];

    $form['settings']['advertising']['skipoffset'] = [
      '#type' => 'number',
      '#title' => t('Skip offset'),
      '#description' => t('Add skip offset in VAST adds.'),
      '#default_value' => $preset->getSetting(['advertising', 'skipoffset']),
      '#weight' => 5,
      '#states' => [
        'invisible' => [
          ':input[name="settings[advertising][client]"]' => ['value' => ''],
        ],
      ],
    ];

    $form['settings']['advertising']['skipmessage'] = [
      '#type' => 'textfield',
      '#title' => t('Skip message'),
      '#description' => t('Customized countdown message. If empty message is "Skip ad in xx"'),
      '#default_value' => $preset->getSetting(['advertising', 'skipmessage']),
      '#weight' => 6,
      '#states' => [
        'invisible' => [
          ':input[name="settings[advertising][client]"]' => ['value' => ''],
        ],
      ],
    ];

    $form['settings']['advertising']['skiptext'] = [
      '#type' => 'textfield',
      '#title' => t('Skip text'),
      '#description' => t('Text of the Skip button after the countdown is over. If empty text is "Skip"'),
      '#default_value' => $preset->getSetting(['advertising', 'skiptext']),
      '#weight' => 7,
      '#states' => [
        'invisible' => [
          ':input[name="settings[advertising][client]"]' => ['value' => ''],
        ],
      ],
    ];

    $form['settings']['player_library_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cloud Player Library Url'),
      '#description' => $this->t('Enter the URL to the player created on JWPlayer.com that contains the settings for this preset.'),
      '#default_value' => $preset->getSetting('player_library_url') ?: FALSE,
      '#states' => [
        'required' => [
          ':input[name="settings[preset_source]"]' => ['value' => 'jwplayer'],
        ],
        'visible' => [
          ':input[name="settings[preset_source]"]' => ['value' => 'jwplayer'],
        ],
      ],
    ];

    $form['#attached']['library'][] = 'jw_player/preset';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function exists($machine_name) {
    return (bool) $this->entityQuery->get('jw_player')
      ->condition('id', $machine_name)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $preset = $this->entity;
    if ($preset->getSetting('preset_source') == 'drupal') {
      if (!$preset->getSetting('width')) {
        $form_state->setErrorByName('settings][width', t('Width field is required.'));
      }
      if ($preset->getSetting('responsive')) {
        if ($preset->getSetting('width') > 100) {
          $form_state->setErrorByName('settings][width', t('Width field must be less than 100%.'));
        }
        // Aspect Ratio Validation.
        if (!$preset->getSetting('aspectratio')) {
          $form_state->setErrorByName('settings][aspectratio', t('Aspect Ratio field is required if Responsive Design is enabled.'));
        }
        elseif (!preg_match('/^([0-9]*):([0-9]*)$/', $preset->getSetting('aspectratio'), $matches) ||
          (!is_numeric($matches[1]) || !is_numeric($matches[2]))
        ) {
          $form_state->setErrorByName('settings][aspectratio', $this->t('Aspect Ratio field must be of the format of two numbers separated by a colon. For example, <em>16:9</em>.'));
        }
      }
      elseif (!$preset->getSetting('height')) {
        $form_state->setErrorByName('settings][height', t('Height field is required.'));
      }
    }
    else {
      preg_match(jw_player_library_url_regex(), $preset->getSetting('player_library_url'), $matches);
      if (!isset($matches[2])) {
        $form_state->setErrorByName('settings][player_library_url', t('Player Library URL does not match format provided by JWPlayer.com.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $preset = $this->entity;
    if ($preset->getSetting('responsive')) {
      unset($preset->settings['height']);
    }
    else {
      unset($preset->settings['aspectratio']);
    }
    if (!$preset->getSetting('sharing')) {
      unset($preset->settings['sharing_heading']);
      unset($preset->settings['sharing_sites']);
    }
    if ($preset->getSetting('preset_source') == 'jwplayer') {
      foreach ($preset->getSettings() as $key => $value) {
        if ($key != 'preset_source' && $key != 'player_library_url') {
          unset($preset->settings[$key]);
        }
      }
    }
    else {
      unset($preset->settings['player_library_url']);
    }
    $preset->save();
    drupal_set_message($this->t('Saved the %label Preset.', array('%label' => $preset->label())));
    $form_state->setRedirect('entity.jw_player.collection');
  }
}
