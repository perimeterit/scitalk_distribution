<?php
namespace Drupal\scitalk_base\ScitalkServices;

use Drupal\Core\Entity\EntityInterface;

class TalkAcademicYear {
    /**
     * Figure out which academic year a Talk belongs to and return its id if found
     */
    public function get(EntityInterface $entity) {
        $academic_year_tid = NULL;
        $talk_date = $entity->field_talk_date->value ?? '';
        if (!empty($talk_date)) {
            $talk_date = date('Y-m-d', strtotime($talk_date));
            $academic_year = \Drupal::entityQuery('taxonomy_term')
                ->condition('vid', 'academic_year')
                ->condition('field_academic_year_dates.value', $talk_date, '<=')
                ->condition('field_academic_year_dates.end_value', $talk_date, '>=')
                ->execute();  

            if (!empty($academic_year)) {
                $academic_year_tid = current($academic_year);
            }
        }

        return $academic_year_tid;
    }
}