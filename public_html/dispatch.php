<?php
chdir('..');
require_once('src/core.php');

$dir = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'Controllers']);
$classNames = ReflectionHelper::loadClasses($dir);
$classNames = array_filter($classNames, function($className) {
	return substr_compare($className, 'Controller', -10, 10) === 0;
});
$bypassCache = !empty($_GET['bypass-cache']);

$controllerContext = new ControllerContext();
$viewContext = new ViewContext();
try
{
	$url = $_SERVER['REQUEST_URI'];
	if (Cache::isFresh($url) and !$bypassCache)
	{
		Cache::load($url);
		exit(0);
	}
	foreach ($classNames as $className)
	{
		if ($className::parseRequest($url, $controllerContext))
		{
			Cache::beginSave($url);
			$className::work($controllerContext, $viewContext);
			View::render($viewContext);
			Cache::endSave();
			exit(0);
		}
	}
	$viewContext->viewName = 'error-404';
	View::render($viewContext);
}
catch (Exception $e)
{
	#log error information
	$viewContext->viewName = 'error';
	$viewContext->exception = $e;
	Logger::log(Config::$errorLogPath, $e);
	View::render($viewContext);
}
exit(1);
