<?php namespace Lego\DSL;

/**
 * Class Engine
 *
 * @package Lego\DSL
 */
class Engine
{
    /**
     * Collection of matchers.
     *
     * @var MatcherCollectionInterface
     */
    protected $matcherCollection;

    /**
     * Compiled matchers.
     *
     * @var \Closure
     */
    protected $compiledMatchers;

    /**
     * Create new Engine instance.
     */
    public function __construct()
    {
        $this->matcherCollection = new MatcherCollection;
    }

    /**
     * Registers new matcher
     *
     * @param string|array $expr
     * @param \Closure $callback
     *
     * @return Engine
     */
    public function registerMatcher($expr, \Closure $callback)
    {
        if (is_array($expr)) {
            return array_map(function ($expr) use ($callback) {
                return $this->registerMatcher($expr, $callback);
            }, $expr);
        }

        $this->matcherCollection[$expr] = $callback;
        $this->compiledMatchers         = null;

        return $this;
    }

    /**
     * Renders
     *
     * @param ContextInterface $context
     *
     * @return string
     */
    public function render(ContextInterface $context)
    {
        return (new Element($this->resolveContext($context)))->render();
    }

    protected function resolveContext(ContextInterface $context)
    {
        $compiledMatchers = $this->getCompiledMatchers();

        $nodes[] = $context;

        /**
         * @var $node ContextInterface
         */
        while ($node = array_shift($nodes)) {
            /**
             * @var ContextInterface $child
             */
            foreach ($node->content() as $child) {
                if (!$child instanceof ContextInterface) {
                    continue;
                }

                $nodes[] = $child->block($node->block());
            }

            $compiledMatchers($node);
        }

        return $context;
    }

    protected function getCompiledMatchers()
    {
        if (null === $this->compiledMatchers) {
            $this->compiledMatchers = (new MatcherCompiler($this->matcherCollection))->compile();
        }

        return $this->compiledMatchers;
    }
}
