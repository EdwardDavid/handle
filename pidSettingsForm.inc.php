<?php

/**
 * @file pidSettingsForm.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class pidSettingsForm
 * @ingroup plugins_generic_pid
 *
 * @brief Form for journal managers to modify PID plugin settings
 */

// $Id: pidSettingsForm.inc.php,v 1.7 2008/07/01 01:16:13 asmecher Exp $


import('form.Form');

class pidSettingsForm extends Form {

	/** @var $journalId int */
	var $journalId;

	/** @var $plugin object */
	var $plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $journalId int
	 */
	function pidSettingsForm(&$plugin, $journalId) {
		$this->plugin = &$plugin;
		$this->journalId = $journalId;
		parent::Form($this->plugin->getTemplatePath() . 'settingsForm.tpl');

		$this->addCheck(new FormValidator($this, 'pidAssignorPath', 'required', 'plugins.generic.pid.manager.settings.pidAssignorPathRequired'));
		$this->addCheck(new FormValidator($this, 'pidResolverPath', 'required', 'plugins.generic.pid.manager.settings.pidResolverPathRequired'));
		$this->addCheck(new FormValidator($this, 'pidUsername', 'required', 'plugins.generic.pid.manager.settings.pidUsernameRequired'));
		$this->addCheck(new FormValidator($this, 'pidPassword', 'required', 'plugins.generic.pid.manager.settings.pidPasswordRequired'));
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$this->_data = array(
		'pidAssignorPath' => $this->plugin->getSetting($this->journalId, 'pidAssignorPath')
		,'pidResolverPath' => $this->plugin->getSetting($this->journalId, 'pidResolverPath')
		,'pidUsername' => $this->plugin->getSetting($this->journalId, 'pidUsername')
		,'pidPassword' => $this->plugin->getSetting($this->journalId, 'pidPassword')
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('pidAssignorPath', 'pidResolverPath', 'pidUsername', 'pidPassword'));
	}

	/**
	 * Save settings.
	 */
	function execute() {
		$this->plugin->updateSetting($this->journalId, 'pidAssignorPath', $this->getData('pidAssignorPath'), 'string');
		$this->plugin->updateSetting($this->journalId, 'pidResolverPath', $this->getData('pidResolverPath'), 'string');
		$this->plugin->updateSetting($this->journalId, 'pidUsername', $this->getData('pidUsername'), 'string');
		$this->plugin->updateSetting($this->journalId, 'pidPassword', $this->getData('pidPassword'), 'string');
	}
}
?>