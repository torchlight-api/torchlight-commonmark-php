<?php

namespace Torchlight\Commonmark;

use Illuminate\Support\Str;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Util\Xml;
use Torchlight\Block;
use Torchlight\Torchlight;

abstract class BaseExtension
{
    /**
     * @var array
     */
    public static $torchlightBlocks = [];

    /**
     * @var callable
     */
    protected $customBlockRenderer;

    /**
     * @param  DocumentParsedEvent  $event
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
        Torchlight::highlight(static::$torchlightBlocks);
    }

    /**
     * @param  callable  $callback
     * @return $this
     */
    public function useCustomBlockRenderer($callback)
    {
        $this->customBlockRenderer = $callback;

        return $this;
    }

    /**
     * @return \Closure
     */
    public function defaultBlockRenderer()
    {
        return function (Block $block) {
            $inner = '';

            // Clones come from multiple themes.
            $blocks = $block->clones();
            array_unshift($blocks, $block);

            foreach ($blocks as $block) {
                $inner .= "<code {$block->attrsAsString()}class='{$block->classes}' style='{$block->styles}'>{$block->highlighted}</code>";
            }

            return "<pre>$inner</pre>";
        };
    }

    /**
     * @return array
     */
    abstract protected function codeNodes();

    /**
     * @param  $node
     * @return string
     */
    abstract protected function getLiteralContent($node);

    /**
     * Bind into a Commonmark V1 or V2 environment.
     *
     * @param  $environment
     * @param  string  $renderMethod
     */
    protected function bind($environment, $renderMethod)
    {
        // We start by walking the document immediately after it's parsed
        // to gather all the code blocks and send off our requests.
        $environment->addEventListener(DocumentParsedEvent::class, [$this, 'onDocumentParsed']);

        foreach ($this->codeNodes() as $blockType) {
            // After the document is parsed, it's rendered. We register our
            // renderers with a higher priority than the default ones,
            // and we'll fetch the blocks straight from the cache.
            $environment->{$renderMethod}($blockType, $this, 10);
        }
    }

    /**
     * @param  $node
     * @return bool
     */
    protected function isCodeNode($node)
    {
        return in_array(get_class($node), $this->codeNodes());
    }

    /**
     * @param  $node
     * @return Block
     */
    protected function makeTorchlightBlock($node)
    {
        return Block::make()
            ->language($this->getLanguage($node))
            ->theme($this->getTheme($node))
            ->code($this->getContent($node));
    }

    /**
     * @param  $node
     * @return string
     */
    protected function renderNode($node)
    {
        $hash = $this->makeTorchlightBlock($node)->hash();

        if (array_key_exists($hash, static::$torchlightBlocks)) {
            $renderer = $this->customBlockRenderer ?? $this->defaultBlockRenderer();

            return call_user_func($renderer, static::$torchlightBlocks[$hash]);
        }
    }

    /**
     * @param  $node
     * @return string
     */
    protected function getContent($node)
    {
        $content = $this->getLiteralContent($node);

        // Check for our file loading convention.
        if (!Str::contains($content, '<<<')) {
            return $content;
        }

        $file = trim(Str::after($content, '<<<'));

        // It must be only one line, because otherwise it might be a heredoc.
        if (count(explode("\n", $file)) > 1) {
            return $content;
        }

        // Blow off the end of comments that require closing tags, e.g. <!-- -->
        $file = head(explode(' ', $file));

        return Torchlight::processFileContents($file) ?: $content;
    }

    /**
     * @param  $node
     * @return array|mixed|null
     */
    protected function getInfo($node)
    {
        if (!$this->isCodeNode($node)) {
            return [];
        }

        if (!is_callable([$node, 'getInfoWords'])) {
            return [];
        }

        $infoWords = $node->getInfoWords();

        return empty($infoWords) ? [] : $infoWords;
    }

    /**
     * @param  $node
     * @return string|null
     */
    protected function getLanguage($node)
    {
        $info = $this->getInfo($node);

        if (empty($info)) {
            return null;
        }

        $language = $info[0];

        return $language ? Xml::escape($language, true) : null;
    }

    /**
     * @param  $node
     * @return string
     */
    protected function getTheme($node)
    {
        foreach ($this->getInfo($node) as $item) {
            if (Str::startsWith($item, 'theme:')) {
                return Str::after($item, 'theme:');
            }
        }
    }
}
