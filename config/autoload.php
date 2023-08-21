<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (C) 2005-2019 Leo Feyer
 *
 * @copyright	Tim Gatzky 2019
 * @author		Tim Gatzky <info@tim-gatzky.de>
 * @package		pct_autogrid
 * @link		http://contao.org
 */

/**
 * Register the namespaces
 */
\Contao\ClassLoader::addNamespaces(array
(
	'iMi',
));


/**
 * Register the classes
 */
\Contao\ClassLoader::addClasses(array
(
	'iMi\ContaoShibboleth\Shibboleth' => 'system/modules/contao-shibboleth/Shibboleth.php',
	'iMi\ContaoShibboleth\ModuleShibbolethLogin' => 'system/modules/contao-shibboleth/ModuleShibbolethLogin.php',
));

/**
 * Register the templates
 */
\Contao\TemplateLoader::addFiles(array
(
	'mod_shibbolethlogin' => 'system/modules/contao-shibboleth/templates',
));
