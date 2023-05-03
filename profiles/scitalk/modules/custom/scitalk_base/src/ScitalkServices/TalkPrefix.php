<?php
namespace Drupal\scitalk_base\ScitalkServices;

use Drupal\Core\Entity\EntityInterface;

class TalkPrefix {
    /**
     * find a Talk's prefix. It may come from the value defined in the Group Repo or from the SciTalk base configuration
     */
    public function get(EntityInterface $entity) {
        $talk_prefix = '';
        //first check if there's an entry under the Source Repo group for the Talk number prefix and if so, use it;
        $repo_id = $entity->get('field_talk_source_repository')->target_id ?? 0;
        if (!empty($repo_id)) {
            $group_storage =  \Drupal::entityTypeManager()->getStorage('group')->load($repo_id);
            $talk_prefix = $group_storage->field_repo_talks_prefix->value ?? '';
        }

        //if no Talk number prefix in the repo source then check if there's one in the SciTalk base config to use
        if (empty($talk_prefix)) {
            $scitalk_base_config = \Drupal::config('scitalk_base.settings');
            $talk_prefix = $scitalk_base_config->get('datacite_talk_prefix') ?? '';
        }

        return $talk_prefix;
    }
}