<?php namespace Lego\DSL;

use Closure;

/**
 * Class ContextProcessor
 * @package Lego\DSL
 */
class ContextProcessor
{
    /**
     * Compiled matchers.
     * @var Closure
     */
    protected $compiledMatchers;

    public function __construct(Closure $compiledMatchers)
    {
        $this->compiledMatchers = $compiledMatchers;
    }

    public function process(ContextInterface $context)
    {
        $compiledMatchers = $this->compiledMatchers;

        $result = [$context];

        $nodes[] = [
            'index'   => 0,
            'block'   => null,
            'context' => $context,
            'result'  => $result,
        ];

        while ($node = array_pop($nodes)) {
            $nodeBlock   = $node['block'];
            $nodeContext = $node['context'];

            if (is_array($nodeContext)) {
                foreach ($nodeContext as $index => $child) {
                    if (!$child instanceof ContextInterface) {
                        continue;
                    }

                    $nodes[] = [
                        'index'   => $index,
                        'block'   => $nodeBlock,
                        'context' => $child,
                        'result'  => $nodeContext,
                    ];
                }

                $result[$node['index']] = $nodeContext;

                continue;
            }

            if ($nodeContext->element()) {
                $nodeBlock = $nodeContext->block() ?: $nodeBlock;
                $nodeContext->block($nodeBlock);
            } elseif ($nodeContext->block()) {
                $nodeBlock = $nodeContext->block();
            }

            $compiledResult = $compiledMatchers($nodeContext);
            if (null !== $compiledResult) {
                $nodeContext = $compiledResult;

                $node['block']   = $nodeBlock;
                $node['context'] = $nodeContext;

                $nodes[] = $node;

                continue;
            }

            if ($nodeContext->content()) {
                if (is_array($nodeContext->content())) {
                    foreach ($nodeContext->content() as $index => $child) {
                        if (!$child instanceof ContextInterface) {
                            continue;
                        }

                        $nodes[] = [
                            'index'   => $index,
                            'block'   => $nodeBlock,
                            'context' => $child,
                            'result'  => $nodeContext,
                        ];
                    }
                } else {
                    $nodes[] = [
                        'index'   => 'content',
                        'block'   => $nodeBlock,
                        'context' => $nodeContext->content(),
                        'result'  => $nodeContext,
                    ];
                }
            }
        }

        return $result[0];
    }
}
