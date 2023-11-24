<?php
require_once 'Autoloader.php';
Autoloader::register();
new Api();

class Api
{
	private static $db;

	public static function getDb()
	{
		return self::$db;
	}

	public function __construct()
	{
		self::$db = (new Database())->init();

		if($_SERVER['SERVER_NAME'] == '127.0.0.1')
		{
			//For checking site on localhost
			//vero is the name of project's folder on localhost
			$project_folder = 'vero/';
			$uri = strtolower(trim((string)$_SERVER['REQUEST_URI'], '/'));
			$uri = str_replace($project_folder,'',$uri);
		}
		else
		{
			$uri = strtolower(trim((string)$_SERVER['PATH_INFO'], '/'));
		}

		$httpVerb = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : 'cli';

		$wildcards = [
			':any' => '[^/]+',
			':num' => '[0-9]+',
		];

		//Available routes
		$routes = [
			'get constructionStages' => [
				'class' => 'ConstructionStages',
				'method' => 'getAll',
			],
			'get constructionStages/(:num)' => [
				'class' => 'ConstructionStages',
				'method' => 'getSingle',
			],
			'delete constructionStages/(:num)' => [
				'class' => 'ConstructionStages',
				'method' => 'delete',
			],
			'patch constructionStages/(:num)' => [
				'class' => 'ConstructionStages',
				'method' => 'patch',
				'bodyType' => 'ConstructionStagesUpdate'
			],
			'post constructionStages' => [
				'class' => 'ConstructionStages',
				'method' => 'post',
				'bodyType' => 'ConstructionStagesCreate'
			],
		];

		$response = [
			'error' => 'No such route',
		];

		if ($uri) {

			//find current route in route list
			foreach ($routes as $pattern => $target) 
			{
				$pattern = str_replace(array_keys($wildcards), array_values($wildcards), $pattern);
				
				//if find the route in the route list
				if (preg_match('#^'.$pattern.'$#i', "{$httpVerb} {$uri}", $matches))
				{
					$params = [];
					$is_valid = true;
					array_shift($matches);
					if (in_array($httpVerb,['post','patch']))
					{
						//get posted data
						$data = json_decode(file_get_contents('php://input'));
						
						//set object of data
						$params = [new $target['bodyType']($data)];

						//validate data
						$validatorObj = new ConstructionStagesValidator($params[0]);
						$is_valid = $validatorObj->isValid();
						if(!$is_valid) $response = ['error' => $validatorObj->errMsg];

					}

					if($is_valid)
					{
						//call final method for doing action
						$params = array_merge($params, $matches);
						$response = call_user_func_array([new $target['class'], $target['method']], $params);
					}
					
					break;
				}
			}

			//return result
			echo json_encode($response, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
		}
	}
}