<?php

namespace Torchlight\Commonmark\V1;

use League\CommonMark\Block\Element\AbstractBlock;
use League\CommonMark\Block\Element\FencedCode;
use League\CommonMark\Block\Element\IndentedCode;
use League\CommonMark\Block\Renderer\BlockRendererInterface;
use League\CommonMark\ConfigurableEnvironmentInterface;
use League\CommonMark\ElementRendererInterface;
use League\CommonMark\Extension\ExtensionInterface;
use Torchlight\Commonmark\BaseExtension;

class TorchlightExtension extends BaseExtension implements ExtensionInterface, BlockRendererInterface
{
    /**
     * This method just proxies to our base class, but the
     * signature has to match Commonmark V1.
     *
     * @param  ConfigurableEnvironmentInterface  $environment
     */
    public function register(ConfigurableEnvironmentInterface $environment)
    {
        $this->bind($environment, 'addBlockRenderer');
    }

    /**
     * This method just proxies to our base class, but the
     * signature has to match Commonmark V1.
     *
     * @param  AbstractBlock  $block
     * @param  ElementRendererInterface  $htmlRenderer
     * @param  false  $inTightList
     * @return mixed
     */
    public function render(AbstractBlock $block, ElementRendererInterface $htmlRenderer, $inTightList = false)
    {
        return $this->renderNode($block);
    }

    /**
     * V1 Code node classes.
     *
     * @return string[]
     */
    protected function codeNodes()
    {
        return [
            FencedCode::class,
            IndentedCode::class,
        ];
    }

    /**
     * Get the string content from a V1 node.
     *
     * @param  $node
     * @return string
     */
    protected function getLiteralContent($node)
    {
        return $node->getStringContent();
    }
}
