<?php

use Nette\Application\Routers\Route;


// Load libraries
require __DIR__ . '/app/libs/nette.phar';
require __DIR__ . '/app/libs/Feed.php';
require __DIR__ . '/app/libs/OAuth.php';
require __DIR__ . '/app/libs/Twitter.php';
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
// https://davidgrudl.com/[cs|en]
$router = $container->getService('router');
$router[] = new Route('[<lang (?-i)cs|en>]', function ($presenter, $lang) use ($container) {
	if (!$lang) {
		$lang = $container->getService('httpRequest')->detectLanguage(['en', 'cs']) ?: 'cs';
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
			static $months = [1 => 'leden', 'únor', 'březen', 'duben', 'květen', 'červen', 'červenec', 'srpen', 'září', 'říjen', 'listopad', 'prosinec'];
			$date = getdate((int) $date);
			return "$date[mday]. {$months[$date['mon']]} $date[year]";
		}
	});

	$template->addFilter('tweet', function ($s) {
		return Twitter::clickable($s);
	});

	$template->twitter = function ($name) use ($container) {
		try {
			return $container->getService($name)->load(Twitter::ME);
		} catch (TwitterException $e) {
			return [];
		}
	};

	$template->rss = function ($path) {
		try {
			return Feed::loadRss($path)->item;
		} catch (FeedException $e) {
			return [];
		}
	};

	$template->addFilter('texy', function (Latte\Runtime\FilterInfo $info, $s) {
		return (new Texy)->process($s);
	});

	return $template;
});


// https://davidgrudl.com/sources
$router[] = new Route('sources', function ($presenter) use ($container) {

	$template = $presenter->createTemplate()
		->setFile(__DIR__ . '/app/sources.latte');

	$template->source = function ($file, $lang = null) {
		return new Latte\Runtime\Html(preg_replace('#<br ?/?>#', '', highlight_file($file, true)));
	};
	return $template;
});


// Run the application!
$container->getService('application')->run();
