<?php

namespace Snex\Asset\Twig;

use Snex\Asset\AssetFactory;
use Assetic\Contracts\Asset\AssetInterface;
use Twig\TokenParser\AbstractTokenParser;
use Twig\Token;
use Twig\Node\Node;
use Twig\Error\SyntaxError;

class AssetTokenParser extends AbstractTokenParser
{
    protected AssetFactory $factory;
    protected array $types;
    protected string $defaultOutput;

    public function __construct(AssetFactory $factory, array $types, string $defaultOutput = 'misc/*')
    {
        $this->factory = $factory;
        $this->types = $types;
        $this->defaultOutput = $defaultOutput;
    }

    public function getTag() : string
    {
        return 'asset';
    }

    public function testEndTag(Token $token) : bool
    {
        return $token->test(['endasset']);
    }

    public function parse(Token $token) : Node
    {
        $inputs = [];
        $filters = [];
        $name = null;

        $attributes = [
            'output' => $this->defaultOutput,
            'var_name' => 'asset_url',
            'vars' => [],
        ];

        $stream = $this->parser->getStream();

        if (!$stream->test(Token::NAME_TYPE, 'type')) {
            $token = $stream->getCurrent();

            throw new SyntaxError(
                sprintf('Unexpected token "%s" of value "%s"', Token::typeToEnglish($token->getType()), $token->getValue()),
                $token->getLine(),
                $stream->getSourceContext()
            );
        }

        $stream->next();
        $stream->expect(Token::OPERATOR_TYPE, '=');

        $type = $stream->expect(Token::STRING_TYPE)->getValue();

        if (!isset($this->types[$type])) {
           throw new SyntaxError(
                sprintf('Invalid value "%s" for "type" parameter', $token->getValue()),
                $token->getLine(),
                $stream->getSourceContext()
            );
        }

        $attributes['output'] = $this->types[$type]['output'];

        while (!$stream->test(Token::BLOCK_END_TYPE)) {
            if ($stream->test(Token::STRING_TYPE)) {
                // '@jquery', 'js/src/core/*', 'js/src/extra.js'
                $inputs[] = $stream->next()->getValue();
            } elseif ($stream->test(Token::NAME_TYPE, 'filter')) {
                // filter='yui_js'
                $stream->next();
                $stream->expect(Token::OPERATOR_TYPE, '=');

                $filters = array_merge($filters, array_filter(array_map('trim', explode(',', $stream->expect(Token::STRING_TYPE)->getValue()))));
            } elseif ($stream->test(Token::NAME_TYPE, 'output')) {
                // output='js/packed/*.js' OR output='js/core.js'
                $stream->next();
                $stream->expect(Token::OPERATOR_TYPE, '=');

                $attributes['output'] = $stream->expect(Token::STRING_TYPE)->getValue();
            } elseif ($stream->test(Token::NAME_TYPE, 'name')) {
                // name='core_js'
                $stream->next();
                $stream->expect(Token::OPERATOR_TYPE, '=');

                $name = $stream->expect(Token::STRING_TYPE)->getValue();
            } elseif ($stream->test(Token::NAME_TYPE, 'as')) {
                // as='the_url'
                $stream->next();
                $stream->expect(Token::OPERATOR_TYPE, '=');

                $attributes['var_name'] = $stream->expect(Token::STRING_TYPE)->getValue();
            } elseif ($stream->test(Token::NAME_TYPE, 'debug')) {
                // debug=true
                $stream->next();
                $stream->expect(Token::OPERATOR_TYPE, '=');

                $attributes['debug'] = 'true' == $stream->expect(Token::NAME_TYPE, array('true', 'false'))->getValue();
            } elseif ($stream->test(Token::NAME_TYPE, 'combine')) {
                // combine=true
                $stream->next();
                $stream->expect(Token::OPERATOR_TYPE, '=');

                $attributes['combine'] = 'true' == $stream->expect(Token::NAME_TYPE, array('true', 'false'))->getValue();
            } elseif ($stream->test(Token::NAME_TYPE, 'vars')) {
                // vars=['locale','browser']
                $stream->next();
                $stream->expect(Token::OPERATOR_TYPE, '=');
                $stream->expect(Token::PUNCTUATION_TYPE, '[');

                while ($stream->test(Token::STRING_TYPE)) {
                    $attributes['vars'][] = $stream->expect(Token::STRING_TYPE)->getValue();

                    if (!$stream->test(Token::PUNCTUATION_TYPE, ',')) {
                        break;
                    }

                    $stream->next();
                }

                $stream->expect(Token::PUNCTUATION_TYPE, ']');
            } else {
                $token = $stream->getCurrent();

                throw new SyntaxError(
                    sprintf('Unexpected token "%s" of value "%s"', Token::typeToEnglish($token->getType()), $token->getValue()),
                    $token->getLine(),
                    $stream->getSourceContext()
                );
            }
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        $body = $this->parser->subparse([$this, 'testEndTag'], true);

        $stream->expect(Token::BLOCK_END_TYPE);

        if (isset($this->types[$type]['single']) && $this->types[$type]['single'] && 1 < count($inputs)) {
            $inputs = array_slice($inputs, -1);
        }

        if (!$name) {
            $name = $this->factory->generateAssetName($inputs, $filters, $attributes);
        }

        $asset = $this->factory->createAsset($inputs, $filters, $attributes + ['name' => $name]);

        return new AssetNode($this->factory, $asset, $body, $inputs, $filters, $name, $attributes, $token->getLine(), $this->getTag());
    }
}
