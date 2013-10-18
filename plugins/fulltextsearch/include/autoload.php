<?php
// @codingStandardsIgnoreFile
// @codeCoverageIgnoreStart
// this is an autogenerated file - do not edit
function autoloada7d9033d4b9a36f0b64dafd08f1c16ef($class) {
    static $classes = null;
    if ($classes === null) {
        $classes = array(
            'elasticsearch_clientfacade' => '/ElasticSearch/ClientFacade.class.php',
            'elasticsearch_clientfactory' => '/ElasticSearch/ClientFactory.class.php',
            'elasticsearch_clientnotfoundexception' => '/ElasticSearch/ClientNotFoundException.class.php',
            'elasticsearch_indexclientfacade' => '/ElasticSearch/IndexClientFacade.class.php',
            'elasticsearch_searchadminclientfacade' => '/ElasticSearch/SearchAdminClientFacade.class.php',
            'elasticsearch_searchclientfacade' => '/ElasticSearch/SearchClientFacade.class.php',
            'elasticsearch_searchresult' => '/ElasticSearch/SearchResult.class.php',
            'elasticsearch_searchresultcollection' => '/ElasticSearch/SearchResultCollection.class.php',
            'elasticsearch_searchresultdocman' => '/ElasticSearch/SearchResultDocman.class.php',
            'elasticsearch_searchresultprojectsfacet' => '/ElasticSearch/SearchResultProjectsFacet.class.php',
            'elasticsearch_searchresultprojectsfacetcollection' => '/ElasticSearch/SearchResultProjectsFacetCollection.class.php',
            'elasticsearch_searchresulttracker' => '/ElasticSearch/SearchResultTracker.class.php',
            'elasticsearch_transporthttpbasicauth' => '/ElasticSearch/TransportHTTPBasicAuth.class.php',
            'fulltextsearch_controller_admin' => '/FullTextSearch/Controller/Admin.class.php',
            'fulltextsearch_controller_search' => '/FullTextSearch/Controller/Search.class.php',
            'fulltextsearch_iindexdocuments' => '/FullTextSearch/IIndexDocuments.class.php',
            'fulltextsearch_isearchdocuments' => '/FullTextSearch/ISearchDocuments.class.php',
            'fulltextsearch_isearchdocumentsforadmin' => '/FullTextSearch/ISearchDocumentsForAdmin.class.php',
            'fulltextsearch_presenter_adminsearch' => '/FullTextSearch/Presenter/AdminSearch.class.php',
            'fulltextsearch_presenter_errornosearch' => '/FullTextSearch/Presenter/ErrorNoSearch.class.php',
            'fulltextsearch_presenter_index' => '/FullTextSearch/Presenter/Index.class.php',
            'fulltextsearch_presenter_search' => '/FullTextSearch/Presenter/Search.class.php',
            'fulltextsearch_presenter_searchonlyresults' => '/FullTextSearch/Presenter/SearchOnlyResults.class.php',
            'fulltextsearch_searchresultcollection' => '/FullTextSearch/SearchResultCollection.class.php',
            'fulltextsearchactions' => '/FullTextSearchActions.class.php',
            'fulltextsearchdocmanactions' => '/FullTextSearchDocmanActions.class.php',
            'fulltextsearchplugin' => '/fulltextsearchPlugin.class.php',
            'fulltextsearchplugindescriptor' => '/FulltextsearchPluginDescriptor.class.php',
            'fulltextsearchplugininfo' => '/FulltextsearchPluginInfo.class.php',
            'fulltextsearchtrackeractions' => '/FullTextSearchTrackerActions.class.php',
            'systemevent_fulltextsearch_docman' => '/SystemEvent_FULLTEXTSEARCH_DOCMAN.class.php',
            'systemevent_fulltextsearch_docman_delete' => '/SystemEvent_FULLTEXTSEARCH_DOCMAN_DELETE.class.php',
            'systemevent_fulltextsearch_docman_index' => '/SystemEvent_FULLTEXTSEARCH_DOCMAN_INDEX.class.php',
            'systemevent_fulltextsearch_docman_update' => '/SystemEvent_FULLTEXTSEARCH_DOCMAN_UPDATE.class.php',
            'systemevent_fulltextsearch_docman_update_metadata' => '/SystemEvent_FULLTEXTSEARCH_DOCMAN_UPDATE_METADATA.class.php',
            'systemevent_fulltextsearch_docman_update_permissions' => '/SystemEvent_FULLTEXTSEARCH_DOCMAN_UPDATE_PERMISSIONS.class.php',
            'systemevent_fulltextsearch_tracker_followup' => '/SystemEvent_FULLTEXTSEARCH_TRACKER_FOLLOWUP.class.php',
            'systemevent_fulltextsearch_tracker_followup_add' => '/SystemEvent_FULLTEXTSEARCH_TRACKER_FOLLOWUP_ADD.class.php',
            'systemevent_fulltextsearch_tracker_followup_update' => '/SystemEvent_FULLTEXTSEARCH_TRACKER_FOLLOWUP_UPDATE.class.php'
        );
    }
    $cn = strtolower($class);
    if (isset($classes[$cn])) {
        require dirname(__FILE__) . $classes[$cn];
    }
}
spl_autoload_register('autoloada7d9033d4b9a36f0b64dafd08f1c16ef');
// @codeCoverageIgnoreEnd