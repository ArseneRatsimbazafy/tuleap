CREATE TABLE IF NOT EXISTS plugin_trafficlights(
    project_id INT(11) UNSIGNED NOT NULL PRIMARY KEY,
    campaign_tracker_id INT(11) NOT NULL,
    test_definition_tracker_id INT(11) NOT NULL,
    test_execution_tracker_id INT(11) NOT NULL,
    issue_tracker_id INT(11) NOT NULL
);

-- Enable service for project 1 and 100
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES ( 100 , 'plugin_testmanagement:service_lbl_key' , 'plugin_testmanagement:service_desc_key' , 'plugin_testmanagement', '/plugins/trafficlights/?group_id=$group_id', 1 , 1 , 'system',  250 );
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES ( 1   , 'plugin_testmanagement:service_lbl_key' , 'plugin_testmanagement:service_desc_key' , 'plugin_testmanagement', '/plugins/trafficlights/?group_id=1', 1 , 1 , 'system',  250 );

-- Create service for all other projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
SELECT DISTINCT group_id , 'plugin_trafficlights:service_lbl_key' , 'plugin_trafficlights:service_desc_key' , 'plugin_testmanagement', CONCAT('/plugins/trafficlights/?group_id=', group_id), 1 , 0 , 'system',  250
FROM service
WHERE group_id NOT IN (SELECT group_id
    FROM service
    WHERE short_name
    LIKE 'plugin_testmanagement');
