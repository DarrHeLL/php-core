<?php

namespace PhpCore;

/**
 * Class Router
 */
class Router
{
	private $url;
	private $tabUrl;
	private $action;
	private $rootPath;
	private $tabRoutes;
	private $tabParams;
	private $className;
	private $routeList;
	private $indexList;
	private $methodName;
	private $routeExist;
	private $controller;
	private $modelsList;
	private $controllersList;

	/**
	 * HouraRouter constructor.
	 * @param $url
	 */
	public function __construct($url)
	{
		// attribus d�finis par la m�thode checkRoute
		$this->controller = null;
		$this->action = null;
		$this->className = null;
		$this->methodName = null;
		$this->tabParams = [];

		// Attribut d�finis par la m�thode findControllers
		$this->controllersList = [];

		// Attribut d�finis par la m�thode findModels
		$this->modelsList = [];

		// Attribut d�finis par la m�thode getAllRoutes
		$this->routeList = [];
		$this->indexList = [];

		$this->url = $url;
		$this->tabUrl = $this->explodeUrl($url);
		$this->rootPath = getcwd();

		// On lance la fonction pour rechercher les controllers
		$this->findControllers($this->rootPath . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . '*');

		// on d�clare les routes à la construction du router
		$this->declareRoutes();

		$this->tabRoutes = $this->getAllRoutes();
		$this->routeExist = $this->checkRoute();
	}

	/**
	 * M�thod static permattant l'autoloading du contenu de src et de leurs enfants
	 * @param $namespace
	 */
	public static function autoload($namespace)
	{
		// Permet d'autoloader les classes de l'application au cas
		// o� on a besoin de charger une classe parente par exemple
		spl_autoload_register(function ($classname) use($namespace) {
			$rootPath = "./";
			$classpath = "src";
			// On v�rifis si le namespace correspond
			if (preg_match("#^".preg_quote($namespace)."#", $classname)) {
				$classname = str_replace($namespace, "", $classname);
				$filename = preg_replace("#\\\\#", "/", $classname).".php";
				$fullpath = $rootPath.$classpath."/$filename";
				if (file_exists($fullpath)) {
					include_once $fullpath;
				}
			}
		});
	}

	/**
	 * M�thode static permettant d'instancier et de lancer le routing depuis l'index de l'application
	 * @param array $params
	 */
	public static function start_router($params = array())
	{
		if (isset($_GET['url']) && $_GET['url'] != 'index.php') {
			$router = new Router($_GET['url']);

			// La suite consiste � regarder si le routeur retourne une route existante
			if ($router->getRouteExist()) {
				// On regarde si l'utilisateur est bien connect� (sauf si la methode ou la classe contient
				// Du coup on commence par instancier la class correspondant � la route
				$className = $router->getClassName();
				$method = $router->getMethodName();
				$tabParams = $router->getTabParams();

				// si la route n�cessite que l'utilisateur soit connect�
				if ($router->checkAuthAnnotations($className, $method) === true) {
					// R�cup�ration des variables globals
					Config::getGlobals();
					$Utilisateur = new \Utilisateur();

					// Si l'utilisateur n'est pas conn�ct� alors on lui demande de se connecter
					if ($Utilisateur->isAuthentifie() !== true) {
						$Utilisateur->demandeAuthentification();
					}
				}

				$class = new $className();
				$class->setParams($params);

				// On appel la m�thode pre action
				$class->preAction();
				// On appel la m�thode voulue
				$output = call_user_func_array([$class, $method], $tabParams);
				// On appel la m�thode post action
				$class->postAction();
				$result = $output;
				if (is_array($result)) {
					throw new Exception("La m�thode $className::$method a retourn� un array au lieu d'une cha�ne");
				} elseif (is_object($result)) {
					$obj_class = get_class($result);
					throw new Exception("La m�thode $className::$method a retourn� un objet $obj_class au lieu d'une cha�ne");
				}
				echo $result;
			} else {
				$twig = new Twig();
				echo $twig->sendError404();
			}
		} else {
			$router = new Router('');

			foreach ($router->getIndexList() as $route) {
				echo "<a href='" . $route . "'>" . $route . "</a> <br />";
			}
		}
	}

	/**
	 * Convertie l'url en tableau associatif
	 * @param $url
	 * @return array
	 */
	private function explodeUrl($url)
	{
		// On doit splitter par / et pas ?
		// afin de s�parer le controller/action?params=string
		// et avoir array( controller, action, params=string )
		$tabUrl = preg_split('#[/\?]#', $url);

		// Par s�curit� on supprime les entr�es vides
		$tabUrl = array_filter($tabUrl, function ($value) {
			return $value !== "";
		});

		// Si il n'y a qu'une seul entr�e dans le tableau de l'url on rajoute par defaut 'index' en deuxi�me pour diriger vers la m�thode index du controller
		if (!isset($tabUrl[1])) {
			$tabUrl[1] = 'index';
		}
		return $tabUrl;
	}

	/**
	 * Permet de de require tout les controller dans l'index afin de pouvoir lire les annotations
	 */
	private function declareRoutes()
	{
		foreach ($this->controllersList as $controller) {
			require_once($controller);
		}
	}

	/**
	 * Permet de rechercher et mettre en forme les annotations de routage
	 * @param $reflection (ReflectionClass or ReflectionMethod)
	 * @return string|null
	 */
	private function checkAnnotations($reflection, $annotationType)
	{
		$route = null;
		if (strpos($reflection->getDocComment(), '@' . $annotationType)) {
			preg_match('^@' . $annotationType . '\(".*"\)^', $reflection->getDocComment(), $result);
			// On remplace la partie de d�claration de la route pour ne r�cup�rer que le contenu
			$patterns = [
				0 => '^@' . $annotationType . '\("^',
				1 => '^"\)^',
				2 => '^/^',
			];
			$replacements = [
				0 => "",
				1 => "",
				2 => "",
			];

			// R�cup�ration de la route mis en forme
			$value = preg_replace($patterns, $replacements, $result[0]);
		}
		return $value;
	}

	private function checkRouteAnnotations($reflection)
	{
		return $this->checkAnnotations($reflection, 'Route');
	}

	public function checkAuthAnnotations($class, $method)
	{
		$reflection = new \ReflectionMethod($class, $method);

		$auth = true;
		if ($this->checkAnnotations($reflection, 'Auth') === "false") {
			$auth = false;
		}

		return $auth;
	}

	/**
	 * M�thode permettant de r�cup�rer les annotations et de retourner la valeur de la route par rapport au nom de la classe dans un tableau
	 * @return array
	 */
	private function getAllRoutes()
	{
		$tabClass = get_declared_classes();
		$tabRoutes = [];

		// On parcours la liste des classes d�clar�es
		foreach ($tabClass as $class) {
			// On commence par instancier la classe de reflexion avec la classe parcourue
			$reflection = new \ReflectionClass($class);

			// On r�cup�re les annotation de type route sur la d�finition de la classe
			$route = $this->checkRouteAnnotations($reflection);

			// On int�gre la route dans le tableau que si elle n'est pas nulle
			if ($route != null) {
				$tabRoutes[$route] = [
					'class' => $class,
				];
				$methods = $reflection->getMethods();
				foreach ($methods as $method) {
					$action = null;
					// Si il y a une m�thode index alors on cr�er la route par d�faut
					if ($method->getName() == 'index') {
						$action = 'index';
					} elseif ($this->checkRouteAnnotations($method)) {
						// Sinon si il y a une annotation route dans la d�finition de la m�thode alors on ajoute la route
						$action = $this->checkRouteAnnotations($method);
					}
					if ($action != null) {
						// On regarde le nombre de param�tres de la fonction et on les ajoutent au tableau
						$tabRoutes[$route][$action] = [
							'method' => $method->getName(),
							'nbParam' => $method->getNumberOfParameters(),
							'nbParamReq' => $method->getNumberOfRequiredParameters(),
						];
						// On ajoute la route � la liste des routes
						$this->routeList[] = '.' . DIRECTORY_SEPARATOR . $route . DIRECTORY_SEPARATOR . $action;
						// Si l'action == index on ajoute la route au tableau des index
						if ($action == "index") {
							$this->indexList[] = './' . $route . '/' . $action;
						}
					}
				}
			}
		}
		return $tabRoutes;
	}

	/**
	 * Permet de rechercher les fichiers suffix� de Controller.php dans toute l'arborescence du projet
	 * @param $path String      Chemin sur lequel on lance le scan
	 */
	public function findControllers($path)
	{
		foreach (glob($path) as $item) {
			if (is_dir($item)) {
				$this->findControllers($item . DIRECTORY_SEPARATOR . '*');
			} elseif (preg_match('^(.*)Controller.php^', $item)) {
				$this->controllersList[] = $item;
			}
		}
	}

	/**
	 * Permet de v�rifier si la route demand� existe
	 * @return bool
	 */
	private function checkRoute()
	{
		$tabRoutes = $this->tabRoutes;
		$tabUrl = $this->tabUrl;
		$exist = false;

		if (count($tabUrl) > 1) {
			$controller = $tabUrl[0];
			$action = $tabUrl[1];
			// On v�rifis si les deux parties de base de l'url sont d�finies
			if (isset($tabRoutes[$controller]['class']) && isset($tabRoutes[$controller][$action]['method'])) {
				// Si c'est le cas on modifis les attribu du routeur avec les bonnes data
				$this->controller = $controller;
				$this->action = $action;
				$this->className = $tabRoutes[$controller]['class'];
				$this->methodName = $tabRoutes[$controller][$action]['method'];

				// ***** Traittement des param�tres ***** //
				foreach ($tabUrl as $key => $param) {
					// On regarde la valeur de la clef. Il y a obligatoirement 2 �l�ments pour une route sans param�tres.
					// Donc on les �l�ments avec un clef > 1 son des param�tres
					if ($key > 1) {
						$this->tabParams[] = $param;
					}
				}
				$exist = true;
			}
		}
		return $exist;
	}

	/**
	 * Permet de savoir si la route avec laquelle l'objet a �t� instanci� existe
	 * @return bool
	 */
	public function getRouteExist()
	{
		return $this->routeExist;
	}

	/**
	 * Permet de r�cup�rer le nom de la classe correspondant � l'URI
	 * @return string
	 */
	public function getClassName()
	{
		return $this->className;
	}

	/**
	 * Permet de retourner le nom de la m�thode correspondant � l'action de l'URI
	 * @return string
	 */
	public function getMethodName()
	{
		return $this->methodName;
	}

	/**
	 * Retourne la liste des param�tre pass�s par l'URI
	 * @return array
	 */
	public function getTabParams()
	{
		return $this->tabParams;
	}

	/**
	 * Retourne la liste de toutes les routes d�clar�es
	 * @return array
	 */
	public function getRoutesList()
	{
		return $this->routeList;
	}

	/**
	 * Retourne la liste des routes avec un index
	 * @return array
	 */
	public function getIndexList()
	{
		return $this->indexList;
	}
}
