<?php
namespace Drupal\scitalk_base\SciVideosIntegration\Entities;

use Drupal\scitalk_base\SciVideosIntegration\Authentication\SciVideosAuthentication;

class VocabularyTerms extends EntityBase {
    private const TERM_PATH_PREFIX = 'api/taxonomy_term/';

    public function __construct(SciVideosAuthentication $scivideos, $vocabulary_name) {
        parent::__construct($scivideos, VocabularyTerms::TERM_PATH_PREFIX . $vocabulary_name);
    }
}