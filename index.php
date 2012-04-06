<?php

use Nette\Application\Routers\Route;


// Load libraries
require __DIR__ . '/app/libs/nette.min.php';
require __DIR__ . '/app/libs/feed.class.php';
require __DIR__ . '/app/libs/twitter.class.php';
require __DIR__ . '/app/libs/texy.min.php';


$configurator = new Nette\Config\Configurator;

// Enable Nette Debugger for error visualisation & logging
$configurator->enableDebugger(__DIR__ . '/app/log');

// Configure libraries
Twitter::$cacheDir = Feed::$cacheDir = __DIR__ . '/app/temp/cache';
$configurator->setTempDirectory(__DIR__ . '/app/temp');

// Create Dependency Injection container from config.neon file
$configurator->addConfig(__DIR__ . '/app/config.neon');
$container = $configurator->createContainer();


// Setup routes
// http://davidgrudl.com/[cs|en]
$container->router[] = new Route('[<lang (?-i)cs|en>]', function($presenter, $lang) use ($container) {
	if (!$lang) {
		$lang = $container->httpRequest->detectLanguage(array('en', 'cs')) ?: 'cs';
		return $presenter->redirectUrl($lang);
	}

	// create template
	$template = $presenter->createTemplate()
		->setFile(__DIR__ . '/app/' . $lang . '.latte');

	// register template helpers like {$foo|date}
	$template->registerHelper('date', function($date) use ($lang) {
		if ($lang === 'en') {
			return date('F j, Y', (int) $date);
		} else {
			static $months = array(1 => 'leden', 'únor', 'březen', 'duben', 'květen', 'červen', 'červenec', 'srpen', 'září', 'říjen', 'listopad', 'prosinec');
			$date = getdate((int) $date);
			return "$date[mday]. {$months[$date['mon']]} $date[year]";
		}
	});

	$template->registerHelper('tweet', function($s) {
		return Twitter::clickable($s);
	});

	$template->registerHelper('rss', function($path) {
		return Feed::loadRss($path);
	});

	$template->registerHelper('texy', array(new Texy, 'process'));
	return $template;
});


// http://davidgrudl.com/sources
$container->router[] = new Route('sources', function($presenter) {

	$template = $presenter->createTemplate()
		->setFile(__DIR__ . '/app/sources.latte');

	$template->registerHelper('source', function($file, $lang = NULL) {
		return preg_replace('#<br ?/?>#', '', highlight_file($file, TRUE));
	});
	return $template;
});


// Run the application!
$container->application->run();
