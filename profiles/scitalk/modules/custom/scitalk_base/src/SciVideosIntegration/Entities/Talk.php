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

    public function fetchTalksUnderCollectionById($collection_uuid) {
        $filter = '?filter[collection][condition][path]=talk_collection.id&filter[collection][condition][operator]==&filter[collection][condition][value]=' . $collection_uuid;
        return parent::fetch($filter);
    }

    public function fetchTalksBySpeakerProfileId($speaker_profile_uuid) {
        $filter = '?filter[speaker][condition][path]=talk_speaker_profile.id&filter[speaker][condition][operator]==&filter[speaker][condition][value]=' . $speaker_profile_uuid;
        return parent::fetch($filter);
    }
}