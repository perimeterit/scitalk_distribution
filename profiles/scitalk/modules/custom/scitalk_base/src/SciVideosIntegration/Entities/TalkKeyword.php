<?php
namespace Drupal\scitalk_base\SciVideosIntegration\Entities;

use Drupal\scitalk_base\SciVideosIntegration\Authentication\SciVideosAuthentication;

class TalkKeyword extends EntityBase {
    private const PATH = 'api/taxonomy_term/talk_keywords';

    public function __construct(SciVideosAuthentication $scivideos) {
        parent::__construct($scivideos, TalkKeyword::PATH);
    }

    public function fetchByName($name) {
        $filter = '?filter[name][value]=' . $name;
        return parent::fetch($filter);
    }
}