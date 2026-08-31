<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2026 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mustache;

use Mustache\Exception\InvalidArgumentException;
use Mustache\Exception\SyntaxException;

/**
 * Mustache Compiler class.
 *
 * This class is responsible for turning a Mustache token parse tree into normal PHP source code.
 */
class Compiler
{
    private $pragmas;
    private $defaultPragmas = [];
    private $sections;
    private $blocks;
    private $blockNames;
    private $hasParents;
    private $partialCacheScopes;
    private $contextFrameScopes;
    private $source;
    private $options;
    private $indentNextLine;
    private $blockContentDepth;

    // Optional Mustache specs
    private $lambdas = true;

    /**
     * Compile a Mustache token parse tree into PHP source code.
     *
     * @throws InvalidArgumentException if the FILTERS pragma is set but lambdas are not enabled
     *
     * The canonical signature is:
     *
     *     compile($source, $tree, $name, CompileOptions $options)
     *
     * The remaining arguments preserve the pre-3.2 positional signature:
     *
     *     compile($source, $tree, $name, $customEscape, $charset, $strictCallables, $entityFlags)
     *
     * @param string              $source                Mustache Template source code
     * @param array               $tree                  Parse tree of Mustache tokens
     * @param string              $name                  Mustache Template class name
     * @param CompileOptions|bool $optionsOrCustomEscape Compile options, or legacy custom escape flag (default: false)
     * @param string              $charset               Legacy charset argument (default: 'UTF-8')
     * @param bool                $strictCallables       Legacy strict callables argument (default: false)
     * @param int                 $entityFlags           Legacy entity flags argument (default: ENT_COMPAT)
     *
     * @return string Generated PHP source code
     */
    public function compile($source, array $tree, $name, $optionsOrCustomEscape = false, $charset = 'UTF-8', $strictCallables = false, $entityFlags = ENT_COMPAT)
    {
        $this->validateClassName($name);

        $this->pragmas            = $this->defaultPragmas;
        $this->sections           = [];
        $this->blocks             = [];
        $this->blockNames         = [];
        $this->hasParents         = false;
        $this->partialCacheScopes = [];
        $this->contextFrameScopes = [];
        $this->source             = $source;
        $this->options            = $optionsOrCustomEscape instanceof CompileOptions
            ? $optionsOrCustomEscape
            : new CompileOptions([
                'custom_escape'    => $optionsOrCustomEscape,
                'charset'          => $charset,
                'strict_callables' => $strictCallables,
                'entity_flags'     => $entityFlags,
            ]);
        $this->indentNextLine     = true;
        $this->blockContentDepth  = 0;

        $code = $this->writeCode($tree, $name);

        if (isset($this->pragmas[Engine::PRAGMA_FILTERS]) && !$this->lambdas) {
            throw new InvalidArgumentException('The FILTERS pragma requires lambda support');
        }

        return $code;
    }

    /**
     * @throws InvalidArgumentException if $name is not a valid unqualified PHP class name
     */
    private function validateClassName($name)
    {
        if (!is_string($name) || !preg_match('/^[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*$/', $name)) {
            throw new InvalidArgumentException('Compiler class name must be a valid PHP class name');
        }
    }

    /**
     * Disable optional Mustache specs.
     *
     * @internal Users should set options in Mustache\Engine, not here :)
     *
     * @param bool[] $options
     */
    public function setOptions(array $options)
    {
        if (isset($options['lambdas'])) {
            $this->lambdas = $options['lambdas'] !== false;
        }
    }

    /**
     * Enable pragmas across all templates, regardless of the presence of pragma
     * tags in the individual templates.
     *
     * @internal Users should set global pragmas in \Mustache\Engine, not here :)
     *
     * @param string[] $pragmas
     */
    public function setPragmas(array $pragmas)
    {
        $this->pragmas = [];
        foreach ($pragmas as $pragma) {
            $this->pragmas[$pragma] = true;
        }
        $this->defaultPragmas = $this->pragmas;
    }

    /**
     * Helper function for walking the Mustache token parse tree.
     *
     * @throws SyntaxException upon encountering unknown token types
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
            $debug = $this->options->debugRendering && $this->isDebuggableNode($node);
            if ($debug) {
                $code .= $this->debugEnter($node, $level);
            }

            switch ($node[Tokenizer::TYPE]) {
                case Tokenizer::T_PRAGMA:
                    $this->pragmas[$node[Tokenizer::NAME]] = true;
                    break;

                case Tokenizer::T_SECTION:
                    $code .= $this->section(
                        $node[Tokenizer::NODES],
                        $node[Tokenizer::NAME],
                        isset($node[Tokenizer::FILTERS]) ? $node[Tokenizer::FILTERS] : [],
                        $node[Tokenizer::INDEX],
                        $node[Tokenizer::END],
                        $node[Tokenizer::OTAG],
                        $node[Tokenizer::CTAG],
                        $level
                    );
                    break;

                case Tokenizer::T_INVERTED:
                    $code .= $this->invertedSection(
                        $node[Tokenizer::NODES],
                        $node[Tokenizer::NAME],
                        isset($node[Tokenizer::FILTERS]) ? $node[Tokenizer::FILTERS] : [],
                        $level
                    );
                    break;

                case Tokenizer::T_PARTIAL:
                    $code .= $this->partial(
                        $node[Tokenizer::NAME],
                        isset($node[Tokenizer::DYNAMIC]) ? $node[Tokenizer::DYNAMIC] : false,
                        isset($node[Tokenizer::INDENT]) ? $node[Tokenizer::INDENT] : '',
                        $level
                    );
                    break;

                case Tokenizer::T_PARENT:
                    $code .= $this->parent(
                        $node[Tokenizer::NAME],
                        isset($node[Tokenizer::DYNAMIC]) ? $node[Tokenizer::DYNAMIC] : false,
                        isset($node[Tokenizer::INDENT]) ? $node[Tokenizer::INDENT] : '',
                        $node[Tokenizer::NODES],
                        $level
                    );
                    break;

                case Tokenizer::T_BLOCK_ARG:
                    $code .= $this->blockArg(
                        $node[Tokenizer::NODES],
                        $node[Tokenizer::NAME],
                        $node[Tokenizer::INDEX],
                        $node[Tokenizer::END],
                        $node[Tokenizer::OTAG],
                        $node[Tokenizer::CTAG],
                        isset($node[Tokenizer::INDENT]) ? $node[Tokenizer::INDENT] : '',
                        isset($node[Tokenizer::STANDALONE]),
                        $level
                    );
                    break;

                case Tokenizer::T_BLOCK_VAR:
                    $code .= $this->blockVar(
                        $node[Tokenizer::NODES],
                        $node[Tokenizer::NAME],
                        $node[Tokenizer::INDEX],
                        $node[Tokenizer::END],
                        $node[Tokenizer::OTAG],
                        $node[Tokenizer::CTAG],
                        isset($node[Tokenizer::INDENT]) ? $node[Tokenizer::INDENT] : '',
                        isset($node[Tokenizer::STANDALONE]),
                        $level
                    );
                    break;

                case Tokenizer::T_COMMENT:
                    break;

                case Tokenizer::T_ESCAPED:
                case Tokenizer::T_UNESCAPED:
                case Tokenizer::T_UNESCAPED_2:
                    $code .= $this->variable(
                        $node[Tokenizer::NAME],
                        isset($node[Tokenizer::FILTERS]) ? $node[Tokenizer::FILTERS] : [],
                        $node[Tokenizer::TYPE] === Tokenizer::T_ESCAPED,
                        $level
                    );
                    break;

                case Tokenizer::T_TEXT:
                    $code .= $this->text($node[Tokenizer::VALUE], $level);
                    break;

                default:
                    throw new SyntaxException(sprintf('Unknown token type: %s', $node[Tokenizer::TYPE]), $node);
            }

            if ($debug) {
                $code .= $this->debugLeave($level);
            }
        }

        return $code;
    }

    /**
     * Whether a token should be tracked in the rendering debug stack.
     *
     * @return bool
     */
    private function isDebuggableNode(array $node)
    {
        switch ($node[Tokenizer::TYPE]) {
            case Tokenizer::T_SECTION:
            case Tokenizer::T_INVERTED:
            case Tokenizer::T_PARTIAL:
            case Tokenizer::T_PARENT:
            case Tokenizer::T_BLOCK_VAR:
            case Tokenizer::T_ESCAPED:
            case Tokenizer::T_UNESCAPED:
            case Tokenizer::T_UNESCAPED_2:
                return true;

            default:
                return false;
        }
    }

    const DEBUG_ENTER = '$context->pushRenderingFrame(%s);';
    const DEBUG_LEAVE = '$context->popRenderingFrame();';

    /**
     * Generate rendering debug stack enter source.
     *
     * @return string
     */
    private function debugEnter(array $node, $level)
    {
        return sprintf($this->prepare(self::DEBUG_ENTER, $level), $this->exportDebugFrame($this->getDebugFrame($node)));
    }

    /**
     * Generate rendering debug stack leave source.
     *
     * @return string
     */
    private function debugLeave($level)
    {
        return $this->prepare(self::DEBUG_LEAVE, $level);
    }

    /**
     * Build a Mustache debug frame for a token.
     *
     * @return array
     */
    private function getDebugFrame(array $node)
    {
        $frame = [
            'type' => Tokenizer::getTagName($node[Tokenizer::TYPE]),
        ];

        if (isset($node[Tokenizer::NAME])) {
            $frame['name'] = $node[Tokenizer::NAME];
        }

        if (isset($node[Tokenizer::LINE])) {
            $frame['line'] = $node[Tokenizer::LINE];
        }

        if (isset($node[Tokenizer::DYNAMIC]) && $node[Tokenizer::DYNAMIC]) {
            $frame['dynamic'] = true;
        }

        if ($this->options->sourceName !== null) {
            $frame['source'] = $this->options->sourceName;
        }

        return $frame;
    }

    /**
     * Export a debug frame as compact PHP source.
     *
     * @return string
     */
    private function exportDebugFrame(array $frame)
    {
        $chunks = [];

        foreach ($frame as $key => $value) {
            $chunks[] = var_export($key, true) . ' => ' . var_export($value, true);
        }

        return 'array(' . implode(', ', $chunks) . ')';
    }

    const KLASS = '<?php

        class %s extends \\Mustache\\Template
        {%s
            public function renderInternal(\\Mustache\\Context $context, $indent = \'\')
            {
                $this->lambdaHelper = new \\Mustache\\LambdaHelper($this->mustache, $context);
                $buffer = \'\';
        %s

                return $buffer;
            }
        %s
        %s
        }';

    const KLASS_NO_LAMBDAS = '<?php

        class %s extends \\Mustache\\Template
        {%s
            public function renderInternal(\\Mustache\\Context $context, $indent = \'\')
            {
                $buffer = \'\';
        %s

                return $buffer;
            }
        }';

    const LAMBDA_HELPER = 'private $lambdaHelper;';

    const STRICT_CALLABLE = 'protected $strictCallables = true;';

    const STRICT_TAGS = 'protected $strictTags = %d;';

    const BLOCK_NAMES = 'protected $blockNames = %s;';

    const HAS_PARENTS = 'protected $hasParents = true;';

    const NO_LAMBDAS = 'protected $lambdas = false;';

    const SOURCE = 'private static $source = %s;';

    /**
     * Generate Mustache Template class PHP source.
     *
     * @param array  $tree Parse tree of Mustache tokens
     * @param string $name Mustache Template class name
     *
     * @return string Generated PHP source code
     */
    private function writeCode(array $tree, $name)
    {
        $code     = $this->walkWithContextFrame($tree);
        $sections = implode("\n", $this->sections);
        $blocks   = implode("\n", $this->blocks);
        $klass    = empty($this->sections) && empty($this->blocks) ? self::KLASS_NO_LAMBDAS : self::KLASS;

        return sprintf($this->prepare($klass, 0, false, true), $name, $this->getClassProperties(), $code, $sections, $blocks);
    }

    /**
     * Generate compiled template property declarations.
     *
     * @return string
     */
    private function getClassProperties()
    {
        $properties = [];

        if (!empty($this->sections) || !empty($this->blocks)) {
            $properties[] = $this->prepare(self::LAMBDA_HELPER);
        }

        if ($this->options->strictCallables) {
            $properties[] = $this->prepare(self::STRICT_CALLABLE);
        }

        if ($this->hasStrictLookups()) {
            $properties[] = sprintf($this->prepare(self::STRICT_TAGS), $this->options->strictTags);
        }

        if (!empty($this->blockNames)) {
            $properties[] = sprintf($this->prepare(self::BLOCK_NAMES), var_export($this->blockNames, true));
        }

        if ($this->hasParents) {
            $properties[] = $this->prepare(self::HAS_PARENTS);
        }

        if (!$this->lambdas) {
            $properties[] = $this->prepare(self::NO_LAMBDAS);
        }

        if ($this->lambdas && !empty($this->sections)) {
            // Substitute the source value after prepare() so its newlines aren't re-indented (which would break section source offsets).
            $properties[] = sprintf($this->prepare(self::SOURCE), var_export($this->source, true));
        }

        return empty($properties) ? '' : implode('', $properties) . "\n";
    }

    const BLOCK_VAR = '
        $blockFunction = $context->findInBlock(%s);
        if (is_callable($blockFunction)) {
            $buffer .= call_user_func($blockFunction, $context, %s);
        %s}
    ';

    const BLOCK_VAR_ELSE = '} else {%s';

    /**
     * Generate Mustache Template inheritance block variable PHP source.
     *
     * @param array  $nodes      Array of child tokens
     * @param string $id         Section name
     * @param int    $start      Section start offset
     * @param int    $end        Section end offset
     * @param string $otag       Current Mustache opening tag
     * @param string $ctag       Current Mustache closing tag
     * @param string $indent
     * @param bool   $standalone
     * @param int    $level
     *
     * @return string Generated PHP source code
     */
    private function blockVar(array $nodes, $id, $start, $end, $otag, $ctag, $indent, $standalone, $level)
    {
        if (($this->options->strictTags & Engine::STRICT_EXTRA_BLOCKS) !== 0) {
            $this->blockNames[$id] = true;
        }
        $id = var_export($id, true);
        $indent = $this->getBlockIndentExpression($nodes, $indent, $standalone);

        $else = $this->walk($nodes, $level);
        if ($else !== '') {
            $else = sprintf($this->prepare(self::BLOCK_VAR_ELSE, $level + 1, false, true), $else);
        }

        return sprintf($this->prepare(self::BLOCK_VAR, $level), $id, $indent, $else);
    }

    const BLOCK_ARG = '%s => [$this, \'block%s\'],';

    /**
     * Generate Mustache Template inheritance block argument PHP source.
     *
     * @param array  $nodes      Array of child tokens
     * @param string $id         Section name
     * @param int    $start      Section start offset
     * @param int    $end        Section end offset
     * @param string $otag       Current Mustache opening tag
     * @param string $ctag       Current Mustache closing tag
     * @param string $indent
     * @param bool   $standalone
     * @param int    $level
     *
     * @return string Generated PHP source code
     */
    private function blockArg($nodes, $id, $start, $end, $otag, $ctag, $indent, $standalone, $level)
    {
        if ($standalone) {
            $nodes = $this->dedentNodes($nodes, $this->getCommonIndent($nodes));
        }

        $key = $this->block($nodes);
        $id = var_export($id, true);

        return sprintf($this->prepare(self::BLOCK_ARG, $level), $id, $key);
    }

    const BLOCK_FUNCTION = '
        public function block%s($context, $indent = \'\')
        {
            $buffer = \'\';%s%s

            return $buffer;
        }
    ';

    /**
     * Generate Mustache Template inheritance block function PHP source.
     *
     * Block override content is compiled into its own standalone method, so
     * it needs its own static partial cache scope -- just like section().
     * Without this, a partial referenced directly inside a block override
     * that is itself nested inside an active section (or another block)
     * would register its cache variable in the *enclosing* scope, while the
     * code using that variable lives in this method, leaving it undefined
     * at render time.
     *
     * @param array $nodes Array of child tokens
     *
     * @return string key of new block function
     */
    private function block(array $nodes)
    {
        $indentNextLine = $this->indentNextLine;
        $this->indentNextLine = true;
        $this->blockContentDepth++;
        $this->beginPartialCacheScope();
        $code = $this->walkWithContextFrame($nodes);
        $partialCacheInits = $this->getPartialCacheInitializers();
        $this->endPartialCacheScope();
        $this->blockContentDepth--;
        $this->indentNextLine = $indentNextLine;
        $key = ucfirst(md5($partialCacheInits . "\n" . $code));

        if (!isset($this->blocks[$key])) {
            $this->blocks[$key] = sprintf($this->prepare(self::BLOCK_FUNCTION, 0), $key, $partialCacheInits, $code);
        }

        return $key;
    }

    /**
     * Get the PHP expression for indentation to apply to an overridden block.
     *
     * Standalone blocks inherit the parent partial's runtime indent and fall
     * back to the block's intrinsic indentation when no explicit indent is
     * present on the tag line.
     *
     * @param array  $nodes      Block default content
     * @param string $indent     Explicit indentation from the block tag line
     * @param bool   $standalone Whether the block tag was standalone
     *
     * @return string PHP indentation expression
     */
    private function getBlockIndentExpression(array $nodes, $indent, $standalone)
    {
        if ($indent === false) {
            $indent = '';
        }

        if ($standalone && $indent === '') {
            $indent = $this->getCommonIndent($nodes);
        }

        $indent = var_export($indent, true);

        if ($standalone) {
            return '$indent . ' . $indent;
        }

        return $indent;
    }

    /**
     * Remove a common indentation prefix from a block argument's content.
     *
     * @param array  $nodes  Block content
     * @param string $indent Indentation to remove
     *
     * @return array Dedented block content
     */
    private function dedentNodes(array $nodes, $indent)
    {
        if ($indent === '') {
            return $nodes;
        }

        $indentLen = strlen($indent);
        $atLineStart = true;
        foreach ($nodes as &$node) {
            if ($node[Tokenizer::TYPE] === Tokenizer::T_TEXT) {
                $node[Tokenizer::VALUE] = $this->dedentText($node[Tokenizer::VALUE], $indent, $atLineStart);
                continue;
            }

            if ($atLineStart && isset($node[Tokenizer::INDENT]) && substr($node[Tokenizer::INDENT], 0, $indentLen) === $indent) {
                $node[Tokenizer::INDENT] = substr($node[Tokenizer::INDENT], $indentLen);
            }

            if (isset($node[Tokenizer::NODES])) {
                $node[Tokenizer::NODES] = $this->dedentNodes($node[Tokenizer::NODES], $indent);
            }

            $atLineStart = false;
        }
        unset($node);

        return $nodes;
    }

    /**
     * Remove indentation from text only when the text starts a rendered line.
     *
     * @param string $text
     * @param string $indent
     * @param bool   $atLineStart Whether this text starts at the beginning of a rendered line
     *
     * @return string Dedented text
     */
    private function dedentText($text, $indent, &$atLineStart)
    {
        $result = '';
        $offset = 0;
        $len = strlen($text);
        $indentLen = strlen($indent);

        while ($offset < $len) {
            if ($atLineStart && $indentLen > 0 && substr($text, $offset, $indentLen) === $indent) {
                $offset += $indentLen;
            }

            $newline = strpos($text, "\n", $offset);
            if ($newline === false) {
                $chunk = substr($text, $offset);
                $result .= $chunk;
                if ($chunk !== '') {
                    $atLineStart = false;
                }
                break;
            }

            $result .= substr($text, $offset, $newline - $offset + 1);
            $atLineStart = true;
            $offset = $newline + 1;
        }

        return $result;
    }

    /**
     * Find the common leading indentation of non-empty text lines.
     *
     * @param array $nodes Block content
     *
     * @return string Common indentation
     */
    private function getCommonIndent(array $nodes)
    {
        $indent = null;
        $atLineStart = true;
        $lineIndent = '';

        foreach ($nodes as $node) {
            if ($indent === '') {
                break;
            }

            if ($node[Tokenizer::TYPE] === Tokenizer::T_TEXT) {
                $this->measureTextIndent($node[Tokenizer::VALUE], $indent, $atLineStart, $lineIndent);
                continue;
            }

            if ($atLineStart) {
                if ($lineIndent === '' && isset($node[Tokenizer::INDENT])) {
                    $lineIndent = $node[Tokenizer::INDENT];
                }
                $this->measureLineIndent($lineIndent, $indent);
                $lineIndent = '';
            }

            $atLineStart = false;
        }

        return $indent === null ? '' : $indent;
    }

    /**
     * Measure common indentation across text content.
     *
     * @param string      $text
     * @param string|null $indent
     * @param bool        $atLineStart
     * @param string      $lineIndent
     */
    private function measureTextIndent($text, &$indent, &$atLineStart, &$lineIndent)
    {
        if ($indent === '') {
            return;
        }

        $len = strlen($text);
        for ($i = 0; $i < $len; $i++) {
            $char = $text[$i];

            if ($atLineStart) {
                if ($char === ' ' || $char === "\t") {
                    $lineIndent .= $char;
                    continue;
                }

                if ($char === "\n") {
                    $lineIndent = '';
                    continue;
                }

                $this->measureLineIndent($lineIndent, $indent);
                $lineIndent = '';
                $atLineStart = false;
            }

            if ($char === "\n") {
                $atLineStart = true;
                $lineIndent = '';
            }
        }
    }

    /**
     * Merge a line indentation value into the common indent.
     *
     * @param string      $lineIndent
     * @param string|null $indent
     */
    private function measureLineIndent($lineIndent, &$indent)
    {
        if ($indent === null) {
            $indent = $lineIndent;
        } else {
            $indent = $this->commonPrefix($indent, $lineIndent);
        }
    }

    /**
     * Find the common prefix shared by two strings.
     *
     * @param string $left
     * @param string $right
     *
     * @return string Common prefix
     */
    private function commonPrefix($left, $right)
    {
        $len = min(strlen($left), strlen($right));
        for ($i = 0; $i < $len; $i++) {
            if ($left[$i] !== $right[$i]) {
                return substr($left, 0, $i);
            }
        }

        return substr($left, 0, $len);
    }

    const SECTION_CALL = '
        $value = %s;%s
        $buffer .= $this->section%s($context, $indent, $value);
    ';

    const SECTION = '
        private function section%s(\\Mustache\\Context $context, $indent, $value)
        {
            $buffer = \'\';

            if (%s) {
                $source = substr(self::$source, %s, %s);
                $value = call_user_func($value, $source, %s);

                if ($value instanceof \\Mustache\\RenderedString) {
                    return $value->getValue();
                }

                if (is_string($value)) {
                    if (strpos($value, %s) === false) {
                        return $value;
                    }

                    return $this->mustache
                        ->loadLambda($value%s)
                        ->renderInternal($context);
                }
            }

            if (!empty($value)) {
                $values = $this->isIterable($value) ? $value : [$value];
                %s
                foreach ($values as $value) {
                    $context->push($value);%s
                    %s
                    $context->pop();
                }
            }

            return $buffer;
        }
    ';

    const SECTION_NO_LAMBDAS = '
        private function section%s(\\Mustache\\Context $context, $indent, $value)
        {
            $buffer = \'\';

            if (!empty($value)) {
                $values = $this->isIterable($value) ? $value : [$value];
                %s
                foreach ($values as $value) {
                    $context->push($value);%s
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
     * @param int      $start   Section start offset
     * @param int      $end     Section end offset
     * @param string   $otag    Current Mustache opening tag
     * @param string   $ctag    Current Mustache closing tag
     * @param int      $level
     *
     * @return string Generated section PHP source code
     */
    private function section(array $nodes, $id, $filters, $start, $end, $otag, $ctag, $level)
    {
        $source   = substr($this->source, $start, $end - $start);
        $length   = $end - $start;
        $callable = $this->getCallable();

        if ($otag !== '{{' || $ctag !== '}}') {
            $delimTag = var_export(sprintf('{{= %s %s =}}', $otag, $ctag), true);
            $helper = sprintf('$this->lambdaHelper->withDelimiters(%s)', $delimTag);
            $delims = ', ' . $delimTag;
            $otag = var_export($otag, true);
        } else {
            $helper = '$this->lambdaHelper';
            $delims = '';
            $otag = '\'{{\'';
        }

        $key = ucfirst(md5($delims . "\n" . $source));

        if (!isset($this->sections[$key])) {
            $useContextFrame = $this->hasMultipleContextFrameLookups($nodes);

            $this->beginPartialCacheScope();
            $this->beginContextFrameScope($useContextFrame);
            $sectionBody = $this->walk($nodes, 2);
            $this->endContextFrameScope();
            $contextFrameHoist = $useContextFrame ? $this->getContextFrameHoist(3) : '';
            $partialCacheInits = $this->getPartialCacheInitializers();
            $this->endPartialCacheScope();

            if ($this->lambdas) {
                $this->sections[$key] = sprintf($this->prepare(self::SECTION), $key, $callable, var_export($start, true), var_export($length, true), $helper, $otag, $delims, $partialCacheInits, $contextFrameHoist, $sectionBody);
            } else {
                $this->sections[$key] = sprintf($this->prepare(self::SECTION_NO_LAMBDAS), $key, $partialCacheInits, $contextFrameHoist, $sectionBody);
            }
        }

        $value = $this->getFindValue($id, Engine::STRICT_SECTIONS);
        $filters = $this->getFilters($filters, $level);

        return sprintf($this->prepare(self::SECTION_CALL, $level), $value, $filters, $key);
    }

    const INVERTED_SECTION = '
        $value = %s;%s
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
     * @param int      $level
     *
     * @return string Generated inverted section PHP source code
     */
    private function invertedSection(array $nodes, $id, $filters, $level)
    {
        $value = $this->getFindValue($id, Engine::STRICT_SECTIONS);
        $filters = $this->getFilters($filters, $level);

        return sprintf($this->prepare(self::INVERTED_SECTION, $level), $value, $filters, $this->walk($nodes, $level));
    }

    const DYNAMIC_NAME = '$this->resolveValue(%s, $context)';

    /**
     * Generate Mustache Template dynamic name resolution PHP source.
     *
     * @param string $id      Tag name
     * @param bool   $dynamic True if the name is dynamic
     *
     * @return string Dynamic name resolution PHP source code
     */
    private function resolveDynamicName($id, $dynamic, $strictTag)
    {
        if (!$dynamic) {
            return var_export($id, true);
        }

        // TODO: filters?

        $value = $this->getFindValue($id, $strictTag);

        if (!$this->lambdas) {
            return $value;
        }

        return sprintf(self::DYNAMIC_NAME, $value);
    }

    const PARTIAL_INDENT = ', $indent . %s';
    const PARTIAL = '
        if ($partial = $this->mustache->loadPartial(%s%s)) {
            $buffer .= $partial->renderInternal($context%s);
        }
    ';
    const PARTIAL_CACHE_INIT = '$%s = false;';
    const PARTIAL_CACHED = '
        if ($%s === false) {
            $%s = $this->mustache->loadPartial(%s%s);
        }
        if ($%s) {
            $buffer .= $%s->renderInternal($context%s);
        }
    ';

    /**
     * Generate Mustache Template partial call PHP source.
     *
     * @param string $id      Partial name
     * @param bool   $dynamic Partial name is dynamic
     * @param string $indent  Whitespace indent to apply to partial
     * @param int    $level
     *
     * @return string Generated partial call PHP source code
     */
    private function partial($id, $dynamic, $indent, $level)
    {
        $strictArg = ($this->options->strictTags & Engine::STRICT_PARTIALS) !== 0 ? ', true' : '';

        if ($indent !== '') {
            $indentParam = sprintf(self::PARTIAL_INDENT, var_export($indent, true));
        } else {
            $indentParam = '';
        }

        if (!$dynamic && $this->isCachingPartials()) {
            $partial = $this->cachePartial('partial', $id);

            return sprintf(
                $this->prepare(self::PARTIAL_CACHED, $level),
                $partial,
                $partial,
                var_export($id, true),
                $strictArg,
                $partial,
                $partial,
                $indentParam
            );
        }

        return sprintf(
            $this->prepare(self::PARTIAL, $level),
            $this->resolveDynamicName($id, $dynamic, Engine::STRICT_PARTIALS),
            $strictArg,
            $indentParam
        );
    }

    const PARENT = '
        if ($parent = $this->mustache->loadPartial(%s%s)) {
            $context->pushBlockContext([%s
            ]);%s
            $buffer .= $parent->renderInternal($context%s);
            $context->popBlockContext();
        }
    ';

    const PARENT_NO_CONTEXT = '
        if ($parent = $this->mustache->loadPartial(%s%s)) {%s
            $buffer .= $parent->renderInternal($context%s);
        }
    ';

    const PARENT_SCOPED = '
        if ($parent = $this->mustache->loadPartial(%s%s)) {
            $context->pushBlockContextScope();
            $context->pushBlockContext([%s
            ]);%s
            $buffer .= $parent->renderInternal($context%s);
            $context->popBlockContextScope();
        }
    ';

    const PARENT_SCOPED_NO_CONTEXT = '
        if ($parent = $this->mustache->loadPartial(%s%s)) {
            $context->pushBlockContextScope();%s
            $buffer .= $parent->renderInternal($context%s);
            $context->popBlockContextScope();
        }
    ';

    const PARENT_CACHED = '
        if ($%s === false) {
            $%s = $this->mustache->loadPartial(%s%s);
        }
        if ($%s) {
            $context->pushBlockContext([%s
            ]);%s
            $buffer .= $%s->renderInternal($context%s);
            $context->popBlockContext();
        }
    ';

    const PARENT_CACHED_NO_CONTEXT = '
        if ($%s === false) {
            $%s = $this->mustache->loadPartial(%s%s);
        }
        if ($%s) {%s
            $buffer .= $%s->renderInternal($context%s);
        }
    ';

    const PARENT_CACHED_SCOPED = '
        if ($%s === false) {
            $%s = $this->mustache->loadPartial(%s%s);
        }
        if ($%s) {
            $context->pushBlockContextScope();
            $context->pushBlockContext([%s
            ]);%s
            $buffer .= $%s->renderInternal($context%s);
            $context->popBlockContextScope();
        }
    ';

    const PARENT_CACHED_SCOPED_NO_CONTEXT = '
        if ($%s === false) {
            $%s = $this->mustache->loadPartial(%s%s);
        }
        if ($%s) {
            $context->pushBlockContextScope();%s
            $buffer .= $%s->renderInternal($context%s);
            $context->popBlockContextScope();
        }
    ';

    const ASSERT_BLOCK_CONTEXT = '%s->assertBlockContext($context);';

    /**
     * Generate Mustache Template inheritance parent call PHP source.
     *
     * @param string $id       Parent tag name
     * @param bool   $dynamic  Tag name is dynamic
     * @param string $indent   Whitespace indent to apply to parent
     * @param array  $children Child nodes
     * @param int    $level
     *
     * @return string Generated PHP source code
     */
    private function parent($id, $dynamic, $indent, array $children, $level)
    {
        $realChildren = array_filter($children, [self::class, 'onlyBlockArgs']);
        if (($this->options->strictTags & Engine::STRICT_EXTRA_BLOCKS) !== 0) {
            $this->hasParents = true;
        }

        $partialName = $this->resolveDynamicName($id, $dynamic, Engine::STRICT_PARENTS);
        $indentParam = $indent !== '' ? sprintf(self::PARTIAL_INDENT, var_export($indent, true)) : ', $indent';
        $strictArg = ($this->options->strictTags & Engine::STRICT_PARENTS) !== 0 ? ', true' : '';

        // Nested inside a block argument: emit a scoped parent so its block
        // contexts don't leak out to the surrounding parent's block lookups.
        $scoped = $this->blockContentDepth > 0;

        if (!$dynamic && $this->isCachingPartials()) {
            $parent = $this->cachePartial('parent', $id);

            if (empty($realChildren)) {
                return sprintf(
                    $this->prepare($scoped ? self::PARENT_CACHED_SCOPED_NO_CONTEXT : self::PARENT_CACHED_NO_CONTEXT, $level),
                    $parent,
                    $parent,
                    var_export($id, true),
                    $strictArg,
                    $parent,
                    $this->getAssertBlockContext('$' . $parent, $level + 1),
                    $parent,
                    $indentParam
                );
            }

            return sprintf(
                $this->prepare($scoped ? self::PARENT_CACHED_SCOPED : self::PARENT_CACHED, $level),
                $parent,
                $parent,
                var_export($id, true),
                $strictArg,
                $parent,
                $this->walk($realChildren, $level + 1),
                $this->getAssertBlockContext('$' . $parent, $level + 1),
                $parent,
                $indentParam
            );
        }

        if (empty($realChildren)) {
            return sprintf(
                $this->prepare($scoped ? self::PARENT_SCOPED_NO_CONTEXT : self::PARENT_NO_CONTEXT, $level),
                $partialName,
                $strictArg,
                $this->getAssertBlockContext('$parent', $level + 1),
                $indentParam
            );
        }

        return sprintf(
            $this->prepare($scoped ? self::PARENT_SCOPED : self::PARENT, $level),
            $partialName,
            $strictArg,
            $this->walk($realChildren, $level + 1),
            $this->getAssertBlockContext('$parent', $level + 1),
            $indentParam
        );
    }

    /**
     * Generate strict extra block assertion source for a loaded parent template.
     *
     * @param string $parent Loaded parent template expression
     * @param int    $level
     *
     * @return string
     */
    private function getAssertBlockContext($parent, $level)
    {
        if (($this->options->strictTags & Engine::STRICT_EXTRA_BLOCKS) === 0) {
            return '';
        }

        return sprintf($this->prepare(self::ASSERT_BLOCK_CONTEXT, $level), $parent);
    }

    /**
     * Helper method for filtering out non-block-arg tokens.
     *
     * @return bool True if $node is a block arg token
     */
    private static function onlyBlockArgs(array $node)
    {
        return $node[Tokenizer::TYPE] === Tokenizer::T_BLOCK_ARG;
    }

    /**
     * Push a new section-local static partial cache scope.
     */
    private function beginPartialCacheScope()
    {
        $this->partialCacheScopes[] = [];
    }

    /**
     * Pop the current section-local static partial cache scope.
     */
    private function endPartialCacheScope()
    {
        array_pop($this->partialCacheScopes);
    }

    /**
     * @return bool
     */
    private function isCachingPartials()
    {
        return !empty($this->partialCacheScopes);
    }

    /**
     * Register a static partial or parent cache in the current section scope.
     *
     * @param string $type Partial variable prefix ('partial' or 'parent')
     * @param string $id   Partial name
     *
     * @return string Generated variable name
     */
    private function cachePartial($type, $id)
    {
        $key = $type . ':' . $id;
        $scope = &$this->partialCacheScopes[count($this->partialCacheScopes) - 1];

        if (!isset($scope[$key])) {
            $scope[$key] = sprintf('%s%s', $type, ucfirst(md5($key)));
        }

        return $scope[$key];
    }

    /**
     * Generate section-local static partial cache initializer source.
     *
     * @return string
     */
    private function getPartialCacheInitializers()
    {
        $scope = $this->partialCacheScopes[count($this->partialCacheScopes) - 1];
        $initializer = $this->prepare(self::PARTIAL_CACHE_INIT, 2);
        $code = '';

        foreach ($scope as $name) {
            $code .= sprintf($initializer, $name);
        }

        return $code;
    }

    const VARIABLE = '
        $value = $this->resolveValue(%s, $context);%s
        $buffer .= %s($value === null ? \'\' : %s);
    ';

    const VARIABLE_NO_LAMBDAS = '
        $value = %s;%s
        $buffer .= %s($value === null ? \'\' : %s);
    ';

    /**
     * Generate Mustache Template variable interpolation PHP source.
     *
     * @param string   $id      Variable name
     * @param string[] $filters Array of filters
     * @param bool     $escape  Escape the variable value for output?
     * @param int      $level
     *
     * @return string Generated variable interpolation PHP source
     */
    private function variable($id, $filters, $escape, $level)
    {
        $lookup   = $this->getFindValue($id, Engine::STRICT_INTERPOLATION);
        $filters  = $this->getFilters($filters, $level);
        $value    = $escape ? $this->getEscape() : $this->getStringify('$value');
        $template = $this->lambdas ? self::VARIABLE : self::VARIABLE_NO_LAMBDAS;

        return sprintf($this->prepare($template, $level), $lookup, $filters, $this->flushIndent(), $value);
    }

    const FILTER = '
        $filter = $context->%s(%s%s);
        if (!(%s)) {
            throw new \\Mustache\\Exception\\UnknownFilterException(%s);
        }
        $value = call_user_func($filter, %s);%s
    ';
    const FILTER_FIRST_VALUE = '$this->resolveValue($value, $context)';
    const FILTER_VALUE = '$value';

    /**
     * Generate Mustache Template variable filtering PHP source.
     *
     * If the initial $value is a lambda it will be resolved before starting the filter chain.
     *
     * @param string[] $filters Array of filters
     * @param int      $level
     * @param bool     $first   (default: false)
     *
     * @return string Generated filter PHP source
     */
    private function getFilters(array $filters, $level, $first = true)
    {
        if (empty($filters)) {
            return '';
        }

        $name     = array_shift($filters);
        $method   = $this->getFindMethod($name);
        $filter   = ($method !== 'last') ? var_export($name, true) : '';
        $findArg  = $this->getFindMethodArgs($method);
        $callable = $this->getCallable('$filter');
        $msg      = var_export($name, true);
        $value    = $first ? self::FILTER_FIRST_VALUE : self::FILTER_VALUE;

        return sprintf($this->prepare(self::FILTER, $level), $method, $filter, $findArg, $callable, $msg, $value, $this->getFilters($filters, $level, false));
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
    const CUSTOM_ESCAPE  = '$this->stringifyValue(call_user_func($this->mustache->getEscape(), %s)%s)';
    const STRINGIFY      = '(is_scalar(%1$s) ? %1$s : $this->stringifyNonScalar(%1$s%2$s))';

    /**
     * Get the current escaper.
     *
     * @param string $value (default: '$value')
     *
     * @return string Either a custom callback, or an inline call to `htmlspecialchars`
     */
    private function getEscape($value = '$value')
    {
        if ($this->options->customEscape) {
            return sprintf(self::CUSTOM_ESCAPE, $value, $this->getStringifyTail());
        }

        return sprintf(self::DEFAULT_ESCAPE, $this->getStringify($value), var_export($this->options->entityFlags, true), var_export($this->options->charset, true));
    }

    /**
     * Get output coercion PHP source for the current coercion strictness.
     *
     * @param string $value (default: '$value')
     *
     * @return string
     */
    private function getStringify($value = '$value')
    {
        return sprintf(self::STRINGIFY, $value, $this->getStringifyTail());
    }

    /**
     * Get arguments to pass through to stringify helpers for current coercion strictness.
     *
     * @return string
     */
    private function getStringifyTail()
    {
        return ($this->options->strictTags & Engine::STRICT_COERCION) !== 0 ? '' : ', false';
    }

    const CONTEXT_FRAME_HOIST = '
        $frame = $context->last();
        if (!is_array($frame)) {
            $frame = [];
        }
    ';

    const CONTEXT_FRAME_LOOKUP = '(array_key_exists(%1$s, $frame) ? $frame[%1$s] : $context->find(%1$s%2$s))';

    /**
     * Walk a subtree with a context frame scope active, prepending the cached-frame
     * hoist if the subtree has enough plain lookups to benefit from it.
     *
     * @param int $level      Walk indent level
     * @param int $hoistLevel Hoist indent level
     *
     * @return string Generated PHP source code
     */
    private function walkWithContextFrame(array $tree, $level = 0, $hoistLevel = 1)
    {
        $useContextFrame = $this->hasMultipleContextFrameLookups($tree);

        $this->beginContextFrameScope($useContextFrame);
        $code = $this->walk($tree, $level);
        $this->endContextFrameScope();

        if ($useContextFrame) {
            $code = $this->getContextFrameHoist($hoistLevel) . $code;
        }

        return $code;
    }

    /**
     * Push a context frame scope. When $enabled, lookups within this scope inline
     * a fast path against the cached top frame instead of calling Context::find.
     *
     * @param bool $enabled
     */
    private function beginContextFrameScope($enabled)
    {
        $this->contextFrameScopes[] = $enabled;
    }

    private function endContextFrameScope()
    {
        array_pop($this->contextFrameScopes);
    }

    /**
     * @return bool
     */
    private function hasContextFrameScope()
    {
        return !empty($this->contextFrameScopes) && $this->contextFrameScopes[count($this->contextFrameScopes) - 1];
    }

    /**
     * @param int $level
     *
     * @return string
     */
    private function getContextFrameHoist($level)
    {
        return $this->prepare(self::CONTEXT_FRAME_HOIST, $level);
    }

    /**
     * Whether this subtree has enough plain context lookups to justify the
     * cached-frame fast path for a generated function.
     *
     * Does not recurse into nodes that emit their own function (sections, partials,
     * parents); those manage their own context frame scope.
     *
     * @return bool
     */
    private function hasMultipleContextFrameLookups(array $tree)
    {
        $count = 0;

        return $this->hasMoreThanOneContextFrameLookup($tree, $count);
    }

    /**
     * Walk a tree until the second plain context lookup is found.
     *
     * @param int $count
     *
     * @return bool
     */
    private function hasMoreThanOneContextFrameLookup(array $tree, &$count)
    {
        foreach ($tree as $node) {
            switch ($node[Tokenizer::TYPE]) {
                case Tokenizer::T_SECTION:
                case Tokenizer::T_ESCAPED:
                case Tokenizer::T_UNESCAPED:
                case Tokenizer::T_UNESCAPED_2:
                    if ($this->isContextFrameLookup($node[Tokenizer::NAME]) && ++$count > 1) {
                        return true;
                    }
                    break;

                case Tokenizer::T_INVERTED:
                    if ($this->isContextFrameLookup($node[Tokenizer::NAME]) && ++$count > 1) {
                        return true;
                    }
                    if ($this->hasMoreThanOneContextFrameLookup($node[Tokenizer::NODES], $count)) {
                        return true;
                    }
                    break;

                case Tokenizer::T_BLOCK_VAR:
                    if ($this->hasMoreThanOneContextFrameLookup($node[Tokenizer::NODES], $count)) {
                        return true;
                    }
                    break;
            }
        }

        return false;
    }

    /**
     * Whether a variable id can use the cached context frame fast path.
     *
     * @param string $id
     *
     * @return bool
     */
    private function isContextFrameLookup($id)
    {
        return $id !== '.' && strpos($id, '.') === false;
    }

    /**
     * Generate source for finding a value in the context.
     *
     * @param string $id Variable name
     *
     * @return string
     */
    private function getFindValue($id, $strictTag = Engine::STRICT_NONE)
    {
        $method = $this->getFindMethod($id);

        if ($method === 'find' && $this->hasContextFrameScope()) {
            return sprintf(self::CONTEXT_FRAME_LOOKUP, var_export($id, true), $this->getFindMethodArgs($method, $strictTag));
        }

        $id = ($method !== 'last') ? var_export($id, true) : '';

        return sprintf('$context->%s(%s%s)', $method, $id, $this->getFindMethodArgs($method, $strictTag));
    }

    /**
     * Select the appropriate Context `find` method for a given $id.
     *
     * The return value will be one of `find`, `findDot`, `findAnchoredDot` or `last`.
     *
     * @see \Mustache\Context::find
     * @see \Mustache\Context::findDot
     * @see \Mustache\Context::last
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

        if (isset($this->pragmas[Engine::PRAGMA_ANCHORED_DOT]) && $this->pragmas[Engine::PRAGMA_ANCHORED_DOT]) {
            if (substr($id, 0, 1) === '.') {
                return 'findAnchoredDot';
            }
        }

        if (strpos($id, '.') === false) {
            return 'find';
        }

        return 'findDot';
    }

    /**
     * Get the args needed for a given find method.
     *
     * Strict-tag and strict-callables args are only emitted when the engine
     * actually uses them, so non-strict templates compile to leaner code.
     *
     * @param string $method Find method name
     */
    private function getFindMethodArgs($method, $strictTag = Engine::STRICT_NONE)
    {
        $strict = $this->hasStrictLookups();

        if ($method === 'find') {
            return $strict ? sprintf(', %d', $strictTag) : '';
        }

        if ($method === 'findDot' || $method === 'findAnchoredDot') {
            if (!$strict) {
                return $this->options->strictCallables ? ', true' : '';
            }

            return sprintf(', %s, %d', $this->options->strictCallables ? 'true' : 'false', $strictTag);
        }

        return '';
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
        $tpl = $this->options->strictCallables ? self::STRICT_IS_CALLABLE : self::IS_CALLABLE;

        return sprintf($tpl, $variable, $variable);
    }

    /**
     * Check whether strict tag lookup categories beyond output coercion are enabled.
     *
     * @return bool
     */
    private function hasStrictLookups()
    {
        return ($this->options->strictTags & ~Engine::STRICT_COERCION) !== Engine::STRICT_NONE;
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
}
