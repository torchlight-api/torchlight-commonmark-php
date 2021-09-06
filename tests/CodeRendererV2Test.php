<?php

namespace Torchlight\Commonmark\Tests;

use Illuminate\Support\Facades\Http;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Parser\MarkdownParser;
use League\CommonMark\Renderer\HtmlRenderer;
use Orchestra\Testbench\TestCase;
use Torchlight\Block;
use Torchlight\Commonmark\TorchlightExtensionV2;

class CodeRendererV2Test extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        if (interface_exists('League\\CommonMark\\ConfigurableEnvironmentInterface')) {
            $this->markTestSkipped('CommonMark V2 not detected.');
        }

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
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new TorchlightExtensionV2);

        $parser = new MarkdownParser($environment);
        $htmlRenderer = new HtmlRenderer($environment);

        $document = $parser->parse($markdown);

        return (string)$htmlRenderer->renderDocument($document);
    }

    /** @test */
    public function it_highlights_code_blocks_v2()
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

        $extension = new TorchlightExtensionV2();
        $extension->useCustomBlockRenderer(function (Block $block) {
            return 'foo_bar';
        });

        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension($extension);

        $parser = new MarkdownParser($environment);
        $htmlRenderer = new HtmlRenderer($environment);

        $document = $parser->parse($markdown);

        $html = (string)$htmlRenderer->renderDocument($document);

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
    public function can_load_file()
    {
        config()->set('torchlight.snippet_directories', [
            __DIR__
        ]);

        $markdown = <<<'EOT'
``` 
<<< Support/file1.php
```
EOT;

        Http::fake();

        $this->render($markdown);

        Http::assertSent(function ($request) {
            return $request['blocks'][0]['code'] === '// this is file 1';
        });
    }

    /** @test */
    public function non_existent_file_just_stays()
    {
        $markdown = <<<'EOT'
``` 
<<< Support/nonexistent.php
```
EOT;

        Http::fake();

        $this->render($markdown);

        Http::assertSent(function ($request) {
            return $request['blocks'][0]['code'] === '<<< Support/nonexistent.php';
        });
    }


    /** @test */
    public function doesnt_load_heredoc()
    {
        $markdown = <<<'EOT'
``` 
<<<SQL
select 1;
SQL;
```
EOT;

        Http::fake();

        $this->render($markdown);

        Http::assertSent(function ($request) {
            return $request['blocks'][0]['code'] === "<<<SQL\nselect 1;\nSQL;";
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
