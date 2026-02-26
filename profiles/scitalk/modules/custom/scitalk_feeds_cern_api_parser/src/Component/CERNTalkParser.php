<?php
namespace Drupal\scitalk_feeds_cern_api_parser\Component;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Queue\QueueFactory;

// This class is responsible for parsing the data fetched from the CERN API and mapping it to the desired structure for the SciTalk feeds. 
// It also handles the creation of Collection entities based on the collections associated with each talk, and adds subtitle download tasks to a queue if subtitles are available for a talk.
class CERNTalkParser  {
    private $cernVideoURL;
    private $queueFactory;
    private $feedGroupID;

    public function __construct(QueueFactory $queueFactory, $feedGroupID) {
        $this->feedGroupID = $feedGroupID;
        $this->queueFactory = $queueFactory;
        $this->cernVideoURL = 'https://videos.cern.ch/record/';
    }

     /**
     * Parses the feed data and returns an array of data.
     *
     * @param object $data
     *   The json object containing CERN data.
     *
     * @return array
     *   an array of all mapped data for the CERN Feed.
    */

    /**
     * Returns an array of data for the CERN feed.
     *
     * @param object $data
     *   The json object containing CERN data.
     *
     * @return array
     *   an array of all mapped data for the CERN Feed.
    */
    public function parseData($data): array {
        $talks = [];
        $hits = $data->hits->hits ?? [];
        foreach($hits as $talk) {
            $talks[] = $this->parseTalkItem($talk);
        }
        $return = [
            'talks' => $talks,
            'next' => $data->links->next ?? null,
            'total' => $data->hits->total ?? 0,
        ];
        return $return;
    }

    /**
     * Parses a talk item from the CERN API and returns an array of data.
     *
     * @param object $talk
     *   The talk item to parse.
     *
     * @return array
     *   An array of data extracted from the talk item.
     */
    private function parseTalkItem($talk): array {
        $talk_item = $talk->metadata ?? null;
    
        $data = [
            'talk_number' => '',
            'title' => '',
            'description' => '',
            'video_url' => '',
            'source_event' => '',
            'speakers' => '',
            'talk_date' => '',
            'location' => '',
            'collection' => '',
            'duration' => '',
            'subject' => '',
            // 'subtitles' => '',
        ];

        $video_url = $this->cernVideoURL . $talk_item->recid;

        // Map the talk and video item data to the desired structure.
        $data['talk_number'] = $talk_item->recid ?? '';
        $data['title'] = $talk_item->title?->title ?? '';
        $data['video_url'] = $video_url;

        //the description field contains links to the video snapshots, parse them a create the appropiate links to the video snapshots in CERN:
        $data['description'] = $this->replaceSlideLinksInDescription($video_url, $talk_item->description);
        
        $data['talk_date'] = $this->fixMissingTimeinDates($talk_item->date  ?? $talk_item->publication_date ?? '');
        $data['location'] = $talk_item->location ?? '';
        $data['speakers'] = $this->parseTalkSpeakers($talk_item->contributors ?? []);

        // changed to pull this data from alternate_identifiers instead of related_identifiers as the source event info:
        // $data['source_event'] = $this->parseSourceEventFromRelatedIdentifiers($talk_item->related_identifiers ?? []);
        $data['source_event'] = $this->parseSourceEventFromAlternateIdentifiers($talk_item->alternate_identifiers ?? []);

        $data['collection'] = $this->parseTalkCollections($talk_item?->collections ?? []);
        $data['duration'] = $this->timeToSeconds($talk_item->duration ?? '');
        $data['subject'] = $this->getSubjectByName('Physics'); // default subject for all CERN talks, as they are all physics related

        $subtitles = $this->parseSubtitles($talk_item->_files ?? []);
        // download subtitles in a cron job:
        if (!empty($subtitles)) {
            $subtitles_data = [
                'talk_number' => $talk_item->recid ?? '',
                'source_repo_id' => $this->feedGroupID,
                'subtitles' => $subtitles,
            ];
            $this->queueFactory->get('scitalk_feeds_cern_subtitle_downloader_queue')->createItem($subtitles_data);
        }

        return $data;
    }

    /**
     * Returns string containing the speakers
     *
     * @param array $contributors
     *   The array of contributors.
     *
     * @return string
     *   a string containing the list of speakers.
    */
    private function parseTalkSpeakers($contributors): string {
        $speakers = '';
        foreach ($contributors as $contributor) {
            if ($contributor->role == 'Speaker') {
                $name = $contributor->name;
                $affiliations = !empty($contributor?->affiliations) ? implode(", ", $contributor->affiliations) : '';
                $speaker = $name . ($affiliations ? ' (' . $affiliations . ')' : '');
                $speakers .= $speakers == '' ?  $speaker : ', ' . $speaker;
            }
        }
        return $speakers;
    }

    /**
     * Returns a CERN talk source event info: to an Indico Contribution or the parent Event
     * searching the alternate_identifiers for a source if none found return an empty string.
     *
     * @param array $sources
     *   The array of sources.
     *
     * @return string
     *   a string containing source event.
    */
    private function parseSourceEventFromAlternateIdentifiers($sources) {
        // it's usually just one entry in the alternate_identifiers array with the source event info, but just in case there are more than one we will look for the one with the scheme "CERN-Event-Contribution-ID" to get the Contribution id, if not found look for a source with scheme "CERN-Event-ID" to get the parent Event id, if none of those are found return an empty string.
        foreach ($sources as $source) {
            if ($source->scheme == 'URL') {
                return $source->value ?? '';
            }
        }
        return '';
    }

    /**
     * Returns a CERN talk source event info: to an Indico Contribution or the parent Event
     * searching the related_identifiers for a source with scheme "CERN-Event-Contribution-ID" to get the Contribution id, if not found look for a source with scheme "CERN-Event-ID" to get the parent Event id, if none of those are found return an empty string.
     *
     * @param array $sources
     *   The array of sources.
     *
     * @return string
     *   a string containing source event.
    */
    private function parseSourceEventFromRelatedIdentifiers($sources) {
        $sourceEvent = '';
        foreach ($sources as $source) {
            if (str_contains($source->identifier, 'contributions')) {
                $sourceEvent = $source->identifier;
                return $sourceEvent;
            }
            elseif ($source->scheme == 'URL') {
                $sourceEvent = $source->identifier;
            }
        }
        return $sourceEvent;
    }

    /**
     * Returns string containing the Collection id's
     *
     * @param array $collection
     *   The title of the collection.
     *
     * @return string
     *   a string containing the Collection's id separated by ; if more than 1.
    */
    private function parseTalkCollections($collections): string {
        $talkCollections = [];
        if (empty($collections)) {
            return '';
        }
        // get the collection's text we need to map for SciVideos
        $collectionsMapped = array_map(function($collection) {
            if ($collection !=  "Lectures" && $collection != "Lectures::Video Lectures") {
                $value = explode("::", $collection);
                return $value[array_key_last($value)] ?? '';
            }
            return '';
        }, $collections);

        // clear out those not mapped
        $notEmptyCollections = array_filter($collectionsMapped, function($collection) {
            return !empty($collection);
        });

        foreach ($notEmptyCollections as $coll) {
            $collection_entity = $this->getOrCreateCERNCollection($coll);
            $talkCollections[] = $collection_entity?->id() ?? '';
        }

        return implode(";", $talkCollections);
    }

    /**
     * Returns a Collection entity for CERN
     * a new Collection will be created if not existing
     *
     * @param string $title
     *   The title of the collection.
     *
     * @return \Drupal\Core\Entity\EntityInterface
     *   The entity found or created.
    */
    private function getOrCreateCERNCollection($title): EntityInterface {
        $properties = [
            "title" => $title,
            "type" => "collection",
            "uid" => 1,
            "field_collection_source_repo" => $this->feedGroupID,
        ];
      
        $storage = \Drupal::entityTypeManager()->getStorage('node');
        $collection = $storage->loadByProperties($properties) ?? [];
        if (!empty($collection)) {
            return reset($collection);
        }

        $newCollection = $storage->create($properties);
        $newCollection->save();
        return $newCollection;
    }


    /**
     * Returns string containing a valid datetime for a CERN Talk
     * most CERN date field values have no time, so add one when that's the case
     *
     * @param string $date_tring
     *   The array of contributors.
     *
     * @return string
     *   a string containing the list of speakers.
    */
    private function fixMissingTimeinDates($date_tring) {
        // Define expected formats
        $formatDateOnly = 'Y-m-d';
        $formatDateTime = 'Y-m-d\\TH:i:s';
        $defaultTime = '09:00:00'; //use 9am as default time
        
        // Attempt to create a DateTime object from the input string
        $date = date_create($date_tring);

        if ($date === false) {
            return "";
        }

        // Check if the original string contained time information.
        // If a string contains only a date, time elements are reset to 0.
        $dateCheck = \DateTime::createFromFormat($formatDateOnly, $date_tring);

        // If createFromFormat($formatDateOnly, ...) succeeds AND the original string 
        // exactly matches the date-only format, then time was not explicitly set.
        if ($dateCheck !== false && $dateCheck->format($formatDateOnly) === $date_tring) {
            // The original string was date-only. Set the time.
            list($h, $m, $s) = explode(':', $defaultTime);
            $date->setTime((int)$h, (int)$m, (int)$s);
        }
       
        return $date->format($formatDateTime);
    }


    // parse the Description field for links to the video snapshots, which have the format [hh:mm:ss] and replace them with links to the corresponding time in the CERN video.
    private function replaceSlideLinksInDescription($prefix_url, $description) {
        if (!empty($description)) {
            $regex = "/(\d{2}:\d{2}:\d{2})/ms";
            // return preg_replace_callback('/(\d{2}:\d{2}:\d{2})/ms', function($matches) use($prefix_url) {
            return preg_replace_callback($regex, function($matches) use($prefix_url) {
                $slide_link = $prefix_url . "?t=" . $this->timeToSeconds($matches[0]);
                $link = "<a href='{$slide_link}' target='_blank'>{$matches[0]}</a>";
                return $link;
            }, $description);
        }
        return $description;

    }

    // the duration field in CERN API is in the format "hh:mm:ss" or "mm:ss", we need to convert it to seconds for SciTalk feeds.
    private function timeToSeconds(string $time): int {
        $arr = explode(':', $time);
        if (count($arr) === 3) {
            return $arr[0] * 3600 + $arr[1] * 60 + $arr[2];
        }
        else if (count($arr) === 2) {
            return $arr[0] * 60 + $arr[1];
        }
        return (int)$arr[0];
    }

    // the subtitles files in CERN have a media_type "subtitle" and we want to grab only the english subtitles, which have a tag with key "language" and value "en". 
    // We will return the link to download the subtitle file, which is located in the "links" object with the key "deleteFile".
    private function parseSubtitles($files): string {
        $subtitles = '';
        foreach ($files as $file) {
            if ($file->media_type == 'subtitle') { //grab only english subtitles
                $subtitleLang = $file->tags?->language ?? '';
                if ($subtitleLang == 'en') {
                    return $file->links?->deleteFile ?? '';
                }
            }
        }
        return $subtitles;
    }

    // No subject data in CERN API, so we will assign the same subject to all talks, which is Physics, as all CERN talks are physics related.
    // the method will look for the term "Physics" in the "subjects" taxonomy vocabulary and return it, if not found it will return an empty string.
    private function getSubjectByName($name = 'Physics') {
        $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => $name, 'vid' => 'subjects']) ?? [];
        if ($term) {
            $term = reset($term);
            return $term->getName();
        }
        return '';
    }

}