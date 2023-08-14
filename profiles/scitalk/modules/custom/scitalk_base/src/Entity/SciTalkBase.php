<?php

namespace Drupal\scitalk_base\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the SciTalk Base entity.
 *
 * @ConfigEntityType(
 *   id = "scitalk_base",
 *   label = @Translation("SciTalk Base"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\scitalk_base\SciTalkBaseListBuilder",
 *     "form" = {
 *       "add" = "Drupal\scitalk_base\Form\SciTalkBaseForm",
 *       "edit" = "Drupal\scitalk_base\Form\SciTalkBaseForm",
 *       "delete" = "Drupal\scitalk_base\Form\SciTalkBaseDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\scitalk_base\SciTalkBaseHtmlRouteProvider",
 *     },
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "site_vocabulary",
 *     "scivideos_vocabulary",
 *     "term_mappings"
 *   },
 *   config_prefix = "scitalk_base",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/scitalk_base/{scitalk_base}",
 *     "add-form" = "/admin/structure/scitalk_base/add",
 *     "edit-form" = "/admin/structure/scitalk_base/{scitalk_base}/edit",
 *     "delete-form" = "/admin/structure/scitalk_base/{scitalk_base}/delete",
 *     "collection" = "/admin/structure/scitalk_base"
 *   }
 * )
 */
class SciTalkBase extends ConfigEntityBase implements SciTalkBaseInterface {

  /**
   * The SciTalk Base ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The SciTalk Base label.
   *
   * @var string
   */
  protected $label;

  /**
   * Site vocabulary.
   *
   * @var string
   */
  protected $site_vocabulary;

  /**
   * SciVideos vocabulary.
   *
   * @var string
   */
  protected $scivideos_vocabulary;

  /**
   * Mapped field plugins.
   *
   */
  protected $term_mappings = [];


  public function getSiteVocabulary() {
    return $this->site_vocabulary;
  }

  public function setSiteVocabulary($vocabulary) {
    $this->site_vocabulary = $vocabulary;
  }
  
  public function getSciVideosVocabulary() {
    return $this->scivideos_vocabulary;
  }
  
  public function setSciVideosVocabulary($vocabulary) {
    $this->scivideos_vocabulary = $vocabulary;
  }

  public function getTermMappings() {
    return $this->term_mappings;
  }

  public function setTermMappings($mappings) {
    $this->term_mappings = $mappings;
  }
}
