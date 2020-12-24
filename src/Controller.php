<?php

namespace PhpCore;

/**
 * Class controller générique
 */
class Controller
{
	public $DIR_TEMPLATES = 'Templates';

	public $charset = 'utf-8';

	public $parameters = array();

	/**
	 * @constructor
	 *
	 * @return le rendu du template
	 */
	public function __construct()
	{
		$appDir = getcwd();
		$this->twig = new Twig($appDir.'/'.$this->DIR_TEMPLATES, $this->charset);
		$this->initParameters();
	}
	public function render($file, $vars)
	{
		$vars['parameters'] = $this->parameters;
		return $this->twig->render($file, $vars);
	}

	/**
	 * Paramètres à passer au controller
	 */
	public function setParams($params)
	{
		foreach ($params as $key => $param) {
			$this->$key = $param;
		}
	}

	/**
	 * Paramètres GET/POST à récupérer
	 */
	public function initParameters()
	{
		$default = $this->getDefaultParameters();
		foreach ($default as $key => $defaultValue) {
			$this->parameters[$key] = isset($_REQUEST[$key]) ? $_REQUEST[$key] : $defaultValue;
		}
		$this->patchParameters();
	}

	/**
	 * Permet de définir les paramètres par défaut à utiliser sur les paramètres n'existent pas
	 *
	 * @return array
	 */
	public function getDefaultParameters()
	{
		return array();
	}

	/**
	 * Permet de faire des corrections après la récupération des paramètres
	 * Ex: changer des formats de date
	 */
	public function patchParameters()
	{
	}

	/**
	 * Méthode appelée avant chaque action
	 */
	public function preAction()
	{
	}

	/**
	 * Méthode appelée après chaque action
	 */
	public function postAction()
	{
	}
}
