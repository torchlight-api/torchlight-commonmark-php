<?php

namespace Torchlight\Commonmark\Tests\V1;

use League\CommonMark\DocParser;
use League\CommonMark\Environment;
use League\CommonMark\HtmlRenderer;
use Torchlight\Commonmark\Tests\BaseRendererTest;

class CodeRendererTest extends BaseRendererTest
{
    protected function version()
    {
        return 1;
    }

    protected function extension()
    {
        return new \Torchlight\Commonmark\V1\TorchlightExtension();
    }

    protected function render($markdown, $extension = null)
    {
        $environment = Environment::createCommonMarkEnvironment();
        $environment->addExtension($extension ?? $this->extension());

        $parser = new DocParser($environment);
        $htmlRenderer = new HtmlRenderer($environment);

        $document = $parser->parse($markdown);

        return $htmlRenderer->renderBlock($document);
    }
}
