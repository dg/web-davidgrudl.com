<?php

use Nette\Application\Routers\Route;


// Load libraries
require __DIR__ . '/app/libs/nette.phar';
require __DIR__ . '/app/libs/feed.class.php';
require __DIR__ . '/app/libs/twitter.class.php';
require __DIR__ . '/app/libs/texy.phar';


$configurator = new Nette\Configurator;

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
$router = $container->getService('router');
$router[] = new Route('[<lang (?-i)cs|en>]', function ($presenter, $lang) use ($container) {
	if (!$lang) {
		$lang = $container->getService('httpRequest')->detectLanguage(array('en', 'cs')) ?: 'cs';
		return $presenter->redirectUrl($lang);
	}

	// create template
	$template = $presenter->createTemplate()
		->setFile(__DIR__ . '/app/' . $lang . '.latte');

	// register template helpers like {$foo|date}
	$template->addFilter('date', function ($date) use ($lang) {
		if ($lang === 'en') {
			return date('F j, Y', (int) $date);
		} else {
			static $months = array(1 => 'leden', 'únor', 'březen', 'duben', 'květen', 'červen', 'červenec', 'srpen', 'září', 'říjen', 'listopad', 'prosinec');
			$date = getdate((int) $date);
			return "$date[mday]. {$months[$date['mon']]} $date[year]";
		}
	});

	$template->addFilter('tweet', function ($s) {
		return Twitter::clickable($s);
	});

	$template->rss = ['Feed', 'loadRss'];

	$template->addFilter('texy', function (Latte\Runtime\FilterInfo $info, $s) {
		return (new Texy)->process($s);
	});
	return $template;
});


// http://davidgrudl.com/sources
$router[] = new Route('sources', function ($presenter) {

	$template = $presenter->createTemplate()
		->setFile(__DIR__ . '/app/sources.latte');

	$template->source = function ($file, $lang = NULL) {
		return preg_replace('#<br ?/?>#', '', highlight_file($file, TRUE));
	};
	return $template;
});


// Run the application!
$container->getService('application')->run();
