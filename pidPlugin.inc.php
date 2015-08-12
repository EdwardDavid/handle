<?php

/**
 * @file pidPlugin.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class pidPlugin
 * @ingroup plugins_generic_pid
 *
 * @brief enables PID functionality.
 */

import('classes.plugins.GenericPlugin');

//define('ASSOC_TYPE_PID_JOURNAL', 1); --- Crrently not supported.
define('ASSOC_TYPE_PID_ARTICLE', 2);

require_once('hsClientQueries.inc.php');

class pidPlugin extends GenericPlugin {

	var $journal;

	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True if plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
			
		$success = parent::register($category, $path);
		$this->addLocaleData();

		$this->import('pidResourceDAO');
		$this->import('pidHandler');

		$pidResourceDao = new pidResourceDao();
		DAORegistry::registerDAO('pidResourceDAO', $pidResourceDao);

		$this->journal =& Request::getJournal();
		$isEnabled = $this->getEnabled();

		if ($success){
			if($isEnabled === true) {

				HookRegistry::register('Template::Author::Submission::Status', array(&$this, 'submissionStatus'));
				HookRegistry::register('Template::sectionEditor::Submission::Status', array(&$this, 'submissionStatus'));
				HookRegistry::register('Template::Article::PID', array(&$this, 'articleTemplate'));

				HookRegistry::register('ArticleDAO::_updateArticle', array(&$this, 'publishedArticlePidHandler')); //Older OJS Versions
				HookRegistry::register('articledao::_updatearticle', array(&$this, 'publishedArticlePidHandler')); //Newer OJS Versions

			}
			HookRegistry::register('OAIDAOinc::_getRecord', array(&$this, 'OAIRecordsHandler'));
			HookRegistry::register('OAIDAOinc::_listRecords', array(&$this, 'OAIRecordsHandler'));
		}

		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'pidPlugin';
	}

	function getDisplayName() {
		$this->addLocaleData();
		return TemplateManager::smartyTranslate(array('key'=>'plugins.generic.pid'), $smarty);
	}

	function getDescription() {
		$this->addLocaleData();
		return TemplateManager::smartyTranslate(array('key'=>'plugins.generic.pid.description'), $smarty);
	}

	function publishedArticlePidHandler($hookName, $args){
		$articleDao = &DAORegistry::getDAO('ArticleDAO');

		preg_match_all('/(\w{1,})\s{0,}=\s{0,}\?/', $args[0],  $fields);
		$row = array_combine($fields[1],$args[1]);

		if (!empty($row['article_id']) && $row['status'] == STATUS_PUBLISHED){

			$article = $articleDao->getArticle($row['article_id']);
			$articleId = $article->getArticleId();

			$localPid = pidHandler::getResourcePid($articleId, ASSOC_TYPE_PID_ARTICLE);
			if(empty($localPid)){
				$pidAssignorPath = $this->getSetting($this->journal->getJournalId(), 'pidAssignorPath');
				$pidResolverPath = $this->getSetting($this->journal->getJournalId(), 'pidResolverPath');
				$pidUsername = $this->getSetting($this->journal->getJournalId(), 'pidUsername');
				$pidPassword = $this->getSetting($this->journal->getJournalId(), 'pidPassword');
				$this->articlePid = pidHandler::requestHsPid($pidAssignorPath, $pidResolverPath, $pidUsername, $pidPassword, $articleId);
			}
			else{
				$this->articlePid = $localPid;
			}
		}
		return false;
	}

	function OAIRecordsHandler($hookName, $args){

		$metadataPrefix = $args[1];

		//This array used for identifying supported formats prior entering the processing loop
		$supported_formats = array( 'marcxml' 	=> '',
									'nlm' 		=> '', 
									'oai_dc' 	=> '',
									'oai_marc' 	=> '', 
									'rfc1807'	=> ''
									);

									if(!isset($supported_formats[$metadataPrefix])){
										return;
									}

									$records = (strcmp($hookName, 'OAIDAOinc::_getRecord') == 0) ? array($args[2]) : $args[2];

									if(sizeof($records)){
										$response = &$args[0];
										foreach($records as $key => $record){
											//Using this rather than Article object to support previous versions
											$articleId = preg_replace('/.*?\/(\d{1,})$/','$1',$record->identifier);
											$pid = pidHandler::getResourcePid($articleId, ASSOC_TYPE_PID_ARTICLE);

											if($pid){
												$purl =  pidHandler::getResourcePurl($pid);
													
												switch($metadataPrefix){
													case 'nlm' 	  :  $response = preg_replace('/(<article-id pub-id-type="other">'.$articleId.'<\/article-id>)/',"$1\n\t\t\t<article-id pub-id-type=\"other\">hdl:{$pid}</article-id>", $response); //"hdl:" - Added by request to simlify filtering mechanism.
													$response = preg_replace('/(<self-uri xlink:href=".*\/'.$articleId.'"\s?\/>)/',"$1\n\t\t\t<self-uri xlink:href=\"{$purl}\" />", $response);
													break;
													case 'oai_dc'  : $response = preg_replace('/(<dc:identifier>.*\/'.$articleId.'<\/dc:identifier>)/',"$1\n\t<dc:identifier>{$purl}</dc:identifier>", $response);
													break;
													case 'rfc1807' : $response = preg_replace('/(<other_access>.*\/'.$articleId.'<\/other_access>)/',"$1\n\t<other_access>{$purl}</other_access>", $response);
													break;
													case 'marcxml' : $response = preg_replace('/(<datafield tag="856" ind1="4" ind2="0">\s{1,}<subfield code="u">.*\/'.$articleId.'<\/subfield>)/', "$1\n\t<subfield code=\"u\">{$purl}</subfield>\n\t</datafield>\n\t<datafield tag=\"024\" ind1=\"8\" ind2=\"#\">\n\t\t<subfield code=\"d\">{$pid}</subfield>", $response);
													break;
													case 'oai_marc': $response = preg_replace('/(<varfield id="856" i1="4" i2="0">\s{1,}<subfield label="u">.*\/'.$articleId.'<\/subfield>)/', "$1\n\t\t<subfield label=\"u\">{$purl}</subfield>\n\t</varfield>\n\t<varfield i1=\"024\" i2=\"8\" ind2=\"#\">\n\t\t<subfield code=\"d\">{$pid}</subfield>", $response);
													break;
												}
											}
										}
									}
									return;
	}

	function isSitePlugin() {
		return false;
	}

	function submissionStatus($hookname, $args){

		$args = Request::getRequestedArgs();

		if(isset($args[0])){
			$templateMgr = &TemplateManager::getManager();

			$articleId = $args[0];
			$articlePid = pidHandler::getResourcePid($articleId, ASSOC_TYPE_PID_ARTICLE);

			if($articlePid){
				$articlePurl = pidHandler::getResourcePurl($articlePid);

				$templateMgr->assign('articlePid', $articlePid);
				$templateMgr->assign('articlePurl', $articlePurl);
			}
			else{
				$templateMgr->assign('articlePid', '-');
				$templateMgr->assign('articlePurl', '-');
			}
			$templateMgr->display($this->getTemplatePath() . 'pidSubmissionStatus.tpl', 'text/html', '');
		}

		return false;
	}
	
	function articleTemplate($hookname, $args){

		$args = Request::getRequestedArgs();

		if(isset($args[0])){
			$templateMgr = &TemplateManager::getManager();

			$articleId = $args[0];
			$articlePid = pidHandler::getResourcePid($articleId, ASSOC_TYPE_PID_ARTICLE);

			if($articlePid){
				$articlePurl = pidHandler::getResourcePurl($articlePid);

				$templateMgr->assign('articlePurl', $articlePurl);
			}
			else{
				$templateMgr->assign('articlePurl', '-');
			}
			$templateMgr->display($this->getTemplatePath() . 'pidArticleTemplate.tpl', 'text/html', '');
		}

		return false;
	}

	function getManagementVerbs() {

		$this->addLocaleData();
		$isEnabled = $this->getSetting($this->journal->getJournalId(), 'enabled');

		$verbs[] = array(
		($isEnabled?'disable':'enable'),
		Locale::translate($isEnabled?'manager.plugins.disable':'manager.plugins.enable')
		);

		if ($isEnabled) $verbs[] = array(
		'settings',
		Locale::translate('plugins.generic.pid.settings')
		);

		return $verbs;
	}

	function manage($verb, $args) {

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));

		$journalId = $this->journal->getJournalId();
		$isEnabled = $this->getSetting($journalId, 'enabled');

		$this->addLocaleData();
		$returner = true;

		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticleIds = $publishedArticleDao->getPublishedArticleIdsByJournal($journalId);

		$articlesNoPid = pidHandler::countPublishedArticlesNoPid($journalId);

		$templateMgr->assign('articlesNoPid', $articlesNoPid);
		$templateMgr->assign('publishedArticles', count($publishedArticleIds));

		switch ($verb) {
			case 'enable':
				$this->updateSetting($journalId, 'enabled', true);
				$returner = false;
				break;
			case 'disable':
				$this->updateSetting($journalId, 'enabled', false);
				$returner = false;
				break;
			case 'settings':

				if ($isEnabled) {
					$this->import('pidSettingsForm');
					$form = new pidSettingsForm($this, $journalId);

					if (Request::getUserVar('save')) {
						$form->readInputData();
						if ($form->validate()) {
							$form->execute();
							Request::redirect(null, 'manager');
						}
					}
					if (Request::getUserVar('setPidsRetroactively')) {
						$form->readInputData();
						if ($form->validate()) {
							$pidAssignorPath = $this->getSetting($journalId, 'pidAssignorPath');
							$pidResolverPath = $this->getSetting($journalId, 'pidResolverPath');
							$pidUsername = $this->getSetting($journalId, 'pidUsername');
							$pidPassword = $this->getSetting($journalId, 'pidPassword');
							pidHandler::setPidsRetroactively($journalId, $pidAssignorPath, $pidResolverPath, $pidUsername, $pidPassword);
							Request::redirect(null, 'manager');
						}
					}
					$form->initData();
					$this->setBreadCrumbs(true);
					$form->display();

				} else {
					Request::redirect(null, 'manager');
				}
				break;
		}
		return $returner;
	}


	/**
	 * Determine whether or not this plugin is enabled.
	 */
	function getEnabled() {
		$journal = &Request::getJournal();
		if (!$journal) return false;
		return $this->getSetting($journal->getJournalId(), 'enabled');
	}

	/**
	 * Set the page's breadcrumbs, given the plugin's tree of items
	 * to append.
	 * @param $subclass boolean
	 */
	function setBreadcrumbs($isSubclass = false) {
		$templateMgr = &TemplateManager::getManager();
		$pageCrumbs = array(
		array(
		Request::url(null, 'user'),
		'navigation.user'
		),
		array(
		Request::url(null, 'manager'),
		'user.role.manager'
		)
		);
		if ($isSubclass) $pageCrumbs[] = array(
		Request::url(null, 'manager', 'plugins'),
		'manager.plugins'
		);

		$templateMgr->assign('pageHierarchy', $pageCrumbs);
	}
}
?>