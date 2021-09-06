<?php

namespace Torchlight\Commonmark;

use Illuminate\Support\Str;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\Xml;
use Torchlight\Block;
use Torchlight\Torchlight;

class TorchlightExtensionV2 implements ExtensionInterface, NodeRendererInterface
{
    public static $torchlightBlocks = [];

    public function register(EnvironmentBuilderInterface $environment): void
    {
        // We start by walking the document immediately after it's parsed
        // to gather up all the code blocks and send off our requests.
        $environment->addEventListener(DocumentParsedEvent::class, [$this, 'onDocumentParsed']);

        // After the document is parsed, it's rendered. We register our
        // renderers with a higher priority than the default ones,
        // and we'll fetch the blocks straight from the cache.
        $environment->addRenderer(FencedCode::class, $this, 10);
        $environment->addRenderer(IndentedCode::class, $this, 10);
    }

    public function onDocumentParsed(DocumentParsedEvent $event)
    {
        $walker = $event->getDocument()->walker();

        while ($event = $walker->next()) {
            /** @var FencedCode|IndentedCode $node */
            $node = $event->getNode();

            // Only look for code nodes, and only process them upon entering.
            if (!$this->isCodeNode($node) || !$event->isEntering()) {
                continue;
            }

            $block = $this->makeTorchlightBlock($node);

            // Set by hash instead of ID, because we'll be remaking all the
            // blocks in the `render` function so the ID will be different,
            // but the hash will always remain the same.
            static::$torchlightBlocks[$block->hash()] = $block;
        }

        // All we need to do is fire the request, which will store
        // the results in the cache. In the render function we
        // use that cached value.
        Torchlight::highlight(static::$torchlightBlocks);
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer)
    {
        $hash = $this->makeTorchlightBlock($node)->hash();

        if (array_key_exists($hash, static::$torchlightBlocks)) {
            $renderer = $this->customBlockRenderer ?? $this->defaultBlockRenderer();

            return call_user_func($renderer, static::$torchlightBlocks[$hash]);
        }
    }

    public function useCustomBlockRenderer($callback)
    {
        $this->customBlockRenderer = $callback;

        return $this;
    }

    public function defaultBlockRenderer()
    {
        return function (Block $block) {
            return "<pre><code class='{$block->classes}' style='{$block->styles}'>{$block->highlighted}</code></pre>";
        };
    }

    protected function makeTorchlightBlock($node)
    {
        return Block::make()
            ->language($this->getLanguage($node))
            ->theme($this->getTheme($node))
            ->code($this->getContent($node));
    }

    protected function isCodeNode($node)
    {
        return $node instanceof FencedCode || $node instanceof IndentedCode;
    }

    protected function getContent($node)
    {
        $content = $node->getLiteral();

        if (!Str::startsWith($content, '<<<')) {
            return $content;
        }

        $file = trim(Str::after($content, '<<<'));

        // It must be only one line, because otherwise it might be a heredoc.
        if (count(explode("\n", $file)) > 1) {
            return $content;
        }

        return Torchlight::processFileContents($file) ?: $content;
    }

    protected function getInfo($node)
    {
        if (!$this->isCodeNode($node) || $node instanceof IndentedCode) {
            return null;
        }

        $infoWords = $node->getInfoWords();

        return empty($infoWords) ? [] : $infoWords;
    }

    protected function getLanguage($node)
    {
        $language = $this->getInfo($node)[0];

        return $language ? Xml::escape($language, true) : null;
    }

    protected function getTheme($node)
    {
        foreach ($this->getInfo($node) as $item) {
            if (Str::startsWith($item, 'theme:')) {
                return Str::after($item, 'theme:');
            }
        }
    }
}
