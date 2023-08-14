<?php
namespace Drupal\scitalk_base\SciVideosIntegration\Entities;

use Drupal\scitalk_base\SciVideosIntegration\Authentication\SciVideosAuthentication;

class Vocabularies extends EntityBase {
    private const VOCABULARY_PATH = 'api/taxonomy_vocabulary/taxonomy_vocabulary';

    public function __construct(SciVideosAuthentication $scivideos) {
        parent::__construct($scivideos, Vocabularies::VOCABULARY_PATH);
    }
}