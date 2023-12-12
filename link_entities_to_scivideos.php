<?php
namespace Drupal\scitalk_base\SyncEntities;

require_once DRUPAL_ROOT . '/core/includes/bootstrap.inc';

/**
 * script to link talks from local site to SciVideos
 */
class LinkEntitiesToSciVideos {
    private $scivideos_service;

    private function __construct() {
        $this->scivideos_service = \Drupal::service('scitalk_base.scivideos_integrate');
    }

    public static function numberOfEntities($entity_type) {
        return (new self())->getNumberOfEntities($entity_type);
    }

    public static function link($entity_type, $run_live = FALSE, $how_many = 0) {
        (new self())->linkToSciVideos($entity_type, $run_live, $how_many);
    }

    private function isIntegrationOn() {
        $config = \Drupal::config('scitalk_base.settings');
        $integrate = $config->get('enable_scivideos_integrate');
        return $integrate;
    }

    private function getNumberOfEntities($entity_type) {
        $types = [$entity_type];
        $query_count = \Drupal::entityQuery('node')
            ->condition('type', $types, 'IN')
            ->accessCheck(TRUE)
            ->notExists('field_scivideos_uuid')
            ->condition('status', 1);

        return  $query_count->count()->execute() ?? 0;
    }

    private function linkToSciVideos($entity_type, $run_live = FALSE, $how_many = 0) {
        if (!$this->isIntegrationOn()) {
            echo "SciVideos Integration is off!";
            echo PHP_EOL;
            return;
        }

        //we only have 3 entities to link (Talk, Collection and Speaker Profile) and they are identified by these 3 fields:
        $field_identifier = $entity_type == 'talk' ?  'field_talk_number' : ($entity_type == 'collection' ? 'field_collection_number' : 'field_sp_external_id');

        $memory_cache = \Drupal::service('entity.memory_cache');
        $types = [$entity_type];
        $query = \Drupal::entityQuery('node')
              ->condition('type', $types, 'IN')
              ->condition('status', 1)
              ->accessCheck(TRUE)
              ->notExists('field_scivideos_uuid');

        if ($how_many > 0) {
            $query->range(0, $how_many);
        }
        $entity_ids = $query->execute();
    
        //split the entity ids into chunk so that we don't run out of memory when running the script on a large data set:
        $ctr = 0;
        $entity_ids_chunk = array_chunk($entity_ids, 1000);
        foreach ($entity_ids_chunk as $chunk) {
            $entities = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($chunk);
            foreach ($entities as $entity) {
                $etitle = $entity->title->value ?? '';
                $eidentifier = $entity->{$field_identifier}->value;
                $ctr++;
                echo " {$ctr} - ({$eidentifier}) '{$etitle}' -> ";
                if (empty($entity->field_scivideos_uuid->value)) {
                    $res = $this->scivideos_service->linkToSciVideos($entity, $field_identifier, $run_live);
                    $data = current($res->data) ?? [];
                    $uuid = $data->id ?? '';
                    if (!empty($uuid)) {
                        $res_title = $data->attributes->title ?? '';
                        $field = str_replace(['field_sp_', 'field_'], ['', ''], $field_identifier);
                        $res_id = $data->attributes->{$field} ?? '';
                        $txt = "linked with SciVideos to ({$res_id}) '{$res_title}'";
                        echo $run_live ? "...SUCCESSFULLY {$txt}" : "...will be {$txt}";
                    }
                    else {
                        echo "...NOT FOUND in SciVideos";
                    }
                }
                echo PHP_EOL;
            }

            $memory_cache->deleteAll();
        }
    
    }
}

/*
    entity types options for updating are: collection, talk, speaker_profile

    e.g. to update all talk records to SciVideos execute as:
        drush php:script link_entities_to_scivideos.php talk 0 live

    e.g. to update 5 collection records to SciVideo exectute as:
        drush php:script link_entities_to_scivideos.php collection 5 live

    to run a test w/o updating SciVideos execute as: (where number_of_records is number to test with, use 0 for all)
        drush php:script link_entities_to_scivideos.php <entity_type> <number_of_records> test
        or  (test is the default)
        drush php:script link_entities_to_scivideos.php <entity_type> <number_of_records>
*/


$entity_type = '';
$how_many = 0;
$run_live = FALSE;
if (!empty($extra)) {//$extra is an array of args passed from the drush script cmd line
    $entity_type = $extra[0];
    $how_many = $extra[1] ?? 0;
    $run_live = (!empty($extra[2]) && $extra[2] == 'live');
}

if (!in_array($entity_type, ['talk', 'collection', 'speaker_profile'])) {
    echo "Invalid entity type to update. Valid values are one of: talk, collection or speaker_profile";
    echo PHP_EOL;
    echo "Usage: drush php:script link_entities_to_scivideos.php <entity_type> <number_of_records> <run_as>";
    echo PHP_EOL;
    echo " where:";
    echo PHP_EOL;
    echo "    <entity_type> is one of talk, collection or speaker_profile";
    echo PHP_EOL;
    echo "    <number_of_records> represents how many records to update (0 for all records)";
    echo PHP_EOL;
    echo "    <run_as> is either test or live. 'test' will simulate what will be updated in SciVideos without actually updating. 'live' will perform the update. The default is 'test'";
    echo PHP_EOL;
    echo "example cmd: 'drush php:script link_entities_to_scivideos.php talk 0 live'";
    echo PHP_EOL;
    return;
}

$cnt = LinkEntitiesToSciVideos::numberOfEntities($entity_type);
echo 'Number of Entities not linked to SciVideos: '.$cnt;
echo PHP_EOL;

$running = $how_many ?: 'all';
echo "running {$running} {$entity_type} records as " . ($run_live ? 'live' : 'test');
echo PHP_EOL;

LinkEntitiesToSciVideos::link($entity_type, $run_live, $how_many);
echo PHP_EOL;
