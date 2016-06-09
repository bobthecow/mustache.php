<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2015 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Mustache Compiler class.
 *
 * This class is responsible for turning a Mustache token parse tree into normal PHP source code.
 */
class Mustache_Compiler
{
    private $pragmas;
    private $defaultPragmas = array();
    private $sections;
    private $blocks;
    private $source;
    private $indentNextLine;
    private $customEscape;
    private $entityFlags;
    private $charset;
    private $strictCallables;

    /**
     * Compile a Mustache token parse tree into PHP source code.
     *
     * @param string $source          Mustache Template source code
     * @param string $tree            Parse tree of Mustache tokens
     * @param string $name            Mustache Template class name
     * @param bool   $customEscape    (default: false)
     * @param string $charset         (default: 'UTF-8')
     * @param bool   $strictCallables (default: false)
     * @param int    $entityFlags     (default: ENT_COMPAT)
     *
     * @return string Generated PHP source code
     */
    public function compile($source, array $tree, $name, $customEscape = false, $charset = 'UTF-8', $strictCallables = false, $entityFlags = ENT_COMPAT)
    {
        $this->pragmas         = $this->defaultPragmas;
        $this->sections        = array();
        $this->blocks          = array();
        $this->source          = $source;
        $this->indentNextLine  = true;
        $this->customEscape    = $customEscape;
        $this->entityFlags     = $entityFlags;
        $this->charset         = $charset;
        $this->strictCallables = $strictCallables;

        return $this->writeCode($tree, $name);
    }

    /**
     * Enable pragmas across all templates, regardless of the presence of pragma
     * tags in the individual templates.
     *
     * @internal Users should set global pragmas in Mustache_Engine, not here :)
     *
     * @param string[] $pragmas
     */
    public function setPragmas(array $pragmas)
    {
        $this->pragmas = array();
        foreach ($pragmas as $pragma) {
            $this->pragmas[$pragma] = true;
        }
        $this->defaultPragmas = $this->pragmas;
    }

    /**
     * Helper function for walking the Mustache token parse tree.
     *
     * @throws Mustache_Exception_SyntaxException upon encountering unknown token types.
     *
     * @param array $tree  Parse tree of Mustache tokens
     * @param int   $level (default: 0)
     *
     * @return string Generated PHP source code
     */
    private function walk(array $tree, $level = 0)
    {
        $code = '';
        $level++;
        foreach ($tree as $node) {
            switch ($node[Mustache_Tokenizer::TYPE]) {
                case Mustache_Tokenizer::T_PRAGMA:
                    $this->pragmas[$node[Mustache_Tokenizer::NAME]] = true;
                    break;

                case Mustache_Tokenizer::T_SECTION:
                    $code .= $this->section(
                        $node[Mustache_Tokenizer::NODES],
                        $node[Mustache_Tokenizer::NAME],
                        isset($node[Mustache_Tokenizer::FILTERS]) ? $node[Mustache_Tokenizer::FILTERS] : array(),
                        isset($node[Mustache_Tokenizer::ATTRS]) ? $node[Mustache_Tokenizer::ATTRS] : array(),
                        $node[Mustache_Tokenizer::INDEX],
                        $node[Mustache_Tokenizer::END],
                        $node[Mustache_Tokenizer::OTAG],
                        $node[Mustache_Tokenizer::CTAG],
                        $level
                    );
                    break;

                case Mustache_Tokenizer::T_INVERTED:
                    $code .= $this->invertedSection(
                        $node[Mustache_Tokenizer::NODES],
                        $node[Mustache_Tokenizer::NAME],
                        isset($node[Mustache_Tokenizer::FILTERS]) ? $node[Mustache_Tokenizer::FILTERS] : array(),
                        isset($node[Mustache_Tokenizer::ATTRS]) ? $node[Mustache_Tokenizer::ATTRS] : array(),
                        $level
                    );
                    break;

                case Mustache_Tokenizer::T_PARTIAL:
                    $code .= $this->partial(
                        $node[Mustache_Tokenizer::NAME],
                        isset($node[Mustache_Tokenizer::INDENT]) ? $node[Mustache_Tokenizer::INDENT] : '',
                        isset($node[Mustache_Tokenizer::ATTRS]) ? $node[Mustache_Tokenizer::ATTRS] : array(),
                        $level
                    );
                    break;

                case Mustache_Tokenizer::T_PARENT:
                    $code .= $this->parent(
                        $node[Mustache_Tokenizer::NAME],
                        isset($node[Mustache_Tokenizer::INDENT]) ? $node[Mustache_Tokenizer::INDENT] : '',
                        $node[Mustache_Tokenizer::NODES],
                        isset($node[Mustache_Tokenizer::ATTRS]) ? $node[Mustache_Tokenizer::ATTRS] : array(),
                        $level
                    );
                    break;

                case Mustache_Tokenizer::T_BLOCK_ARG:
                    $code .= $this->blockArg(
                        $node[Mustache_Tokenizer::NODES],
                        isset($node[Mustache_Tokenizer::ATTRS]) ? $node[Mustache_Tokenizer::ATTRS] : array(),
                        $node[Mustache_Tokenizer::NAME],
                        $node[Mustache_Tokenizer::INDEX],
                        $node[Mustache_Tokenizer::END],
                        $node[Mustache_Tokenizer::OTAG],
                        $node[Mustache_Tokenizer::CTAG],
                        $level
                    );
                    break;

                case Mustache_Tokenizer::T_BLOCK_VAR:
                    $code .= $this->blockVar(
                        $node[Mustache_Tokenizer::NODES],
                        $node[Mustache_Tokenizer::NAME],
                        $node[Mustache_Tokenizer::INDEX],
                        $node[Mustache_Tokenizer::END],
                        $node[Mustache_Tokenizer::OTAG],
                        $node[Mustache_Tokenizer::CTAG],
                        $level
                    );
                    break;

                case Mustache_Tokenizer::T_COMMENT:
                    break;

                case Mustache_Tokenizer::T_ESCAPED:
                case Mustache_Tokenizer::T_UNESCAPED:
                case Mustache_Tokenizer::T_UNESCAPED_2:
                    $code .= $this->variable(
                        $node[Mustache_Tokenizer::NAME],
                        isset($node[Mustache_Tokenizer::FILTERS]) ? $node[Mustache_Tokenizer::FILTERS] : array(),
                        isset($node[Mustache_Tokenizer::ATTRS]) ? $node[Mustache_Tokenizer::ATTRS] : array(),
                        $node[Mustache_Tokenizer::TYPE] === Mustache_Tokenizer::T_ESCAPED,
                        $level
                    );
                    break;

                case Mustache_Tokenizer::T_TEXT:
                    $code .= $this->text($node[Mustache_Tokenizer::VALUE], $level);
                    break;

                default:
                    throw new Mustache_Exception_SyntaxException(sprintf('Unknown token type: %s', $node[Mustache_Tokenizer::TYPE]), $node);
            }
        }

        return $code;
    }

    const KLASS = '<?php

        class %s extends Mustache_Template
        {
            private $lambdaHelper;%s

            public function renderInternal(Mustache_Context $context, $indent = \'\')
            {
                $this->lambdaHelper = new Mustache_LambdaHelper($this->mustache, $context);
                $buffer = \'\';
                $newContext = array();
        %s

                return $buffer;
            }
        %s
        %s
        }';

    const KLASS_NO_LAMBDAS = '<?php

        class %s extends Mustache_Template
        {%s
            public function renderInternal(Mustache_Context $context, $indent = \'\')
            {
                $buffer = \'\';
                $newContext = array();
        %s

                return $buffer;
            }
        }';

    const STRICT_CALLABLE = 'protected $strictCallables = true;';

    /**
     * Generate Mustache Template class PHP source.
     *
     * @param array  $tree Parse tree of Mustache tokens
     * @param string $name Mustache Template class name
     *
     * @return string Generated PHP source code
     */
    private function writeCode($tree, $name)
    {
        $code     = $this->walk($tree);
        $sections = implode("\n", $this->sections);
        $blocks   = implode("\n", $this->blocks);
        $klass    = empty($this->sections) && empty($this->blocks) ? self::KLASS_NO_LAMBDAS : self::KLASS;

        $callable = $this->strictCallables ? $this->prepare(self::STRICT_CALLABLE) : '';

        return sprintf($this->prepare($klass, 0, false, true), $name, $callable, $code, $sections, $blocks);
    }

    const BLOCK_VAR = '
        $blockFunction = $context->findInBlock(%s);
        if (is_callable($blockFunction)) {
            $buffer .= call_user_func($blockFunction, $context);
        } else {%s
        }
    ';

    /**
     * Generate Mustache Template inheritance block variable PHP source.
     *
     * @param array  $nodes Array of child tokens
     * @param string $id    Section name
     * @param int    $start Section start offset
     * @param int    $end   Section end offset
     * @param string $otag  Current Mustache opening tag
     * @param string $ctag  Current Mustache closing tag
     * @param int    $level
     *
     * @return string Generated PHP source code
     */
    private function blockVar($nodes, $id, $start, $end, $otag, $ctag, $level)
    {
        $id = var_export($id, true);

        return sprintf($this->prepare(self::BLOCK_VAR, $level), $id, $this->walk($nodes, $level));
    }

    const BLOCK_ARG = '$newContext[%s] = array($this, \'block%s\');';

    /**
     * Generate Mustache Template inheritance block argument PHP source.
     *
     * @param array  $nodes Array of child tokens
     * @param array  $attrs Array of attributes
     * @param string $id    Section name
     * @param int    $start Section start offset
     * @param int    $end   Section end offset
     * @param string $otag  Current Mustache opening tag
     * @param string $ctag  Current Mustache closing tag
     * @param int    $level
     *
     * @return string Generated PHP source code
     */
    private function blockArg($nodes, array $attrs, $id, $start, $end, $otag, $ctag, $level)
    {
        $key = $this->block($nodes, $attrs);
        $keystr = var_export($key, true);
        $id = var_export($id, true);

        return sprintf($this->prepare(self::BLOCK_ARG, 1), $id, $key);
    }

    const BLOCK_FUNCTION = '
        public function block%s($context)
        {
            $indent = $buffer = \'\';%s

            return $buffer;
        }
    ';

    /**
     * Generate Mustache Template inheritance block function PHP source.
     *
     * @param array $nodes Array of child tokens
     * @param array $attrs Array of attributes
     *
     * @return string key of new block function
     */
    private function block($nodes, array $attrs)
    {
        $code = $this->walk($nodes, 0);
        $key = ucfirst(md5($code));

        if (count($attrs)) {
            $code = $this->wrapAttrContext($attrs, $code, 1);
        }

        if (!isset($this->blocks[$key])) {
            $this->blocks[$key] = sprintf($this->prepare(self::BLOCK_FUNCTION, 0), $key, $code);
        }

        return $key;
    }

    const SECTION_CALL = '
        // %s section
        $value = $context->%s(%s);%s
        $buffer .= $this->section%s($context, $indent, $value);
    ';

    const SECTION = '
        private function section%s(Mustache_Context $context, $indent, $value)
        {
            $buffer = \'\';
            if (%s) {
                $source = %s;
                $result = call_user_func($value, $source, %s);
                if (strpos($result, \'{{\') === false) {
                    $buffer .= $result;
                } else {
                    $buffer .= $this->mustache
                        ->loadLambda((string) $result%s)
                        ->renderInternal($context);
                }
            } elseif (!empty($value)) {
                $values = $this->isIterable($value) ? $value : array($value);
                foreach ($values as $value) {
                    $context->push($value);
                    %s
                    $context->pop();
                }
            }

            return $buffer;
        }
    ';

    /**
     * Generate Mustache Template section PHP source.
     *
     * @param array    $nodes   Array of child tokens
     * @param string   $id      Section name
     * @param string[] $filters Array of filters
     * @param array[]  $attrs   Array of attributes
     * @param int      $start   Section start offset
     * @param int      $end     Section end offset
     * @param string   $otag    Current Mustache opening tag
     * @param string   $ctag    Current Mustache closing tag
     * @param int      $level
     * @param bool     $arg     (default: false)
     *
     * @return string Generated section PHP source code
     */
    private function section($nodes, $id, $filters, array $attrs, $start, $end, $otag, $ctag, $level, $arg = false)
    {
        $source   = var_export(substr($this->source, $start, $end - $start), true);
        $callable = $this->getCallable();

        if ($otag !== '{{' || $ctag !== '}}') {
            $delimTag = var_export(sprintf('{{= %s %s =}}', $otag, $ctag), true);
            $helper = sprintf('$this->lambdaHelper->withDelimiters(%s)', $delimTag);
            if (count($attrs)) {
                $helper = $helper . ', ' . $this->passAttrs($attrs, 3);
            }
            $delims = ', ' . $delimTag;
        } else {
            $helper = '$this->lambdaHelper';
            if (count($attrs)) {
                $helper .= ', ' . $this->passAttrs($attrs, 3);
            }
            $delims = '';
        }

        $key = ucfirst(md5($delims . "\n" . $source));

        if (!isset($this->sections[$key])) {
            $body = $this->walk($nodes, 2);
            if (count($attrs) > 0) {
                $body = $this->wrapAttrContext($attrs, $body, 3);
            }
            $this->sections[$key] = sprintf($this->prepare(self::SECTION), $key, $callable, $source, $helper, $delims, $body);
        }

        if ($arg === true) {
            return $key;
        } else {
            $method  = $this->getFindMethod($id);
            $id      = var_export($id, true);
            $filters = $this->getFilters($filters, $level);

            return sprintf($this->prepare(self::SECTION_CALL, $level), $id, $method, $id, $filters, $key);
        }
    }

    const INVERTED_SECTION = '
        // %s inverted section
        $value = $context->%s(%s);%s
        if (empty($value)) {
            %s
        }
    ';

    /**
     * Generate Mustache Template inverted section PHP source.
     *
     * @param array    $nodes   Array of child tokens
     * @param string   $id      Section name
     * @param string[] $filters Array of filters
     * @param array[]  $attrs   Array of attributes
     * @param int      $level
     *
     * @return string Generated inverted section PHP source code
     */
    private function invertedSection($nodes, $id, $filters, $attrs, $level)
    {
        $method  = $this->getFindMethod($id);
        $id      = var_export($id, true);
        $filters = $this->getFilters($filters, $level);

        $body = $this->walk($nodes, $level);
        if (count($attrs) > 0) {
            $body = $this->wrapAttrContext($attrs, $body, $level);
        }

        return sprintf($this->prepare(self::INVERTED_SECTION, $level), $id, $method, $id, $filters, $body);
    }

    const PARTIAL_INDENT = ', $indent . %s';
    const PARTIAL = '
        if ($partial = $this->mustache->loadPartial(%s)) {
            $buffer .= $partial->renderInternal($context%s);
        }
    ';

    /**
     * Generate Mustache Template partial call PHP source.
     *
     * @param string $id     Partial name
     * @param string $indent Whitespace indent to apply to partial
     * @param array  $attrs  Array of attributes
     * @param int    $level
     *
     * @return string Generated partial call PHP source code
     */
    private function partial($id, $indent, array $attrs, $level)
    {
        if ($indent !== '') {
            $indentParam = sprintf(self::PARTIAL_INDENT, var_export($indent, true));
        } else {
            $indentParam = '';
        }

        $body = sprintf(
            $this->prepare(self::PARTIAL, $level),
            var_export($id, true),
            $indentParam
        );

        if (count($attrs) > 0) {
            $body = $this->wrapAttrContext($attrs, $body, $level);
        }

        return $body;
    }

    const PARENT = '
        %s

        if ($parent = $this->mustache->LoadPartial(%s)) {
            $context->pushBlockContext($newContext);
            $buffer .= $parent->renderInternal($context, $indent);
            $context->popBlockContext();
        }
    ';

    /**
     * Generate Mustache Template inheritance parent call PHP source.
     *
     * @param string $id       Parent tag name
     * @param string $indent   Whitespace indent to apply to parent
     * @param array  $children Child nodes
     * @param array  $attrs    Array of attributes
     * @param int    $level
     *
     * @return string Generated PHP source code
     */
    private function parent($id, $indent, array $children, array $attrs, $level)
    {
        $realChildren = array_filter($children, array(__CLASS__, 'onlyBlockArgs'));

        $body =  sprintf(
            $this->prepare(self::PARENT, $level),
            $this->walk($realChildren, $level),
            var_export($id, true),
            var_export($indent, true)
        );

        if (count($attrs) > 0) {
            $body = $this->wrapAttrContext($attrs, $body, $level);
        }

        return $body;
    }

    /**
     * Helper method for filtering out non-block-arg tokens.
     *
     * @param array $node
     *
     * @return bool True if $node is a block arg token.
     */
    private static function onlyBlockArgs(array $node)
    {
        return $node[Mustache_Tokenizer::TYPE] === Mustache_Tokenizer::T_BLOCK_ARG;
    }

    const VARIABLE = '
        $value = $this->resolveValue($context->%s(%s), $context%s);%s
        $buffer .= %s%s;
    ';

    /**
     * Generate Mustache Template variable interpolation PHP source.
     *
     * @todo handle attributes
     *
     * @param string   $id      Variable name
     * @param string[] $filters Array of filters
     * @param array    $attrs   Array of attributes
     * @param bool     $escape  Escape the variable value for output?
     * @param int      $level
     *
     * @return string Generated variable interpolation PHP source
     */
    private function variable($id, $filters, array $attrs, $escape, $level)
    {
        $method  = $this->getFindMethod($id);
        $id      = ($method !== 'last') ? var_export($id, true) : '';
        $value   = $escape ? $this->getEscape() : '$value';
        $filters = $this->getFilters($filters, $level);

        $args = count($attrs) ? ', ' . $this->passAttrs($attrs, $level + 1) : '';

        return sprintf($this->prepare(self::VARIABLE, $level), $method, $id, $args, $filters, $this->flushIndent(), $value);
    }

    const FILTER = '
        $filter = $context->%s(%s);
        if (!(%s)) {
            throw new Mustache_Exception_UnknownFilterException(%s);
        }
        $value = call_user_func($filter, $value);%s
    ';

    /**
     * Generate Mustache Template variable filtering PHP source.
     *
     * @param string[] $filters Array of filters
     * @param int      $level
     *
     * @return string Generated filter PHP source
     */
    private function getFilters(array $filters, $level)
    {
        if (empty($filters)) {
            return '';
        }

        $name     = array_shift($filters);
        $method   = $this->getFindMethod($name);
        $filter   = ($method !== 'last') ? var_export($name, true) : '';
        $callable = $this->getCallable('$filter');
        $msg      = var_export($name, true);

        return sprintf($this->prepare(self::FILTER, $level), $method, $filter, $callable, $msg, $this->getFilters($filters, $level));
    }

    const LINE = '$buffer .= "\n";';
    const TEXT = '$buffer .= %s%s;';

    /**
     * Generate Mustache Template output Buffer call PHP source.
     *
     * @param string $text
     * @param int    $level
     *
     * @return string Generated output Buffer call PHP source
     */
    private function text($text, $level)
    {
        $indentNextLine = (substr($text, -1) === "\n");
        $code = sprintf($this->prepare(self::TEXT, $level), $this->flushIndent(), var_export($text, true));
        $this->indentNextLine = $indentNextLine;

        return $code;
    }

    /**
     * Prepare PHP source code snippet for output.
     *
     * @param string $text
     * @param int    $bonus          Additional indent level (default: 0)
     * @param bool   $prependNewline Prepend a newline to the snippet? (default: true)
     * @param bool   $appendNewline  Append a newline to the snippet? (default: false)
     *
     * @return string PHP source code snippet
     */
    private function prepare($text, $bonus = 0, $prependNewline = true, $appendNewline = false)
    {
        $text = ($prependNewline ? "\n" : '') . trim($text);
        if ($prependNewline) {
            $bonus++;
        }
        if ($appendNewline) {
            $text .= "\n";
        }

        return preg_replace("/\n( {8})?/", "\n" . str_repeat(' ', $bonus * 4), $text);
    }

    const DEFAULT_ESCAPE = 'htmlspecialchars(%s, %s, %s)';
    const CUSTOM_ESCAPE  = 'call_user_func($this->mustache->getEscape(), %s)';

    /**
     * Get the current escaper.
     *
     * @param string $value (default: '$value')
     *
     * @return string Either a custom callback, or an inline call to `htmlspecialchars`
     */
    private function getEscape($value = '$value')
    {
        if ($this->customEscape) {
            return sprintf(self::CUSTOM_ESCAPE, $value);
        }

        return sprintf(self::DEFAULT_ESCAPE, $value, var_export($this->entityFlags, true), var_export($this->charset, true));
    }

    /**
     * Select the appropriate Context `find` method for a given $id.
     *
     * The return value will be one of `find`, `findDot` or `last`.
     *
     * @see Mustache_Context::find
     * @see Mustache_Context::findDot
     * @see Mustache_Context::last
     *
     * @param string $id Variable name
     *
     * @return string `find` method name
     */
    private function getFindMethod($id)
    {
        if ($id === '.') {
            return 'last';
        }

        if (isset($this->pragmas[Mustache_Engine::PRAGMA_ANCHORED_DOT]) && $this->pragmas[Mustache_Engine::PRAGMA_ANCHORED_DOT]) {
            if (substr($id, 0, 1) === '.') {
                return 'findAnchoredDot';
            }
        }

        if (strpos($id, '.') === false) {
            return 'find';
        }

        return 'findDot';
    }

    const IS_CALLABLE        = '!is_string(%s) && is_callable(%s)';
    const STRICT_IS_CALLABLE = 'is_object(%s) && is_callable(%s)';

    /**
     * Helper function to compile strict vs lax "is callable" logic.
     *
     * @param string $variable (default: '$value')
     *
     * @return string "is callable" logic
     */
    private function getCallable($variable = '$value')
    {
        $tpl = $this->strictCallables ? self::STRICT_IS_CALLABLE : self::IS_CALLABLE;

        return sprintf($tpl, $variable, $variable);
    }

    const LINE_INDENT = '$indent . ';

    /**
     * Get the current $indent prefix to write to the buffer.
     *
     * @return string "$indent . " or ""
     */
    private function flushIndent()
    {
        if (!$this->indentNextLine) {
            return '';
        }

        $this->indentNextLine = false;

        return self::LINE_INDENT;
    }

    const ATTR_SCOPE = '
        $context->pushAttrContext([%s
        ]);%s
        $context->popAttrContext();
    ';
    const ATTR_BINDING = '"%s" => %s,';

    /**
     * Scope a block tag (or a partial) with attributes.
     *
     * @param array  $attrs The attributes to resolve.
     * @param string $body  The code to wrap.
     * @param int    $level
     *
     * @return string
     */
    private function wrapAttrContext($attrs, $body, $level)
    {
        $text = '';
        foreach ($attrs as $attr) {
            $text .= sprintf(
                $this->prepare(self::ATTR_BINDING, $level + 1),
                $attr[Mustache_Tokenizer::NAME],
                $this->resolveAttrValue($attr[Mustache_Tokenizer::TYPE], $attr[Mustache_Tokenizer::VALUE])
            );
        }

        return sprintf($this->prepare(self::ATTR_SCOPE, $level), $text, $body);
    }

    /**
     * Prepare attributes to send to a callable.
     *
     * @param array $attrs
     * @param int   $level
     *
     * @return string
     */
    private function passAttrs($attrs, $level)
    {
        $text = '';
        foreach ($attrs as $attr) {
            $text .= sprintf(
                $this->prepare(self::ATTR_BINDING, $level + 1),
                $attr[Mustache_Tokenizer::NAME],
                $this->resolveAttrValue($attr[Mustache_Tokenizer::TYPE], $attr[Mustache_Tokenizer::VALUE])
            );
        }

        return sprintf('[%s]', $text);
    }

    const ATTR_VALUE = '$this->resolveValue($context->%s("%s"), $context)';

    /**
     * Resolve the value of an attribute. It'll either be a variable (like `foo.bar`)
     * or a string/number.
     *
     * @param string $type
     * @param string $value
     *
     * @return string
     */
    private function resolveAttrValue($type, $value)
    {
        switch ($type) {
            case Mustache_Tokenizer::T_ESCAPED:
                return sprintf(self::ATTR_VALUE, $this->getFindMethod($value), $value);
            case Mustache_Tokenizer::T_TEXT:
            default:
                return sprintf('"%s"', $value);
        }
    }
}
