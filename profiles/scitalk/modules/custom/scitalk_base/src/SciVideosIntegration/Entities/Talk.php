<?php
namespace Drupal\scitalk_base\SciVideosIntegration\Entities;

use Drupal\scitalk_base\SciVideosIntegration\Authentication\SciVideosAuthentication;

class Talk extends EntityBase {
    private const TALK_PATH = 'api/node/talk';

    public function __construct(SciVideosAuthentication $scivideos) {
        parent::__construct($scivideos, Talk::TALK_PATH);
    }

    public function fetchByTalkNumber($talk_number) {
        $filter = '?filter[field_talk_number][value]=' . $talk_number;
        return parent::fetch($filter);
    }
}