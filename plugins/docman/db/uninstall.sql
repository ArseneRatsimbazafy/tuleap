DROP TABLE IF EXISTS plugin_docman_item;
DROP TABLE IF EXISTS plugin_docman_version;
DROP TABLE IF EXISTS plugin_docman_link_version;
DROP TABLE IF EXISTS plugin_docman_log;
DROP TABLE IF EXISTS plugin_docman_project_settings;
DROP TABLE IF EXISTS plugin_docman_metadata;
DROP TABLE IF EXISTS plugin_docman_metadata_value;
DROP TABLE IF EXISTS plugin_docman_metadata_love;
DROP TABLE IF EXISTS plugin_docman_metadata_love_md;
DROP TABLE IF EXISTS plugin_docman_report;
DROP TABLE IF EXISTS plugin_docman_report_filter;
DROP TABLE IF EXISTS plugin_docman_item_lock;
DROP TABLE IF EXISTS plugin_docman_notifications;
DROP TABLE IF EXISTS plugin_docman_notification_ugroups;
DROP TABLE IF EXISTS plugin_docman_version_deleted;
DROP TABLE IF EXISTS plugin_docman_new_document_upload;
DROP TABLE IF EXISTS plugin_docman_item_id;
DROP TABLE IF EXISTS plugin_docman_new_version_upload;

DELETE FROM service WHERE short_name='docman';

DELETE FROM permissions_values WHERE permission_type LIKE 'PLUGIN_DOCMAN_%';

DELETE FROM permissions WHERE permission_type LIKE 'PLUGIN_DOCMAN_%';

DELETE FROM user_preferences WHERE preference_name LIKE 'plugin_docman%';

DROP TABLE IF EXISTS plugin_docman_widget_embedded;
