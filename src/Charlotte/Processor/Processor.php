<?php

namespace Charlotte\Processor;

abstract class Processor
{
    protected $connector;

    public function __construct($connector = '')
    {
        if(!empty($connector)) {
            $this->setConnector($connector);
        }
    }

    public function setConnector($connector)
    {
        $this->connector = $connector;
    }

    public function process($page)
    {
        if(!empty($page["url"])) {
            $this->urlQuery($page["url"], $page["title"]);
            if(!empty($page["metas"])) $this->metaQuery($page["url"], $page["metas"]);
            if(!empty($page["h1s"])) $this->h1Query($page["url"], $page["h1s"]);
            if(!empty($page["scripts"])) $this->scriptQuery($page["url"], $page["scripts"]);
            if(!empty($page["stylesheets"])) $this->stylesheetQuery($page["url"], $page["stylesheets"]);
            if(!empty($page["keywords"])) $this->keywordQuery($page["url"], $page["keywords"]);
            if(!empty($page["links"])) $this->linksQuery($page["url"], $page["links"]);
        }
    }

    abstract protected function query($str, $vars = array());
    abstract protected function urlQuery($url, $title);
    abstract protected function metaQuery($url, $metas);
    abstract protected function keywordQuery($url, $keywords);
    abstract protected function h1Query($url, $headers);
    abstract protected function scriptQuery($url, $scripts);
    abstract protected function stylesheetQuery($url, $stylesheets);
    abstract protected function linksQuery($url, $links);
}
