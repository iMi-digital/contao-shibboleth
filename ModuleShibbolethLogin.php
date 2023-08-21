<?php

namespace iMi\ContaoShibboleth;
?><?php use Contao\BackendTemplate;

if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Andreas Schempp 2011-2012
 * @author     Andreas Schempp <andreas@schempp.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */


class ModuleShibbolethLogin extends \Module
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_shibbolethlogin';

	/**
	 * Shibboleth object
     * // FIXME: typehint property
	 */

	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### SHIBBOLETH LOGIN ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = $this->Environment->script.'?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}


		// Login
		if ($this->Input->post('FORM_SUBMIT') == 'tl_shibbolethlogin' || $this->autologin || $this->Input->get('shibauth'))
		{
			$this->import('iMi\ContaoShibboleth\Shibboleth', 'Shibboleth');

			if (($objUser = $this->Shibboleth->authenticateFrontend(($this->Input->post('FORM_SUBMIT') == 'tl_shibbolethlogin'))) !== false)
			{
				// HOOK: post login callback
				if (isset($GLOBALS['TL_HOOKS']['postLogin']) && is_array($GLOBALS['TL_HOOKS']['postLogin']))
				{
					foreach ($GLOBALS['TL_HOOKS']['postLogin'] as $callback)
					{
						$this->import($callback[0]);
						$this->$callback[0]->$callback[1]($objUser);
					}
				}

				$this->redirect($this->findRedirectUrl());
			}
		}

		// Logout and redirect to the website root if the current page is protected
		elseif ($this->Input->post('FORM_SUBMIT') == 'tl_logout')
		{
			global $objPage;

			$this->import('FrontendUser', 'User');
			$strRedirect = $this->Environment->request;

			// Redirect to last page visited
			if ($this->redirectBack && strlen($_SESSION['LAST_PAGE_VISITED']))
			{
				$strRedirect = $_SESSION['LAST_PAGE_VISITED'];
			}

			// Redirect home if the page is protected
			elseif ($objPage->protected)
			{
				$strRedirect = $this->Environment->base;
			}

			// Logout and redirect
			if ($this->User->logout())
			{
				// HOOK: post logout callback
				if (isset($GLOBALS['TL_HOOKS']['postLogout']) && is_array($GLOBALS['TL_HOOKS']['postLogout']))
				{
					foreach ($GLOBALS['TL_HOOKS']['postLogout'] as $callback)
					{
						$this->import($callback[0]);
						$this->$callback[0]->$callback[1]($this->User);
					}
				}

				$this->redirect($strRedirect);
			}

			$this->reload();
		}

		return parent::generate();
	}


	protected function compile()
	{
		// Show logout form
		if (FE_USER_LOGGED_IN)
		{
			$this->import('FrontendUser', 'User');
			$this->strTemplate = ($this->cols > 1) ? 'mod_logout_2cl' : 'mod_logout_1cl';

			$this->Template = new FrontendTemplate($this->strTemplate);
			$this->Template->setData($this->arrData);

			$this->Template->slabel = specialchars($GLOBALS['TL_LANG']['MSC']['logout']);
			$this->Template->loggedInAs = sprintf($GLOBALS['TL_LANG']['MSC']['loggedInAs'], $this->User->username);
			$this->Template->action = $this->getIndexFreeRequest();

			if ($this->User->lastLogin > 0)
			{
				$this->Template->lastLogin = sprintf($GLOBALS['TL_LANG']['MSC']['lastLogin'][1], $this->parseDate($GLOBALS['TL_CONFIG']['datimFormat'], $this->User->lastLogin));
			}

			return;
		}

		$blnHasError = false;

		if (count($_SESSION['TL_ERROR'] ?? []))
		{
			$blnHasError = true;
			$_SESSION['LOGIN_ERROR'] = $_SESSION['TL_ERROR'][0];
			$_SESSION['TL_ERROR'] = array();
		}

		if (strlen($_SESSION['LOGIN_ERROR'] ?? ''))
		{
			$blnHasError = true;
			$this->Template->message = $_SESSION['LOGIN_ERROR'];
			$_SESSION['LOGIN_ERROR'] = '';
		}

		$this->Template->hasError = $blnHasError;
		$this->Template->action = ampersand($this->Environment->request);
		$this->Template->formSubmit = 'tl_shibbolethlogin';
		$this->Template->slabel = $GLOBALS['TL_LANG']['MSC']['shibLogin'];
	}


	protected function findRedirectUrl($objUser)
	{
		$strRedirect = $this->Environment->request;

		// Redirect to last page visited
		if ($this->redirectBack && strlen($_SESSION['LAST_PAGE_VISITED']))
		{
			$strRedirect = $_SESSION['LAST_PAGE_VISITED'];
		}

		else
		{
			// Redirect to jumpTo page
			if (strlen($this->jumpTo))
			{
				$objNextPage = $this->Database->prepare("SELECT id, alias FROM tl_page WHERE id=?")
											  ->limit(1)
											  ->execute($this->jumpTo);

				if ($objNextPage->numRows)
				{
					$strRedirect = $this->generateFrontendUrl($objNextPage->fetchAssoc());
				}
			}

			$arrGroups = deserialize($objUser->groups);

			if (is_array($arrGroups) && count($arrGroups) > 0)
			{
				$time = time();

				// Get jumpTo page IDs
				$arrGroupPage = array();
				$objGroupPage = $this->Database->execute("SELECT id, jumpTo FROM tl_member_group WHERE id IN(" . implode(',', array_map('intval', $arrGroups)) . ") AND redirect=1 AND disable!=1 AND (start='' OR start<$time) AND (stop='' OR stop>$time)");

				// Simulate FIND_IN_SET()
				while ($objGroupPage->next())
				{
					$arrGroupPage[$objGroupPage->id] = $objGroupPage->jumpTo;
				}

				foreach ($arrGroups as $gid)
				{
					if (isset($arrGroupPage[$gid]))
					{
						// Get jumpTo page
						$objNextPage = $this->Database->prepare("SELECT id, alias FROM tl_page WHERE id=?")
													  ->limit(1)
													  ->execute($arrGroupPage[$gid]);

						if ($objNextPage->numRows)
						{
							$strRedirect = $this->generateFrontendUrl($objNextPage->fetchAssoc());
						}

						break;
					}
				}
			}
		}

		return $strRedirect;
	}
}


