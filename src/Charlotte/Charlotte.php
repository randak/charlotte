<?php
/**
 * The main class for Charlotte, a web crawler that processes a website's links,
 * scripts, stylesheets, and more and stores them in a database.
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
    protected $start;
    protected $links;
    protected $traversed;

    private $processor;
    private $config;

    public function __construct($config = "", $processor="")
    {
        $this->bases = array();
        if(!empty($config)) $this->setConfig($config);
        if(!empty($processor)) $this->setProcessor($processor);
    }

    public function setConfig($config)
    {
        $this->config = $config;
        $this->setStart($this->config["crawler"]["start"]);
    }

    public function setStart($start)
    {
        $this->start = (is_array($start)) ? $start : array($start);
        $this->links = $this->start;
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
                $this->processPage($url);
            }
            $end = microtime(true);

            echo "\033[32mCompleted job in " . number_format(($end-$start), 2, ".", "") . " seconds \033[0m".PHP_EOL;
        } else {
            die("This script can only be run from the command line.");
        }
    }

    protected function traversable($url)
    {
        if(empty($url)) return false;

        foreach($this->config["crawler"]["exclude"] as $pattern) {
            if(preg_match($pattern, $url)) return false;
        }

        return !$this->isURLExternal($url);
    }

    protected function processPage($url)
    {
        try {
            $start = microtime(true);

            $client = new Client();
            $crawler = $client->request("GET", $url);

            $url = $this->regulateURL($client->getRequest()->getUri());

            //don't crawl HTTPS version of HTTP page
            $alt = '';
            $prot = $this->getProtocol($url);
            if($prot === "https://") {
                $alt = preg_replace("/https:\/\//", "http://", $url);
            }

            if(!in_array($url, $this->traversed) && !in_array($alt, $this->traversed)) {
                array_push($this->traversed, $url);
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

                        $end = microtime(true);

                        echo "Page indexed at \033[33m".$url."\033[0m in \033[36m" . number_format(($end-$start), 2, ".", "") . " seconds\033[0m".
                             " with response code \033[32m200\033[0m".PHP_EOL;
                } else {
                    echo "Page not indexed at \033[31m".$url."\033[0m due to status \033[31m".$statusCode."\033[0m".PHP_EOL;
                }
            } else {
                // echo "Page ignored (already indexed) at \033[31m".$url."\033[0m".PHP_EOL;
            }
        } catch (\Guzzle\Http\Exception\CurlException $ex) {
        } catch (\Exception $ex) {
        }
    }

    protected function getTitle($crawler, $url)
    {
        try {
            return trim($crawler->filterXPath("html/head/title")->text());
        } catch (\InvalidArgumentException $e) {
            return "";
        }
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
                $href = $this->regulateURL($node->attr("href"), $url);

                if($this->traversable($href)) {
                    array_push($links, array(
                            "text" => $node->text(),
                            "href" => $href
                        ));
                    if(!in_array($href, $this->traversed) && !in_array($href, $this->links)) {
                        array_push($this->links, $href);
                    }
                }
            }
        });
        return $links;
    }

    protected function regulateURL($url, $current = "")
    {
        $url = preg_replace(array("/#.*$/", "/\/#.*$/", "/\/$/"), "", $url);

        if(!empty($current)) {
            $protocol = $this->getProtocol($current);
            $subdomain = $this->getSubdomain($current);
            $base = $this->getBase($current);

            if(preg_match("/^\/(?!\/)/", $url)) {
                return $protocol.$subdomain.$base.$url;
            } elseif(preg_match("/(?<=^\/\/).*/", $url, $matches)) {
                return $protocol.$matches[0];
            } //fall through
        }

        return $url;
    }

    protected function getProtocol($url) {
        preg_match("/^((http(s)?\:)?\/\/)/", $url, $matches);
        return (count($matches)) ? $matches[0] : "";
    }

    protected function getSubdomain($url) {
        $url = preg_replace("/(http(s)?)\:?\/\//", "", $url);
        foreach($this->start as $start) {
            $esc = preg_quote($this->getBase($start));
            $pattern = "/^(((\w|\d|-)*)\.)*(?=".$esc.")/";
            preg_match($pattern, $url, $matches);
            if(count($matches)) return $matches[0];
        }
        return "";
    }

    protected function getBase($url)
    {
        $base = preg_replace("/^http(s)?:\/\/\w*\./", "", $url);
        return preg_replace("/\/.*$/", "", $base);
    }

    protected function isURLExternal($url)
    {
        foreach($this->start as $start) {
            if($this->getBase($url) == $this->getBase($start)) {
                return false;
            }
        }
        return true;
    }
}
