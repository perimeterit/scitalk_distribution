<?php
namespace Drupal\scitalk_media\SciTalkMediaServices;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileExists;

use Exception;
use MrMySQL\YoutubeTranscript\TranscriptListFetcher;
use MrMySQL\YoutubeTranscript\Exception\NoTranscriptFoundException;
use MrMySQL\YoutubeTranscript\Exception\YouTubeRequestFailedException;
use MrMySQL\YoutubeTranscript\Exception\TooManyRequestsException;
use MrMySQL\YoutubeTranscript\Exception\TranscriptsDisabledException;
use MrMySQL\YoutubeTranscript\Exception\NoTranscriptAvailableException;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
// use GuzzleHttp\RequestOptions;

/** 
 * this class will download VTT from YouTube and create VTT's media for talks.
*/
class YouTubeVTTMedia {

    /**
     * Create Transcript media
     *
     * @param \Drupal\Core\Entity\EntityInterface entity
     * @param string video_id
     */
    public function create(EntityInterface $entity, $video_id) {
        if (empty($video_id)) {
            return false;
        }
        
        // $proxies = [
        //     'http' => '',
        //     'https' => '',
        // ];

        // $http_client = new Client([
        //     RequestOptions::PROXY => $proxies,
        //     RequestOptions::VERIFY => false, # disable SSL certificate validation
        //     RequestOptions::TIMEOUT => 30, # timeout of 30 seconds
        // ]);

        $http_client = new Client();
        $request_factory = new HttpFactory();
        $stream_factory = new HttpFactory(); // GuzzleHttp\Psr7\HttpFactory implements StreamFactoryInterface
        $fetcher = new TranscriptListFetcher($http_client, $request_factory, $stream_factory);
        $transcript_text = [];
        try {
            $transcript_list = $fetcher->fetch($video_id);            
            $langService = \Drupal::service('scitalk_media.subtitle_languages');
            $language_codes = $langService->getLanguageCodes();
            // $language_codes = ['en', 'fr-CA']; // Prioritized language codes
            foreach ($language_codes as $lang) {
                try {
                    $transcript = $transcript_list->findTranscript([$lang]);  // this only returns the first item in the language array, so need to loop for each wnat i need
                    $transcript_text = $transcript->fetch();
    
                    $sp = "\n";
                    $vtt = "WEBVTT{$sp}{$sp}";
                    $trans_length = count($transcript_text);
                    foreach ($transcript_text as $idx => $trans) {
                        $start = $this->formatVTTTime($trans['start']);
                        $end = 0;
    
                        if ($idx < $trans_length - 1) {
                            $end = $this->formatVTTTime( $transcript_text[$idx + 1]['start'] );
                        }
                        else {
                            $end = $this->formatVTTTime(($trans['start'] + $trans['duration']));
                        }
                        $vtt .= "$start --> {$end}{$sp}";
                        $vtt .= "{$trans['text']}{$sp}{$sp}";
                    }
    
                    $file = $this->writeVTTFile($vtt, $video_id, $lang);
                    if ($file) {
                        $vtt_media = $this->createVTTMedia($file, $lang);
                        $entity->field_subtitle_upload_file[] = ["target_id" => $vtt_media->id()] ;
                    }
                }
                catch (NoTranscriptFoundException $e) {
                    // no transcript for this lang, continue
                }
            }
        }
        catch (TooManyRequestsException | YouTubeRequestFailedException | TranscriptsDisabledException | NoTranscriptAvailableException $e) {
            throw $e;
        }
        catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function formatVTTTime($seconds) {
        $time = new \DateTime('@' . $seconds); // '@' specifies a Unix timestamp
        $time->setTimezone(new \DateTimeZone('UTC')); // Set timezone to UTC to avoid local timezone effects

        return $time->format('H:i:s.v'); // Output: 03:25:45
    }
    
    private function writeVTTFile($vtt, $video_id, $lang = 'en') {
         $file_path = 'public://vtt/utube-vtts';
         $filename = "{$video_id}_{$lang}_vtt.vtt";
         if (\Drupal::service('file_system')->prepareDirectory($file_path, FileSystemInterface::CREATE_DIRECTORY)) {
            $vtt_filename = $file_path . '/' . $filename;
            $file = \Drupal::service('file.repository')->writeData($vtt, $vtt_filename, FileExists::Replace);
            if ($file) {
                return $file;
            }
         }
         return false;
    }

    /**
     * Create VTT media
     *
     * @param string content
     */
    private function createVTTMedia($file, $transcript_language_code) {
        $media = [
            'bundle' => 'subtitles_uploaded_file',
            'name' => $file->getFilename(),
            'field_media_file' => [ 
                'target_id' => $file->id(),
                'alt' => $file->getFilename(),
                'title' => $file->getFilename(),
            ],
            'field_subtitles_language' => $transcript_language_code,
            'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
            'status' => 1,
        ];

        $new_media = \Drupal::entityTypeManager()->getStorage('media')->create($media);
        $new_media->save();
        return $new_media;
    }
}