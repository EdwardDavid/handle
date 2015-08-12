<?php
/**
 * @defgroup plugins_generic_pid
 */

/**
 * @file plugins/generic/pidPlugin/index.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_pid
 * @brief Wrapper for PID plugin.
 *
 */

// $Id: index.php,v 1.7 2008/07/01 01:16:13 asmecher Exp $

require_once('pidPlugin.inc.php');
return new pidPlugin();
?>