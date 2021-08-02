<?php

namespace Torchlight\Commonmark\Tests;

use Illuminate\Support\Facades\Http;
use League\CommonMark\DocParser;
use League\CommonMark\Environment;
use League\CommonMark\HtmlRenderer;
use Orchestra\Testbench\TestCase;
use Torchlight\Block;
use Torchlight\Commonmark\TorchlightExtension;

class CodeRendererTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        config()->set('torchlight.token', 'token');

        $ids = [
            'block_id_1',
            'block_id_2',
            'block_id_3',
        ];

        Block::$generateIdsUsing = function () use (&$ids) {
            return array_shift($ids);
        };
    }

    protected function render($markdown)
    {
        $environment = Environment::createCommonMarkEnvironment();
        $environment->addExtension(new TorchlightExtension);

        $parser = new DocParser($environment);
        $htmlRenderer = new HtmlRenderer($environment);

        $document = $parser->parse($markdown);

        return $htmlRenderer->renderBlock($document);
    }

    /** @test */
    public function it_highlights_code_blocks()
    {
        $markdown = <<<'EOT'
before

```html
<div>html</div>
```
after
EOT;

        $response = [
            'blocks' => [[
                'id' => 'block_id_1',
                'classes' => 'torchlight',
                'styles' => 'color: red;',
                'highlighted' => 'highlighted',
            ]]
        ];

        Http::fake([
            'api.torchlight.dev/*' => Http::response($response, 200),
        ]);

        $html = $this->render($markdown);

        $expected = <<<EOT
<p>before</p>
<pre><code class='torchlight' style='color: red;'>highlighted</code></pre>
<p>after</p>

EOT;

        $this->assertEquals($expected, $html);
    }

    /** @test */
    public function it_can_set_a_custom_renderer()
    {
        $markdown = <<<'EOT'
```html
<div>html</div>
```
EOT;

        $response = [
            'blocks' => [[
                'id' => 'block_id_1',
                'wrapped' => '<pre><code>highlighted</code></pre>',
            ]]
        ];

        Http::fake([
            'api.torchlight.dev/*' => Http::response($response, 200),
        ]);

        $extension = new TorchlightExtension;
        $extension->useCustomBlockRenderer(function (Block $block) {
            return 'foo_bar';
        });

        $environment = Environment::createCommonMarkEnvironment();
        $environment->addExtension($extension);

        $parser = new DocParser($environment);
        $htmlRenderer = new HtmlRenderer($environment);

        $document = $parser->parse($markdown);

        $html = $htmlRenderer->renderBlock($document);

        $expected = <<<EOT
foo_bar

EOT;

        $this->assertEquals($expected, $html);
    }

    /** @test */
    public function gets_language_and_contents()
    {
        $markdown = <<<'EOT'
before

```foobarlang
<div>test</div>
```
after
EOT;

        Http::fake();

        $this->render($markdown);

        Http::assertSent(function ($request) {
            return $request['blocks'][0]['language'] === 'foobarlang'
                && $request['blocks'][0]['code'] === '<div>test</div>'
                && $request['blocks'][0]['theme'] === null;
        });
    }

    /** @test */
    public function can_set_theme()
    {
        $markdown = <<<'EOT'
before

```lang theme:daft-punk 
<div>test</div>
```
after
EOT;

        Http::fake();

        $this->render($markdown);

        Http::assertSent(function ($request) {
            return $request['blocks'][0]['language'] === 'lang'
                && $request['blocks'][0]['code'] === '<div>test</div>'
                && $request['blocks'][0]['theme'] === 'daft-punk';
        });
    }

    /** @test */
    public function it_sends_one_request_only_and_matches_by_id()
    {
        $markdown = <<<'EOT'
before

```php
some php
```

```ruby
some ruby
```

```js
some js
```
after
EOT;

        $response = [
            'blocks' => [[
                'id' => 'block_id_3',
                'highlighted' => 'some js',
            ], [
                'id' => 'block_id_1',
                'highlighted' => 'some php',
            ], [
                'id' => 'block_id_2',
                'highlighted' => 'some ruby',
            ]]
        ];

        Http::fake([
            'api.torchlight.dev/*' => Http::response($response, 200),
        ]);

        $html = $this->render($markdown);

        Http::assertSentCount(1);

        $expected = <<<EOT
<p>before</p>
<pre><code class='' style=''>some php</code></pre>
<pre><code class='' style=''>some ruby</code></pre>
<pre><code class='' style=''>some js</code></pre>
<p>after</p>

EOT;

        $this->assertEquals($expected, $html);
    }
}
