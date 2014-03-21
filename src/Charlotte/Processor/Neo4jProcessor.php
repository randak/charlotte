<?php

namespace Charlotte\Processor;

use Everyman\Neo4j\Cypher\Query;

class Neo4jProcessor extends Processor
{
	protected function query($str, $vars = array())
	{
		if(!isset($this->connector)) {
			die("No database is connected. Aborting query.");
		}

		$query = new Query($this->connector, $str, $vars);
		$query->getResultSet();
	}

	protected function urlQuery($url, $title)
	{
		$str = "MERGE (n:Page {url: {url} }) 
				SET n.title = {title}";
				// ON CREATE SET n.updateTime=timestamp(), n.createTime=timestamp(), n.title={title}
				// ON MATCH SET n.title={title}';

		$vars = array(
					'url'  => $url,
					'title' => $title
				);

		$this->query($str, $vars);
	}

	protected function metaQuery($url, $metas)
	{
		$description = isset($metas["description"]) ? $metas["description"] : "";
		$keywords = isset($metas["keywords"]) ? $metas["keywords"] : "";

		$str = "MATCH (n:Page {url: {url} })
				SET n.description={description}, n.keywords={keywords}";

		$vars = array(
					"url" => $url,
					"description" => $description,
					"keywords" => $keywords
				);

		$this->query($str, $vars);
	}

	protected function keywordQuery($url, $keywords)
	{
		foreach($keywords as $key => $value) {
			$str = "MERGE (n:Page {url: {url} })
					MERGE (m:Keyword {word: {word} })
					MERGE (n)-[:CONTAINS_KEYWORD {frequency: {freq}}]->(m)";

			$vars = array(
						"url" => $url,
						"word" => $key,
						"freq" => $value
					);

			$this->query($str, $vars);
		}
	}

	protected function h1Query($url, $h1s)
	{
		$h1s = "[" . implode("\", \"", $h1s) . "]";
		$h1s = str_replace(array("\r\n", "\n", "\r"), ' ', $h1s);

		$str = "MATCH (n:Page {url: {url} })
				SET n.h1={h1s}";
				//SET n.updateTime=timestamp(), n.h1s={h1s}";

		$vars = array(
					'url' => $url,
					'h1s'  => $h1s
				);

		$this->query($str, $vars);
	}

	protected function scriptQuery($url, $scripts)
	{
		foreach($scripts as $script) {
			$str = "MERGE (n:Page {url:{url}}) 
					MERGE (s:Script {src:{src}, content:{content}}) 
					MERGE (n)-[:CONTAINS_SCRIPT {location: {location}}]->(s)";
					// ON CREATE SET s.updateTime=timestamp(), s.createTime=timestamp()
					// ON MATCH SET s.updateTime=timestamp(), n.updateTime=timestamp()";

			$location = empty($script["src"]) ? "inline" : "external";
			$vars = array(
						'url'   	=> $url,
						'src' 		=> $script["src"],
						'content' 	=> $script["content"],
						'location'	=> $location
					);

			$this->query($str, $vars);
		}
	}

	protected function stylesheetQuery($url, $stylesheets)
	{
		foreach($stylesheets as $stylesheet) {
			$str = "MERGE (n:Page {url: {url} }) 
					MERGE (s:StyleSheet {href: {href}, media: {media}, type: {type} }) 
					MERGE (n)-[:CONTAINS_STYLESHEET]->(s)";
					// ON CREATE SET s.updateTime=timestamp(), s.createTime=timestamp()
					// ON MATCH SET s.updateTime=timestamp(), n.updateTime=timestamp()';

			$vars = array(
						"url"	=> $url,
						"href" 	=> $stylesheet["href"],
						"media" => $stylesheet["media"],
						"type" 	=> $stylesheet["type"]
					);

			$this->query($str, $vars);
		}
	}

	protected function linksQuery($url, $links)
	{
		foreach($links as $link) {
			$str = 'MERGE (n:Page {url: {url} }) 
					MERGE (m:Page {url: {link} }) 
					MERGE (n)-[:LINKS_TO {text: {text} }]->(m)
					ON CREATE SET m.updateTime=timestamp(), m.createTime=timestamp()
					ON MATCH SET n.updateTime=timestamp(), m.updateTime=timestamp()';

			$vars = array(
						'url' 	=> $url,
						'link' 	=> $link["href"],
						'text'	=> $link["text"]
					);

			$this->query($str, $vars);
		}
	}
}