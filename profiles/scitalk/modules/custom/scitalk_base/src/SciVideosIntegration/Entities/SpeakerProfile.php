<?php
namespace Drupal\scitalk_base\SciVideosIntegration\Entities;

use Drupal\scitalk_base\SciVideosIntegration\Authentication\SciVideosAuthentication;

class SpeakerProfile extends EntityBase {
    private const SPEAKER_PROFILE_PATH = 'api/node/speaker_profile';

    public function __construct(SciVideosAuthentication $scivideos) {
        parent::__construct($scivideos, SpeakerProfile::SPEAKER_PROFILE_PATH);
    }

    public function fetchByExternalID($external_id) {
        $filter = '?filter[external_id][value]=' . $external_id;
        return parent::fetch($filter);
    }
}