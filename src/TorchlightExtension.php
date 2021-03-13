<?php

namespace Hammerstone\Torchlight\Commonmark;

use Hammerstone\Torchlight\Block;
use Hammerstone\Torchlight\Client;
use League\CommonMark\Block\Element\AbstractBlock;
use League\CommonMark\Block\Element\FencedCode;
use League\CommonMark\Block\Element\IndentedCode;
use League\CommonMark\Block\Renderer\BlockRendererInterface;
use League\CommonMark\ConfigurableEnvironmentInterface;
use League\CommonMark\ElementRendererInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Util\Xml;

class TorchlightExtension implements ExtensionInterface, BlockRendererInterface
{
    public static $torchlightBlocks = [];

    public function register(ConfigurableEnvironmentInterface $environment)
    {
        // We start by walking the document immediately after it's parsed
        // to gather up all the code blocks and send off our requests.
        $environment->addEventListener(DocumentParsedEvent::class, [$this, 'onDocumentParsed']);


        // After the document is parsed, it's rendered. We register our
        // renderers with a higher priority than the default ones,
        // and we'll fetch the blocks straight from the cache.
        $environment->addBlockRenderer(FencedCode::class, $this, 10);
        $environment->addBlockRenderer(IndentedCode::class, $this, 10);

    }

    /**
     * @param DocumentParsedEvent $event
     */
    public function onDocumentParsed(DocumentParsedEvent $event)
    {
        $walker = $event->getDocument()->walker();

        while ($event = $walker->next()) {
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
        (new Client)->highlight(static::$torchlightBlocks);
    }

    public function render(AbstractBlock $block, ElementRendererInterface $htmlRenderer, $inTightList = false)
    {
        $hash = $this->makeTorchlightBlock($block)->hash();

        if (array_key_exists(static::$torchlightBlocks, $hash)) {
            return static::$torchlightBlocks[$hash]->html;
        }
    }

    protected function makeTorchlightBlock($node)
    {
        return Block::make()
            ->setLanguage($this->getLanguage($node))
            ->setCode($node->getStringContent());
    }

    protected function isCodeNode($node)
    {
        return $node instanceof FencedCode || $node instanceof IndentedCode;
    }

    protected function getLanguage($node)
    {
        if (!$this->isCodeNode($node) || $node instanceof IndentedCode) {
            return null;
        }

        $infoWords = $node->getInfoWords();

        if (empty($infoWords) || empty($infoWords[0])) {
            return null;
        }

        return Xml::escape($infoWords[0], true);
    }
}
