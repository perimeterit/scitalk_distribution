<?php
namespace Drupal\scitalk_media\SciTalkMediaServices;

use Drupal\media\Entity\Media;
use Drupal\field\Entity\FieldConfig;

class SubtitlesLanguages {
    /**
     * Get list of allowed subtitles languages for a VTT Subtitle Media
     *
     */
    public function getAllowedKeyValues() {
        $entity_type_id = 'media';
        $bundle = 'subtitles_uploaded_file';
        $field_name = 'field_subtitles_language';
        $field_config = FieldConfig::load("$entity_type_id.$bundle.$field_name");
        if ($field_config) {
            return $field_config->getSetting('allowed_values');
        }
        return [];
    }

    /**
     * Get a subtitle language label from its language code
     *
     * @param string $lang_code
     */
    public function getLanguageLabel($lang_code) {
        $allowed_values = $this->getAllowedKeyValues();
        return array_key_exists($lang_code, $allowed_values) ? $allowed_values[$lang_code] : '';
    }
    
    /**
     * Get all subtitle language keys (language codes)
     *
     */
    public function getLanguageCodes() {
        $allowed_values = $this->getAllowedKeyValues();
        if ($allowed_values) {
            return array_keys($allowed_values);
        }
        return [];
    }

    // /**
    //  * Set an allowed subtitles language value
    //  *
    //  * @param string $language
    //  */
    // public function setValue($media_target_id, $lang_code) {
    //     $media = Media::load($media_target_id);

    //     $allowed_values = $this->getAllowedValues($media);
    //     if (array_key_exists($lang_code, $allowed_values)) {
    //         $media->set($this->field_name, $lang_code);
    //     }
    // }

}