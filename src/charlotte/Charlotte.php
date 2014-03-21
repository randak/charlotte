<?php

namespace Charlotte;

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

class Charlotte
{
	private $baseURL;
	private $links;
	private $traversed;

	public function __construct($baseURL)
	{
		$this->baseURL = $baseURL;
		$this->links = array($baseURL);
		$this->traversed = array();
	}

	public function crawlable($url) {
		if(empty($url)) {
			return false;
		}

		$ignore = array(
				"/^javascript\:void\(0\)$/",
				"/^#.*/",
				"/\//"
			);

		foreach($ignore as $pattern) {
			if(preg_match($pattern, $url)) return false;
		}

		return true;
	}

	public function cleanURL($url) {
		return preg_replace("/#.*$/", "", $url);
	}

	public function traverse() {
		while(!empty($this->links)) {
			$url = array_pop($this->links);
			if(!in_array($url, $this->traversed)) {
				array_push($this->traversed, $url);
				$this->processPage($url);
			}
		}
	}

	protected function processPage($url) {
		try {
			$client = new Client();
			$crawler = $client->request("GET", $url);

			$statusCode = $client->getResponse()->getStatus();

			if($statusCode == 200) { //VALID URL
				$title = $this->getTitle($crawler, $url);
				$metas = $this->getMetaTags($crawler, $url);
				$h1s = $this->getH1s($crawler, $url);
				$scripts = $this->getScripts($crawler, $url);
				$stylesheets = $this->getStylesheets($crawler, $url);
				$keywords = $this->getKeywords($crawler, $url);
			}
		} catch (Guzzle\Http\Exception\CurlException $ex) {

		} catch (Exception $ex) {

		}
	}

	protected function getTitle($crawler, $url) {
		return trim($crawler->filterXPath("html/head/title")->text());
	}

	protected function getMetaTags($crawler, $url) {
		$metas = array();
		$crawler->filter("meta")->each(function(Crawler $node) use ($url, &$metas) {
			if($node->attr("name")) {
				$metas[trim($node->attr("name"))] = preg_replace("/[\r\n ]+/", " ", trim($node->attr("content")));
			}
		});
		return $metas;
	}

	protected function getH1s($crawler, $url) {
		$h1s = array();
		$crawler->filter("h1")->each(function(Crawler $node) use ($url, &$h1s) {
			array_push($h1s, preg_replace("/[\r\n ]+/", " ", trim($node->text())));
		});
		return $h1s;
	}

	protected function getScripts($crawler, $url) {
		$scripts = array();
		$crawler->filter("script")->each(function(Crawler $node) use ($url, &$scripts) {
			$script = array(
						"src" => trim($node->attr("src")),
						"content" => $node->text()
					  );

			array_push($scripts, $script);
		});
		return $scripts;
	}

	protected function getStylesheets($crawler, $url) {
		$stylesheets = array();
		$crawler->filter("link")->each(function(Crawler $node) use ($url, &$stylesheets) {
			if($node->attr("rel") == "stylesheet") {
				$stylesheet = array(
								"href" => trim($node->attr("href")),
								"media" => trim($node->attr("media")),
								"type" => trim($node->attr("type"))
							  );

				array_push($stylesheets, $stylesheet);
			}
		});
		return $stylesheets;
	}

	// protected function getKeywords($crawler, $url) {
	// 	$text = "";
	// 	$crawler->filter("body > div")->each(function(Crawler $node) use($url, &$text) {
	// 		//$text .= $node->text() . " ";
	// 		print_r($node);
	// 	});
	// 	echo $text;

		// $text = "";

		// foreach($elements as $element) {
		// 	$text .= $element->plaintext . " ";
		// }

		// // The 100 most common English words according to the Oxford English Dictionary
		// $stopwords = array("the", "be", "to", "of", "and", "a", "in", "that", "have", "I", "it", "for", 
		// 				"not", "on", "with", "he", "as", "you", "do", "at", "this", "but", "his", "by", 
		// 				"from", "they", "we", "say", "her", "she", "or", "an", "will", "my", "one", "all", 
		// 				"would", "there", "their", "what", "so", "up", "out", "if", "about", "who", "get", 
		// 				"which", "go", "me", "when", "make", "can", "like", "time", "no", "just", "him", 
		// 				"know", "take", "people", "into", "year", "your", "good", "some", "could", "them", 
		// 				"see", "other", "than", "then", "now", "look", "only", "come", "its", "over", "think", 
		// 				"also", "back", "after", "use", "two", "how", "our", "work", "first", "well", "way", 
		// 				"even", "new", "want", "because", "any", "these", "give", "day", "most", "us");

		// $words = str_word_count($text, 1); 
		// $words = array_map('strtolower', $words);

		// $diff = array_diff($words, $stopwords);

		// $frequency = array_count_values($diff);

		// return arsort($frequency);
	// }

	// public function visited($url) {
	// 	return in_array($page, $this->visited)
	// }

	// public function visit($url) {
	// 	array_push($this->visited, $url);
	// 	$this->crawler = new Crawler($url);
		
	// 	$title = $this->getTitle();
	// }

	// public function getTitle()
	// {
	// 	$title = $this->crawler->filter('title')->;
	// 	return ($title) ? $title->plaintext : "";
	// }

	// public function crawl() 
	// {
	// 	while(count($pages)) {
	// 		$current = array_pop($this->pageQueue);
	// 		if(!visited($current)) {
	// 			$page = visit($current);
	// 		}
	// 	}

	// 	if(php_sapi_name() === "cli") {
	// 		$overallstart = microtime(true);

	// 		//while there are still pages to parse, parse pages
	// 		while(count($pages)) {
	// 			set_time_limit(30); //increase PHP time limit so the script can continue to run

	// 			$start = microtime(true);
	// 			if(!in_array($page, $this->visited)) {
	// 				array_push($pages_visited, $page);

	// 				// $page = new Page();

	// 				$this->crawler->load_file($page);

	// 				$page = new Page();
	// 				$page->setTitle($this->getTitle());
	// 				$page->setKeywords($this->getKeywords());
	// 				$page->setHeaders($this->getHeaders());

	// 				$this->parseScripts();
	// 				$this->parseStylesheets();
	// 				$this->parseLinks();
	// 			}
	// 			$end = microtime(true);

	// 			echo "Page indexed at \033[35m".$page."\033[0m in \033[36m" . number_format(($end-$start), 2, ".", "") . " seconds \033[0m".PHP_EOL;

	// 			// $crawler->clear();
	// 		}

	// 		$overallend = microtime(true);

	// 		echo "\033[32mCompleted job in " . number_format(($overallend-$overallstart), 2, ".", "") . " seconds \033[0m".PHP_EOL;
	// 	} else {
	// 		die("Please run this script from the command line.");
	// 	}
	// }

	// public function getKeywords()
	// {
	// 	$elements = $this->crawler->find("*");

	// 	$text = "";

	// 	foreach($elements as $element) {
	// 		$text .= $element->plaintext . " ";
	// 	}

	// 	// The 100 most common English words according to the Oxford English Dictionary
	// 	$stopwords = array("the", "be", "to", "of", "and", "a", "in", "that", "have", "I", "it", "for", 
	// 					"not", "on", "with", "he", "as", "you", "do", "at", "this", "but", "his", "by", 
	// 					"from", "they", "we", "say", "her", "she", "or", "an", "will", "my", "one", "all", 
	// 					"would", "there", "their", "what", "so", "up", "out", "if", "about", "who", "get", 
	// 					"which", "go", "me", "when", "make", "can", "like", "time", "no", "just", "him", 
	// 					"know", "take", "people", "into", "year", "your", "good", "some", "could", "them", 
	// 					"see", "other", "than", "then", "now", "look", "only", "come", "its", "over", "think", 
	// 					"also", "back", "after", "use", "two", "how", "our", "work", "first", "well", "way", 
	// 					"even", "new", "want", "because", "any", "these", "give", "day", "most", "us");

	// 	$words = str_word_count($text, 1); 
	// 	$words = array_map('strtolower', $words);

	// 	$diff = array_diff($words, $stopwords);

	// 	$frequency = array_count_values($diff);

	// 	return arsort($frequency);
	// }

	// public function getHeaders()
	// {
	// 	$h1s = array();
	// 	foreach($this->crawler->find('h1') as $h1) {
	// 		array_push($h1s, $h1->plaintext);
	// 	}
	// 	return $h1s;
	// }

	// public function getScripts()
	// {
	// 	$scripts = array();
	// 	foreach($this->crawler->find('script') as $script) {
	// 		if(isset($script->src)) array_push($scripts, $this->formURL($script->src))
	// 	}
	// 	return $scripts;
	// }

	// public function getStylesheets()
	// {
	// 	$stylesheets = array();
	// 	foreach($this->crawler->find('link') as $link) {
	// 		if($link->rel == "stylesheet") array_push($stylesheets, $this->formURL($link->href));
	// 	}
	// 	return $stylesheets;
	// }

	// public function getLinks()
	// {
	// 	foreach($this->crawler->find('a') as $link) {
	// 		$pattern = "/^\/.+$/";
	// 		preg_match($pattern, $link->href, $matches);

	// 		$excluded_files = array(".zip", ".pdf", ".png", ".jpg", ".gif", ".doc", ".ppt"); //files not to parse

	// 		if($matches && !in_array(strtolower(substr($matches[0], -4)), $excluded_files)) {
	// 			$match = $root.$matches[0];
				
	// 			//QUERY LINKS

	// 			//TODO fix this
	// 			if(!in_array($match, $pages) && !in_array($match, $pages_visited)) {
	// 				array_push($pages, $match);
	// 			}
	// 		}
	// 	}
	// }

	// private function formURL($url) {
	// 	$url = trim($url);

	// 	if(substr($url, 0, 2) === "//") {
	// 		$url = "http://www.".$this->root.$url;
	// 	} elseif(substr($url, 0, 1) === "/") {

	// 	} elseif(substr($url, 0, 3) === "http") {

	// 	} else {
	// 		$url = "http://www.".$this->page.$url;
	// 	}

	// 	//Absolute URL
	// 	if(substr($url, 0, 1) == "/" && substr($url, 0, 2) != "//") {
	// 		$url = $root.$url;
	// 	} elseif() {

	// 	}

	// 	return $url;
	// }
}