<?php

namespace PhpCore;

/**
 * Class controller g�n�rique
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
	 * Param�tres � passer au controller
	 */
	public function setParams($params)
	{
		foreach ($params as $key => $param) {
			$this->$key = $param;
		}
	}

	/**
	 * Param�tres GET/POST � r�cup�rer
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
	 * Permet de d�finir les param�tres par d�faut � utiliser sur les param�tres n'existent pas
	 *
	 * @return array
	 */
	public function getDefaultParameters()
	{
		return array();
	}

	/**
	 * Permet de faire des corrections apr�s la r�cup�ration des param�tres
	 * Ex: changer des formats de date
	 */
	public function patchParameters()
	{
	}

	/**
	 * M�thode appel�e avant chaque action
	 */
	public function preAction()
	{
	}

	/**
	 * M�thode appel�e apr�s chaque action
	 */
	public function postAction()
	{
	}
}
