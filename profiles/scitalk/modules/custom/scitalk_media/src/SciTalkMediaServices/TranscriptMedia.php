<?php
namespace Drupal\scitalk_media\SciTalkMediaServices;

use Drupal\Core\Entity\EntityInterface;
use Drupal\media\Entity\Media;

/** 
 * this class will create Transcript media for talks.
*/
class TranscriptMedia {
    /**
     * Create Transcript media if new vtt's have been attached to the entity
     *
     * @param \Drupal\Core\Entity\EntityInterface entity
     */
    public function createFromVTT(EntityInterface $entity) {
        $vtt_uploaded_before = $entity?->original?->get('field_subtitle_upload_file')->getValue() ?? [];
        $vtt_uploaded_after = $entity?->get('field_subtitle_upload_file')->getValue() ?? [];
        $vtt_url_before = $entity?->original?->get('field_subtitle_url')->getValue() ?? [];
        $vtt_url_after = $entity?->get('field_subtitle_url')->getValue() ?? [];

       \Drupal::logger('scitalk_media')->notice('in Trans Media createfrom BTT ',);
        // newly uploaded vtt
        if (!empty($vtt_uploaded_after)) {
            $before_vtts_ids = array_map(function($vtt) {return $vtt['target_id'];}, $vtt_uploaded_before);
            foreach ($vtt_uploaded_after as $vtt) {
                if (!in_array($vtt['target_id'], $before_vtts_ids)) {
                    $attached_vtt_media = \Drupal::entityTypeManager()->getStorage('media')->load($vtt['target_id']);
                    $this->attachTranscriptMedia($attached_vtt_media, $entity);
                }
            }
        }
        // URL to vtt file
        elseif (!empty($vtt_url_after)) {
            $before_vtts_ids = array_map(function($vtt) {return $vtt['target_id'];}, $vtt_url_before);
            foreach ($vtt_url_after as $vtt) {
                if (!in_array($vtt['target_id'], $before_vtts_ids)) {
                    $attached_vtt_media = \Drupal::entityTypeManager()->getStorage('media')->load($vtt['target_id']);
                    $this->attachTranscriptMedia($attached_vtt_media, $entity);
                }
            }
        }
    }

    
    /**
     * Attach Transcript media from a new vtt/subtitle media
     *
     * @param \Drupal\media\Entity\Media subtitles_media
     * @param \Drupal\Core\Entity\EntityInterface entity
     */
    private function attachTranscriptMedia(Media $subtitles_media, EntityInterface $talk) {
        $created_media = $this->create($subtitles_media, $talk);
        // attach the transcript media to the Talk
        if (!empty($created_media)) {
            $mid = $created_media->mid->value;
            $talk->field_talk_transcripts[] = ['target_id' => $mid];
        }
    }

    /**
     * Create Transcript media
     *
     * @param \Drupal\media\Entity\Media subtitles_media
     * @param \Drupal\Core\Entity\EntityInterface entity
     */
    public function create(Media $subtitles_media, EntityInterface $talk) {
        $bundle = $this->getSubtitlesBundle($subtitles_media, $talk);
        if ($bundle) {
            switch ($bundle) {
                case 'subtitles_uploaded_file':
                    return $this->createFromUploadedFile($subtitles_media);
                case 'subtitles_url':
                    return $this->createFromRemoteURL($subtitles_media);
                default:
                    return false;
            }
        }
        return false;
    }

    /**
     * Check if we can create the Transcript media and if so return the bundle
     *
     * @param \Drupal\media\Entity\Media;subtitles_media
     * @param \Drupal\Core\Entity\EntityInterface entity
     */
    private function getSubtitlesBundle(Media $subtitles_media, EntityInterface $talk) {
        //subtitles are either an uploaded file or url to a vtt file:
        $bundle = $subtitles_media->bundle->target_id ?? "";
        $vtt_bundles = ['subtitles_uploaded_file', 'subtitles_url'];
        if (!in_array($bundle, $vtt_bundles)) {
            return false;
        }
        
        //if there's already a Transcript media attached to the talk with the same lang code as the vtt, then do not create a new one
        $vtt_lang = $subtitles_media->field_subtitles_language->value ?? '';
        $attachments = !empty($talk) ? ($talk->get('field_talk_transcripts')->getValue() ?? []) : [];
        foreach($attachments as $atch) {
            $target_id = $atch['target_id'] ?? 0;
            $attached_media = \Drupal::entityTypeManager()->getStorage('media')->load($target_id);
            $attached_media_bundle = $attached_media->bundle->target_id ?? '';
            $attached_media_lang = $attached_media->field_subtitles_language->value ?? '';
            if ($attached_media_bundle == 'scitalk_transcription' && $attached_media_lang == $vtt_lang) {
                return false;
            }
        }
        return $bundle;
    }
  
    /**
     * Create Transcript media from uploaded vtt file
     *
     * @param \Drupal\Core\Entity\EntityInterface entity
     */
    private function createFromUploadedFile(EntityInterface $entity) {
        $source = $entity->field_media_file->target_id ?? '';
        \Drupal::logger('scitalk_media')->notice('Create transcript from file @vid', ['@vid'=> $source]);
        if (!empty($source)) {
            $lang = $entity->field_subtitles_language->value ?? '';
            $vtt = \Drupal::entityTypeManager()->getStorage('file')->load($source)->getFileUri();
            $url = \Drupal::service('file_url_generator')->generateAbsoluteString($vtt);
            $content = $this->getContent($url);
            return $this->createTranscriptMedia($content, $lang);
        }
    }

    /**
     * Create Transcript media from a remote vtt url
     *
     * @param \Drupal\Core\Entity\EntityInterface entity
     */
    private function createFromRemoteURL(EntityInterface $entity) {
        $source = $entity->field_media_scitalk_remote_file->value ?? "";
        if (!empty($source)) {
            $lang = $entity->field_subtitles_language->value ?? '';
            $content = $this->getContent($source);
            return $this->createTranscriptMedia($content, $lang);
        }
    }

    /**
     * Read vtt file
     *
     * @param string source
     */
    private function getContent($source) {
        $context = stream_context_create(array(
            'http' => array('timeout' =>10),
        ));
        $content = @file_get_contents($source, false, $context);
        return $content;
    }

    /**
     * Create Transcript media
     *
     * @param string content
     */
    private function createTranscriptMedia($content, $lang) {
        $media = [
            'bundle' => 'scitalk_transcription',
            'name' => "Transcript {$lang}",
            'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
            'status' => 1,
            'field_media_scitalk_transcript' => ['value' => $content],
            'field_searchable_text' => ['value' => $content],
            'field_subtitles_language' => $lang
        ];

        $new_media = \Drupal::entityTypeManager()->getStorage('media')->create($media);
        $new_media->save();
        return $new_media;
    }
}