<?php

namespace PhpCore;

/**
 * Class Twig
 * Encapsule un Twig 2.0
 */
class Twig
{
	public $twig;

	public $filters = array(
		'print_r',
		'utf8_encode',
		'utf8_decode',
		'is_numeric',
	);

	public $defaultCharset = 'utf-8';

	public function __construct($dir = __DIR__, $charset = null)
	{
		$loader = new \Twig\Loader\FilesystemLoader(
			[
				'' => $dir,
				'DarrHeLL' => __DIR__.'/../../templates',
			]
		);
		$this->twig = new \Twig\Environment($loader, array(
			'charset' => $charset ?? $this->defaultCharset,
		));
		foreach ($this->filters as $filter) {
			$this->twig->addFilter(new \Twig\TwigFilter($filter, $filter));
		}
		$this->twig->addGlobal('HOURANET_JS_PATH', Config::HOURANET_JS());
		$this->twig->addGlobal('HOURANET_CSS_PATH', Config::HOURANET_CSS());
		$this->twig->addGlobal('HOURANET_IMAGES_PATH', Config::HOURANET_IMAGES());
		$this->twig->addGlobal('HOURANET_PATH', Config::HOURANET1_URL());
		$this->twig->addGlobal('HOURANET2_PATH', Config::HOURANET2_URL());
		$this->twig->addGlobal('HOURANET7_PATH', Config::HOURANET_URL());

		return $this->twig;
	}
	public function addFilter($filter)
	{
		return $this->twig->addFilter($filter);
	}
	public function render($tpl, $vars = array())
	{
		return $this->twig->render($tpl, $vars);
	}

	/**
	 * Envoi le template d'erreur 404
	 *
	 * @return string le contenu HTML d'erreur 404
	 */
	public function sendError404()
	{
		return $this->render('error404.html');
	}
}
