<?php
/**
 * The main class for Charlotte, a web crawler that processes a website's links, scripts,
 * stylesheets, and more and stores them in a database. 
 * 
 * @category Randak
 * @package Charlotte
 * @author Kristian Randall <kristian.l.randall@gmail.com>
 * @copyright 2014
 * @license https://www.gnu.org/copyleft/gpl.html GPL 3.0
 * @version 1.0
 * @since 1.0
 */

namespace Charlotte;

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;
use Charlotte\Processor\Processor;

/**
 * Charlotte crawls the web so you don't have to.
 *
 * @category Randak
 * @package Charlotte
 * @author Kristian Randall <kristian.l.randall@gmail.com>
 * @copyright 2014
 * @license https://www.gnu.org/copyleft/gpl.html GPL 3.0
 * @version 1.0
 * @since 1.0
 */
class Charlotte
{
	private $start;
	private $links;
	private $traversed;
	private $processor;

	public function __construct($start = "", $processor = "")
	{
		if(!empty($start)) $this->setStart($start);
		if(!empty($processor)) $this->setProcessor($processor);

		$this->cli = false;
	}

	public function setStart($start)
	{
		$this->start = $start;
		$this->links = array($start);
		$this->traversed = array();
	}

	public function setProcessor(Processor $processor)
	{
		$this->processor = $processor;
	}

	public function traverse()
	{
		if(php_sapi_name() === "cli") {
			$start = microtime(true);
			while(!empty($this->links)) {
				$url = array_pop($this->links);
				if(!in_array($url, $this->traversed)) {
					array_push($this->traversed, $url);
					$this->processPage($url);
				}
			}
			$end = microtime(true);

			echo "\033[32mCompleted job in " . number_format(($end-$start), 2, ".", "") . " seconds \033[0m".PHP_EOL;
		} else {
			die("This script can only be run from the command line.");
		}
	}

	protected function traversable($url)
	{
		if(empty($url)) {
			return false;
		}

		$ignore = array(
				"/^javascript\:void\(0\)$/", 			//javascript void
				"/^#.*/", 								//hash link (same page)
				"/^\/$/", 								//link to home page
				"/\.(pdf|zip|zi|png|jpg|jpeg|doc|ppt)$/i" //bad file types to crawl
			);

		foreach($ignore as $pattern) {
			if(preg_match($pattern, $url)) return false;
		}

		return true;
	}

	protected function cleanURL($url)
	{
		$url = trim($url);

		if(substr($url, 0, 2) === "//") {
			$url = "http:".$url;
		} elseif(substr($url, 0, 1) === "/") {
			$url = $this->start.$url;
		}

		return preg_replace(array("/#.*$/", "/\/#.*$/", "/\/$/"), "", $url);
	}

	protected function isURLExternal($url)
	{
        $base = str_replace(array("http://", "https://"), "", $this->start);
        $base = str_replace("www.", "", $base);

        return !preg_match("/^http(s)?\:\/\/(\w+\.){0,1}".$base."/", $url);
	}

	protected function processPage($url)
	{
		$start = microtime(true);

		try {
			$client = new Client();
			$crawler = $client->request("GET", $url);

			$statusCode = $client->getResponse()->getStatus();

			if($statusCode == 200) { //VALID URL
				$page = array();

				$page["url"] = $url;
				$page["title"] = $this->getTitle($crawler, $url);
				$page["metas"] = $this->getMetaTags($crawler, $url);
				$page["h1s"] = $this->getH1s($crawler, $url);
				$page["scripts"] = $this->getScripts($crawler, $url);
				$page["stylesheets"] = $this->getStylesheets($crawler, $url);
				// $page["keywords"] = $this->getKeywords($crawler, $url);
				$page["links"] = $this->getLinks($crawler, $url);

				$this->processor->process($page);
			}
		} catch (\Guzzle\Http\Exception\CurlException $ex) {
		} catch (\Exception $ex) {
		}

		$end = microtime(true);

		echo "Page indexed at \033[35m".$url."\033[0m in \033[36m" . number_format(($end-$start), 2, ".", "") . " seconds \033[0m".PHP_EOL;
	}

	protected function getTitle($crawler, $url)
	{
		try {
			$title = trim($crawler->filterXPath("html/head/title")->text());
		} catch (\InvalidArgumentException $e) {
			return "";
		}

		return $title;
	}

	protected function getMetaTags($crawler, $url)
	{
		$metas = array();
		$crawler->filter("meta")->each(function(Crawler $node) use ($url, &$metas) {
			if($node->attr("name")) {
				$metas[trim($node->attr("name"))] = preg_replace("/[\r\n ]+/", " ", trim($node->attr("content")));
			}
		});
		return $metas;
	}

	protected function getH1s($crawler, $url)
	{
		$h1s = array();
		$crawler->filter("h1")->each(function(Crawler $node) use ($url, &$h1s) {
			array_push($h1s, preg_replace("/[\r\n ]+/", " ", trim($node->text())));
		});
		return $h1s;
	}

	protected function getScripts($crawler, $url)
	{
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

	protected function getStylesheets($crawler, $url)
	{
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

	protected function getKeywords($crawler, $url)
	{
		$text = "";
		$crawler->filter("div")->each(function(Crawler $node) use ($url, &$keywords) {
			$text .= $node->text(); //TODO figure out how to get text without repetition from all elements except scripts
		});

		// The 100 most common English words according to the Oxford English Dictionary
		$stopwords = array("the", "be", "to", "of", "and", "a", "in", "that", "have", "I", "it", "for", 
						"not", "on", "with", "he", "as", "you", "do", "at", "this", "but", "his", "by", 
						"from", "they", "we", "say", "her", "she", "or", "an", "will", "my", "one", "all", 
						"would", "there", "their", "what", "so", "up", "out", "if", "about", "who", "get", 
						"which", "go", "me", "when", "make", "can", "like", "time", "no", "just", "him", 
						"know", "take", "people", "into", "year", "your", "good", "some", "could", "them", 
						"see", "other", "than", "then", "now", "look", "only", "come", "its", "over", "think", 
						"also", "back", "after", "use", "two", "how", "our", "work", "first", "well", "way", 
						"even", "new", "want", "because", "any", "these", "give", "day", "most", "us");

		$words = str_word_count($text, 1); 
		$words = array_map('strtolower', $words);

		$diff = array_diff($words, $stopwords);

		$frequency = array_count_values($diff);

		return arsort($frequency);
	}

	protected function getLinks($crawler, $url)
	{
		$links = array();
		$crawler->filter("a")->each(function(Crawler $node) use ($url, &$links) {
			if($node->attr("href")) {
				$linkURL = $this->cleanURL($node->attr("href"));

				if($this->traversable($linkURL) && !$this->isURLExternal($linkURL)) {
					array_push($links, array(
							"text" => $node->text(),
							"href" => $linkURL
						));
					if(!in_array($linkURL, $this->traversed) && !in_array($linkURL, $this->links)) {
						array_push($this->links, $linkURL);
					}
				}
			}
		});
		return $links;
	}
}