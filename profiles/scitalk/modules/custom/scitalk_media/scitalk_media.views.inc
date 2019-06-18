<?php


/**
 * Implements hook_views_data_alter().
 */
function scitalk_media_views_data_alter(array &$data) {
  $data['node']['media_thumbnail'] = array(
    'title' => t('Media Thumbnail'),
    'group' => t('Content'),
    'field' => array(
      'title' => t('Media Thumbnail'),
      'help' => t('If you have a SciTalk Video field on your content type, this field will determine if there is a thumbnail present on the media or a thumbnail field to use.'),
      'id' => 'scitalk_media_thumbnail',
    ),
  );
  
}