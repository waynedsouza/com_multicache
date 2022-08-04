<?php
/**
 *
 * @version 1.0.1.5
 * @package com_multicache
 * @copyright Copyright (C) 2015. All rights reserved.
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 * @author Wayne <wayne.dsouza@onlinemarketingconsultants.in> - http://OnlineMarketingConsultants.in
 */
defined('_JEXEC') or die();

// Include dependancies
jimport('joomla.application.component.controller');

// Execute the task.
$controller = JControllerLegacy::getInstance('Multicache');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
