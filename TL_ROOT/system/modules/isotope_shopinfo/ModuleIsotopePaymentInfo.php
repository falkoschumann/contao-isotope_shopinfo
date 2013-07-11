<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Isotope Shop Info Extension for Contao
 * Copyright (c) 2013, Falko Schumann <http://www.muspellheim.de>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *  - Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * PHP version 5
 * @copyright  2013, Falko Schumann <http://www.muspellheim.de>
 * @author     Falko Schumann <falko.schumann@muspellheim.de>
 * @package    IsotopeShopInfo
 * @license    BSD-2-Clause 
 * @filesource
 */


/**
 * Show information about selected payment methods.
 *
 * @copyright  2013, Falko Schumann <http://www.muspellheim.de>
 * @author     Falko Schumann <falko.schumann@muspellheim.de>
 * @package    Controller
 */
class ModuleIsotopePaymentInfo extends ModuleIsotope
{

	/**
	 * @var string
	 */
	protected $strTemplate = 'mod_iso_paymentinfo';


	/**
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			return $this->generateBackendWildcard();
		}
		return parent::generate();
	}

	
	/**
	 * @return string
	 */
	private function generateBackendWildcard()
	{
		$objTemplate = new BackendTemplate('be_wildcard');
		$objTemplate->wildcard = '### ISOTOPE PAYMENT INFO ###';
		$objTemplate->title = $this->headline;
		$objTemplate->id = $this->id;
		$objTemplate->link = $this->name;
		$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;
		return $objTemplate->parse();
	}
	

	protected function compile()
	{
		$arrModules = $this->getModules();
		if (empty($arrModules))
			$this->compileMessageNoModules();
		else
			$this->compileModules($arrModules);
	}


	/**
	 * Return an array of associative arrays with keys id, label, price and note.
	 * 
	 * @return array
	 */
	private function getModules()
	{
		$arrModules = array();
		$arrModuleIds = deserialize($this->iso_payment_modules);
		if (is_array($arrModuleIds) && !empty($arrModuleIds))
		{
			$arrModuleIds = array_map('intval', $arrModuleIds);
			$objModules = $this->Database->execute(
					'SELECT * ' .
					'FROM tl_iso_payment_modules ' .
					'WHERE id IN (' . implode(',', $arrModuleIds) . ')' . (BE_USER_LOGGED_IN === true ? ' ' : " AND enabled='1' ") .
					'ORDER BY ' . $this->Database->findInSet('id', $arrModuleIds));
			while ($objModules->next())
			{
				$objModule = $this->createModule($objModules);
				if (!$objModule)
					continue;

				$arrModules[] = array
				(
					'id'		=> $objModule->id,
					'label'		=> $objModule->label,
					'price'		=> $this->calculatePrice($objModule),
					'note'		=> $objModule->note,
				);
			}
		}
		return $arrModules;
	}
	
	/**
	 * @param Database_Result $result
	 * @return IsotopePayment|void
	 */
	private function createModule(&$result)
	{
		$strClass = $GLOBALS['ISO_PAY'][$result->type];
		if (strlen($strClass) && $this->classFileExists($strClass))
			return new $strClass($result->row());

		return;
	}

	/**
	 * @param IsotopePayment $objModule
	 * @return string
	 */
	private function calculatePrice(&$objModule)
	{
		$fltPrice = $objModule->price;
		$strSurcharge = $objModule->surcharge;
		if ($fltPrice != 0) 
			return (($strSurcharge == '' ? '' : ' ('.$strSurcharge.')') . ': '.$this->Isotope->formatPriceWithCurrency($fltPrice));

		return '';
	}


	private function compileMessageNoModules()
	{
		$this->Template = new FrontendTemplate('mod_message');
		$this->Template->class = 'payment_method';
		$this->Template->hl = 'h2';
		$this->Template->headline = $GLOBALS['TL_LANG']['ISO']['payment_method'];
		$this->Template->type = 'error';
		$this->Template->message = $GLOBALS['TL_LANG']['MSC']['noPaymentModules'];
	}


	/**
	 * @param array $arrModules
	 */
	private function compileModules(&$arrModules)
	{
		$this->Template->paymentMethods = $arrModules;
	}

}

?>