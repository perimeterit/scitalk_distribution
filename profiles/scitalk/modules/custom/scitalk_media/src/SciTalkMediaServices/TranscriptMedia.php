<?php
namespace Drupal\scitalk_media\SciTalkMediaServices;

use Drupal\Core\Entity\EntityInterface;

/** 
 * this class will create InterimHD media for talks.
*/
class TranscriptMedia {

    /**
     * Create Transcript media
     *
     * @param \Drupal\Core\Entity\EntityInterface entity
     */
    public function create(EntityInterface $entity) {
        $bundle = $entity->bundle->target_id ?? "";
        switch ($bundle) {
            case 'subtitles_uploaded_file':
                return $this->createFromUploadedFile($entity);
            case 'subtitles_url':
                return $this->createFromRemoteURL($entity);
            default:
                return false;
        }
    }
  
    /**
     * Create Transcript media from uploaded vtt file
     *
     * @param \Drupal\Core\Entity\EntityInterface entity
     */
    private function createFromUploadedFile(EntityInterface $entity) {
        $source = $entity->field_media_file->target_id ?? '';
        if (!empty($source)) {
            $vtt = \Drupal::entityTypeManager()->getStorage('file')->load($source)->getFileUri();
            $url = \Drupal::service('file_url_generator')->generateAbsoluteString($vtt);
            $content = $this->getContent($url);
            return $this->createTranscriptMedia($content);
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
            $content = $this->getContent($source);
            return $this->createTranscriptMedia($content);
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
    private function createTranscriptMedia($content) {
        $media = [
            'bundle' => 'scitalk_transcription',
            'name' => 'Transcript',
            'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
            'status' => 1,
            'field_media_scitalk_transcript' => ['value' => $content],
            'field_searchable_text' => ['value' => $content]
        ];

        $new_media = \Drupal::entityTypeManager()->getStorage('media')->create($media);
        $new_media->save();
        return $new_media;
    }
}