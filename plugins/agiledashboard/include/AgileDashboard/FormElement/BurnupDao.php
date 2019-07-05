<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
 *
 * This file is a part of Tuleap.
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

namespace Tuleap\AgileDashboard\FormElement;

use DataAccessObject;

class BurnupDao extends DataAccessObject
{
    public function searchArtifactsWithBurnup()
    {
        $type = $this->da->quoteSmart(Burnup::TYPE);

        $sql = "SELECT
                  tracker_artifact.id,
                  SUM(tracker_changeset_value_date.value) AS start_date,
                  SUM(tracker_changeset_value_int.value)  AS duration
            FROM tracker_field AS burnup_field
            INNER JOIN tracker
              ON tracker.id = burnup_field.tracker_id
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
              burnup_field.formElement_type = $type
              AND burnup_field.use_it = 1
              GROUP BY tracker_artifact.id, burnup_field.id
              HAVING start_date IS NOT NULL
              AND duration IS NOT NULL
             ORDER BY tracker_artifact.id, start_date DESC";

        return $this->retrieve($sql);
    }

    public function getBurnupInformation($artifact_id, $start_date_field_name, $duration_field_name)
    {
        $artifact_id           = $this->da->escapeInt($artifact_id);
        $type                  = $this->da->quoteSmart(Burnup::TYPE);
        $start_date_field_name = $this->da->quoteSmart($start_date_field_name);
        $duration_field_name   = $this->da->quoteSmart($duration_field_name);

        $sql = "SELECT
                  tracker_artifact.id,
                  SUM(tracker_changeset_value_date.value) AS start_date,
                  SUM(tracker_changeset_value_int.value)  AS duration
            FROM tracker_field AS burnup_field
            INNER JOIN tracker
              ON tracker.id = burnup_field.tracker_id
            INNER JOIN tracker_field AS tracker_field_for_start_date
              ON tracker.id = tracker_field_for_start_date.tracker_id
              AND tracker_field_for_start_date.name = $start_date_field_name
            INNER JOIN tracker_field AS tracker_field_for_duration
              ON tracker.id = tracker_field_for_duration.tracker_id
              AND tracker_field_for_duration.name = $duration_field_name
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
              burnup_field.formElement_type = $type
              AND burnup_field.use_it = 1
              AND tracker_artifact.id = $artifact_id
              GROUP BY tracker_artifact.id, burnup_field.id
              HAVING start_date IS NOT NULL
              AND duration IS NOT NULL";

        return $this->retrieveFirstRow($sql);
    }

    public function searchLinkedArtifactsAtGivenTimestamp($artifact_id, $timestamp)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $timestamp   = $this->da->escapeInt($timestamp);

        $sql = "SELECT linked_art.id AS id
                FROM tracker_artifact AS parent_art
                    INNER JOIN tracker ON parent_art.tracker_id = tracker.id
                    INNER JOIN tracker_changeset AS changeset1 ON changeset1.artifact_id = parent_art.id
                    LEFT JOIN  tracker_changeset AS changeset2
                        ON (
                            changeset2.artifact_id = parent_art.id
                            AND changeset1.id < changeset2.id
                            AND changeset2.submitted_on <= $timestamp
                        )
                    INNER JOIN tracker_field AS f
                        ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value AS cv
                        ON (cv.changeset_id = changeset1.id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink artlink
                        ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact AS linked_art
                        ON (linked_art.id = artlink.artifact_id)
                    INNER JOIN plugin_agiledashboard_planning
                        ON plugin_agiledashboard_planning.planning_tracker_id = parent_art.tracker_id
                    INNER JOIN plugin_agiledashboard_planning_backlog_tracker
                        ON plugin_agiledashboard_planning_backlog_tracker.planning_id = plugin_agiledashboard_planning.id
                        AND linked_art.tracker_id = plugin_agiledashboard_planning_backlog_tracker.tracker_id
                WHERE parent_art.id = $artifact_id
                    AND tracker.deletion_date IS NULL
                    AND changeset2.id IS NULL
                    AND changeset1.submitted_on <= $timestamp";

        return $this->retrieve($sql);
    }
}
