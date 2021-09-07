<?php

namespace Torchlight\Commonmark\Test\V2;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Parser\MarkdownParser;
use League\CommonMark\Renderer\HtmlRenderer;
use Torchlight\Commonmark\Tests\BaseRendererTest;

class CodeRendererTest extends BaseRendererTest
{
    protected function version()
    {
        return 2;
    }

    protected function extension()
    {
        return new \Torchlight\Commonmark\V2\TorchlightExtension();
    }

    protected function render($markdown, $extension = null)
    {
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension($extension ?? $this->extension());

        $parser = new MarkdownParser($environment);
        $htmlRenderer = new HtmlRenderer($environment);

        $document = $parser->parse($markdown);

        return (string)$htmlRenderer->renderDocument($document);
    }
}
