<?php

namespace Drupal\scitalk_base\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
//use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\NodeInterface;

/**
 * Provides a 'SciTalks Citations' Block.
 *
 * @Block(
 *   id = "scitalk_citations_block",
 *   admin_label = @Translation("SciTalk Citations Block"),
 *   category = @Translation("SciTalk Citations"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   }
 * )
 */
class SciTalkCitations extends BlockBase implements ContainerFactoryPluginInterface {

    /**
     * Constructs a new SciTalkCitations object.
     *
     * @param array $configuration
     *   A configuration array containing information about the plugin instance.
     * @param string $plugin_id
     *   The plugin_id for the plugin instance.
     * @param mixed $plugin_definition
     *   The plugin implementation definition.
     * @param \Drupal\Core\Session\AccountInterface $current_user
     *   Current user.
   */
    public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->currentUser = $current_user;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('current_user')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function build() {
        $talk_number = $doi = $url = $talk_date = $lang = $year = $title = $site_name = $publisher = $repo = '';
        $speakers = $keywords = [];

        $pirsa = $this->getContextValue('node');
        if ($pirsa instanceof \Drupal\node\NodeInterface) {           
            $config = \Drupal::config('system.site');
            $datacite_config = \Drupal::config('scitalk_base.settings');

            //use the site name as prefix for the BiTeX id e.g. "@misc{ pirsa_19100012,  ..."
            $site_name = str_replace(' ', '_', strtolower($config->get('name')));

            $title = $pirsa->title->value ?? '';
            $talk_number = $pirsa->field_talk_number->value ?? '';
            $talk_date = $pirsa->field_talk_date->value ?? '';
            $talk_date_formatted = $talk_date ? date('M. d, Y', strtotime($talk_date)) : '';
            $year = $talk_date ? date('Y', strtotime($talk_date)) : '';
            $doi = $pirsa->field_talk_doi->value ?? '';
            $url = $pirsa->field_talk_source_event->uri;
            $lang = $pirsa->langcode->value ?? '';

            //using the site name for the repository field that shows in APA and MLA citations:
            $repository = $config->get('name') ?? '';

            //use for publisher the entry in "DataCite Creator Institution" from the Scitalk configuration form:
            $publisher = $datacite_config->get('datacite_creator_institution') ?? '';

            $speakers = array_map(function($sp) {
                //create name initials 
                $initials = array_reduce( explode(' ', $sp->field_sp_first_name->value), function($carry, $item) {
                    $carry .= strtoupper($item[0]) . '.';
                    return $carry;
                });

                return [
                    'first' => $sp->field_sp_first_name->value, 
                    'last' => $sp->field_sp_last_name->value, 
                    'initials' => $initials, 
                    'display_name' => $sp->field_sp_display_name->value
                ];

            }, $pirsa->field_talk_speaker_profile->referencedEntities());

            //get scientific areas
            $sa = array_map(function($sa) {
                return $sa->name->value;
            }, $pirsa->field_scientific_area->referencedEntities());

            //get talk keywords
            $kws = array_map(function($kw) {
                return $kw->name->value;
            }, $pirsa->field_talk_keywords->referencedEntities());

            //Citations keywords are scientific areas and talk keywords
            $keywords = array_unique( array_merge($sa, $kws) );
            
        }

        return [
            '#theme' => 'scitalk_citations_block',
            '#cache' => ['contexts' => ['url.path']],
            '#title' => $title,
            '#talk_number' => $talk_number,
            '#talk_date' => $talk_date,
            '#talk_date_formatted' => $talk_date_formatted,
            '#year' => $year,
            '#doi' => $doi,
            '#url' => $url,
            '#language' => $lang,
            '#site_name' => $site_name,
            '#speakers' => $speakers,
            '#keywords' => $keywords,
            '#publisher' => $publisher,
            '#repository' => $repository
        ];

    }

  }
