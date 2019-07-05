<?php
/**
 * Copyright (c) Enalean SAS 2014 - 2016. All rights reserved
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  Data Access Object for Tracker_FormElement_Field
 */
class Tracker_FormElement_Field_BurndownDao extends Tracker_FormElement_SpecificPropertiesDao
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_field_burndown';
    }

    public function save($field_id, $row)
    {
        $field_id  = $this->da->escapeInt($field_id);
        $use_cache = (int) (isset($row['use_cache']) && $row['use_cache']);

        $sql = "REPLACE INTO tracker_field_burndown (field_id, use_cache)
                VALUES ($field_id, $use_cache)";

        return $this->update($sql);
    }

    /**
     * Duplicate specific properties of field
     *
     * @param int $from_field_id the field id source
     * @param int $to_field_id the field id target
     *
     * @return bool true if ok, false otherwise
     */
    public function duplicate($from_field_id, $to_field_id)
    {
        $from_field_id = $this->da->escapeInt($from_field_id);
        $to_field_id   = $this->da->escapeInt($to_field_id);

        $sql = "REPLACE INTO tracker_field_burndown (field_id, use_cache)
                SELECT $to_field_id, use_cache
                FROM $this->table_name
                WHERE field_id = $from_field_id";

        return $this->update($sql);
    }

    /**
     * SUM(): Magic trick
     * The request returns values for 2 fields, start_date and duration
     * SUM of null + value give us the value for field in one single line
     *
     * @return DataAccessResult|false
     */
    public function getArtifactsWithBurndown()
    {
        $sql = "SELECT
                  tracker_artifact.id,
                  SUM(tracker_changeset_value_date.value)      AS start_date,
                  SUM(tracker_changeset_value_int.value)       AS duration,
                  tracker_field_for_start_date.id              AS start_date_field_id,
                  tracker_field_for_duration.id                AS duration_field_id,
                  tracker_field_for_remaining_effort.id        AS remaining_effort_field_id,
                  DATE_ADD(
                    DATE_FORMAT(FROM_UNIXTIME(SUM(tracker_changeset_value_date.value)), '%Y-%m-%d 00:00:00'),
                    INTERVAL SUM(tracker_changeset_value_int.value) +1 DAY
                  ) AS end_date
            FROM tracker_field AS burndown_field
            INNER JOIN tracker
              ON tracker.id = burndown_field.tracker_id
            LEFT JOIN (
                SELECT timeframe.tracker_id,
                       start_date_field.name as start_date_field_name,
                       duration_field.name   as duration_field_name
                FROM tracker_semantic_timeframe AS timeframe
                     INNER JOIN tracker_field AS start_date_field
                        ON (
                                timeframe.tracker_id = start_date_field.tracker_id
                            AND timeframe.start_date_field_id = start_date_field.id
                        )
                     INNER JOIN tracker_field AS duration_field
                        ON (
                                timeframe.tracker_id = duration_field.tracker_id
                            AND timeframe.duration_field_id = duration_field.id
                        )
            ) AS tracker_with_timeframe_semantic
                ON (
                    tracker.id = tracker_with_timeframe_semantic.tracker_id
                )
            INNER JOIN tracker_field AS tracker_field_for_start_date
              ON tracker.id = tracker_field_for_start_date.tracker_id
              AND tracker_field_for_start_date.name = IFNULL(tracker_with_timeframe_semantic.start_date_field_name, 'start_date')
            INNER JOIN tracker_field AS tracker_field_for_duration
              ON tracker.id = tracker_field_for_duration.tracker_id
              AND tracker_field_for_duration.name = IFNULL(tracker_with_timeframe_semantic.duration_field_name, 'duration')
            INNER JOIN tracker_field AS tracker_field_for_remaining_effort
              ON tracker.id = tracker_field_for_remaining_effort.tracker_id
              AND tracker_field_for_remaining_effort.name = 'remaining_effort'
            INNER JOIN tracker_artifact
              ON tracker.id = tracker_artifact.tracker_id
            INNER JOIN tracker_changeset
              ON tracker_changeset.id = tracker_artifact.last_changeset_id
            INNER JOIN tracker_changeset_value
              ON tracker_changeset_value.changeset_id = tracker_changeset.id
            LEFT JOIN tracker_changeset_value_date
              ON tracker_changeset_value_date.changeset_value_id = tracker_changeset_value.id
              AND tracker_field_for_start_date.id = tracker_changeset_value.field_id
            LEFT JOIN tracker_changeset_value_int
              ON tracker_changeset_value_int.changeset_value_id = tracker_changeset_value.id
              AND tracker_field_for_duration.id = tracker_changeset_value.field_id
            WHERE
              burndown_field.formElement_type = 'burndown'
              AND burndown_field.use_it = 1
              GROUP BY tracker_artifact.id, burndown_field.id
              HAVING start_date IS NOT NULL
              AND duration IS NOT NULL
             ORDER BY tracker_artifact.id, start_date DESC";

        return $this->retrieve($sql);
    }

    /**
     * SUM(): Magic trick
     * The request returns values for 2 fields, start_date and duration
     * SUM of null + value give us the value for field in one single line
     *
     * @return array|false
     */
    public function getBurndownInformation($artifact_id, $start_date_field_name, $duration_field_name)
    {
        $artifact_id           = $this->da->escapeInt($artifact_id);
        $start_date_field_name = $this->da->quoteSmart($start_date_field_name);
        $duration_field_name   = $this->da->quoteSmart($duration_field_name);

        $sql = "SELECT
                  tracker_artifact.id,
                  SUM(tracker_changeset_value_date.value)      AS start_date,
                  SUM(tracker_changeset_value_int.value)       AS duration,
                  tracker_field_for_start_date.id              AS start_date_field_id,
                  tracker_field_for_duration.id                AS duration_field_id,
                  tracker_field_for_remaining_effort.id        AS remaining_effort_field_id,
                  DATE_ADD(
                    DATE_FORMAT(FROM_UNIXTIME(SUM(tracker_changeset_value_date.value)), '%Y-%m-%d 00:00:00'),
                    INTERVAL SUM(tracker_changeset_value_int.value) +1 DAY
                  ) AS end_date,
                 UNIX_TIMESTAMP(DATE_ADD(
                    (FROM_UNIXTIME(SUM(tracker_changeset_value_date.value))),
                    INTERVAL SUM(tracker_changeset_value_int.value) +1 DAY
                  )) AS timestamp_end_date
            FROM tracker_field AS burndown_field
            INNER JOIN tracker
              ON tracker.id = burndown_field.tracker_id
            INNER JOIN tracker_field AS tracker_field_for_start_date
              ON tracker.id = tracker_field_for_start_date.tracker_id
              AND tracker_field_for_start_date.name = $start_date_field_name
            INNER JOIN tracker_field AS tracker_field_for_duration
              ON tracker.id = tracker_field_for_duration.tracker_id
              AND tracker_field_for_duration.name = $duration_field_name
            INNER JOIN tracker_field AS tracker_field_for_remaining_effort
              ON tracker.id = tracker_field_for_remaining_effort.tracker_id
              AND tracker_field_for_remaining_effort.name = 'remaining_effort'
            INNER JOIN tracker_artifact
              ON tracker.id = tracker_artifact.tracker_id
            INNER JOIN tracker_changeset
              ON tracker_changeset.id = tracker_artifact.last_changeset_id
            INNER JOIN tracker_changeset_value
              ON tracker_changeset_value.changeset_id = tracker_changeset.id
            LEFT JOIN tracker_changeset_value_date
              ON tracker_changeset_value_date.changeset_value_id = tracker_changeset_value.id
              AND tracker_field_for_start_date.id = tracker_changeset_value.field_id
            LEFT JOIN tracker_changeset_value_int
              ON tracker_changeset_value_int.changeset_value_id = tracker_changeset_value.id
              AND tracker_field_for_duration.id = tracker_changeset_value.field_id
            WHERE
              burndown_field.formElement_type = 'burndown'
              AND tracker_artifact.id = $artifact_id
              AND burndown_field.use_it = 1
            GROUP BY tracker_artifact.id, burndown_field.id
            HAVING start_date IS NOT NULL
            AND duration IS NOT NULL";

        return $this->retrieveFirstRow($sql);
    }
}
