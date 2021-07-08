<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace RectorPrefix20210708\Symfony\Component\Yaml;

use RectorPrefix20210708\Symfony\Component\Yaml\Exception\DumpException;
use RectorPrefix20210708\Symfony\Component\Yaml\Exception\ParseException;
use RectorPrefix20210708\Symfony\Component\Yaml\Tag\TaggedValue;
/**
 * Inline implements a YAML parser/dumper for the YAML inline syntax.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 */
class Inline
{
    public const REGEX_QUOTED_STRING = '(?:"([^"\\\\]*+(?:\\\\.[^"\\\\]*+)*+)"|\'([^\']*+(?:\'\'[^\']*+)*+)\')';
    public static $parsedLineNumber = -1;
    public static $parsedFilename;
    private static $exceptionOnInvalidType = \false;
    private static $objectSupport = \false;
    private static $objectForMap = \false;
    private static $constantSupport = \false;
    /**
     * @param int $flags
     * @param int|null $parsedLineNumber
     * @param string|null $parsedFilename
     */
    public static function initialize($flags, $parsedLineNumber = null, $parsedFilename = null)
    {
        self::$exceptionOnInvalidType = (bool) (\RectorPrefix20210708\Symfony\Component\Yaml\Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE & $flags);
        self::$objectSupport = (bool) (\RectorPrefix20210708\Symfony\Component\Yaml\Yaml::PARSE_OBJECT & $flags);
        self::$objectForMap = (bool) (\RectorPrefix20210708\Symfony\Component\Yaml\Yaml::PARSE_OBJECT_FOR_MAP & $flags);
        self::$constantSupport = (bool) (\RectorPrefix20210708\Symfony\Component\Yaml\Yaml::PARSE_CONSTANT & $flags);
        self::$parsedFilename = $parsedFilename;
        if (null !== $parsedLineNumber) {
            self::$parsedLineNumber = $parsedLineNumber;
        }
    }
    /**
     * Converts a YAML string to a PHP value.
     *
     * @param string $value      A YAML string
     * @param int    $flags      A bit field of PARSE_* constants to customize the YAML parser behavior
     * @param array  $references Mapping of variable names to values
     *
     * @return mixed A PHP value
     *
     * @throws ParseException
     */
    public static function parse($value = null, $flags = 0, &$references = [])
    {
        self::initialize($flags);
        $value = \trim($value);
        if ('' === $value) {
            return '';
        }
        if (2 & (int) \ini_get('mbstring.func_overload')) {
            $mbEncoding = \mb_internal_encoding();
            \mb_internal_encoding('ASCII');
        }
        try {
            $i = 0;
            $tag = self::parseTag($value, $i, $flags);
            switch ($value[$i]) {
                case '[':
                    $result = self::parseSequence($value, $flags, $i, $references);
                    ++$i;
                    break;
                case '{':
                    $result = self::parseMapping($value, $flags, $i, $references);
                    ++$i;
                    break;
                default:
                    $result = self::parseScalar($value, $flags, null, $i, null === $tag, $references);
            }
            // some comments are allowed at the end
            if (\preg_replace('/\\s*#.*$/A', '', \substr($value, $i))) {
                throw new \RectorPrefix20210708\Symfony\Component\Yaml\Exception\ParseException(\sprintf('Unexpected characters near "%s".', \substr($value, $i)), self::$parsedLineNumber + 1, $value, self::$parsedFilename);
            }
            if (null !== $tag && '' !== $tag) {
                return new \RectorPrefix20210708\Symfony\Component\Yaml\Tag\TaggedValue($tag, $result);
            }
            return $result;
        } finally {
            if (isset($mbEncoding)) {
                \mb_internal_encoding($mbEncoding);
            }
        }
    }
    /**
     * Dumps a given PHP variable to a YAML string.
     *
     * @param mixed $value The PHP variable to convert
     * @param int   $flags A bit field of Yaml::DUMP_* constants to customize the dumped YAML string
     *
     * @return string The YAML string representing the PHP value
     *
     * @throws DumpException When trying to dump PHP resource
     */
    public static function dump($value, $flags = 0) : string
    {
        switch (\true) {
            case \is_resource($value):
                if (\RectorPrefix20210708\Symfony\Component\Yaml\Yaml::DUMP_EXCEPTION_ON_INVALID_TYPE & $flags) {
                    throw new \RectorPrefix20210708\Symfony\Component\Yaml\Exception\DumpException(\sprintf('Unable to dump PHP resources in a YAML file ("%s").', \get_resource_type($value)));
                }
                return self::dumpNull($flags);
            case $value instanceof \DateTimeInterface:
                return $value->format('c');
            case $value instanceof \RectorPrefix20210708\UnitEnum:
                return \sprintf('!php/const %s::%s', \get_class($value), $value->name);
            case \is_object($value):
                if ($value instanceof \RectorPrefix20210708\Symfony\Component\Yaml\Tag\TaggedValue) {
                    return '!' . $value->getTag() . ' ' . self::dump($value->getValue(), $flags);
                }
                if (\RectorPrefix20210708\Symfony\Component\Yaml\Yaml::DUMP_OBJECT & $flags) {
                    return '!php/object ' . self::dump(\serialize($value));
                }
                if (\RectorPrefix20210708\Symfony\Component\Yaml\Yaml::DUMP_OBJECT_AS_MAP & $flags && ($value instanceof \stdClass || $value instanceof \ArrayObject)) {
                    $output = [];
                    foreach ($value as $key => $val) {
                        $output[] = \sprintf('%s: %s', self::dump($key, $flags), self::dump($val, $flags));
                    }
                    return \sprintf('{ %s }', \implode(', ', $output));
                }
                if (\RectorPrefix20210708\Symfony\Component\Yaml\Yaml::DUMP_EXCEPTION_ON_INVALID_TYPE & $flags) {
                    throw new \RectorPrefix20210708\Symfony\Component\Yaml\Exception\DumpException('Object support when dumping a YAML file has been disabled.');
                }
                return self::dumpNull($flags);
            case \is_array($value):
                return self::dumpArray($value, $flags);
            case null === $value:
                return self::dumpNull($flags);
            case \true === $value:
                return 'true';
            case \false === $value:
                return 'false';
            case \ctype_digit($value):
                return \is_string($value) ? "'{$value}'" : (int) $value;
            case \is_numeric($value) && \false === \strpos($value, "\f") && \false === \strpos($value, "\n") && \false === \strpos($value, "\r") && \false === \strpos($value, "\t") && \false === \strpos($value, "\v"):
                $locale = \setlocale(\LC_NUMERIC, 0);
                if (\false !== $locale) {
                    \setlocale(\LC_NUMERIC, 'C');
                }
                if (\is_float($value)) {
                    $repr = (string) $value;
                    if (\is_infinite($value)) {
                        $repr = \str_ireplace('INF', '.Inf', $repr);
                    } elseif (\floor($value) == $value && $repr == $value) {
                        // Preserve float data type since storing a whole number will result in integer value.
                        $repr = '!!float ' . $repr;
                    }
                } else {
                    $repr = \is_string($value) ? "'{$value}'" : (string) $value;
                }
                if (\false !== $locale) {
                    \setlocale(\LC_NUMERIC, $locale);
                }
                return $repr;
            case '' == $value:
                return "''";
            case self::isBinaryString($value):
                return '!!binary ' . \base64_encode($value);
            case \RectorPrefix20210708\Symfony\Component\Yaml\Escaper::requiresDoubleQuoting($value):
                return \RectorPrefix20210708\Symfony\Component\Yaml\Escaper::escapeWithDoubleQuotes($value);
            case \RectorPrefix20210708\Symfony\Component\Yaml\Escaper::requiresSingleQuoting($value):
            case \RectorPrefix20210708\Symfony\Component\Yaml\Parser::preg_match('{^[0-9]+[_0-9]*$}', $value):
            case \RectorPrefix20210708\Symfony\Component\Yaml\Parser::preg_match(self::getHexRegex(), $value):
            case \RectorPrefix20210708\Symfony\Component\Yaml\Parser::preg_match(self::getTimestampRegex(), $value):
                return \RectorPrefix20210708\Symfony\Component\Yaml\Escaper::escapeWithSingleQuotes($value);
            default:
                return $value;
        }
    }
    /**
     * Check if given array is hash or just normal indexed array.
     *
     * @param array|\ArrayObject|\stdClass $value The PHP array or array-like object to check
     *
     * @return bool true if value is hash array, false otherwise
     */
    public static function isHash($value) : bool
    {
        if ($value instanceof \stdClass || $value instanceof \ArrayObject) {
            return \true;
        }
        $expectedKey = 0;
        foreach ($value as $key => $val) {
            if ($key !== $expectedKey++) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * Dumps a PHP array to a YAML string.
     *
     * @param array $value The PHP array to dump
     * @param int   $flags A bit field of Yaml::DUMP_* constants to customize the dumped YAML string
     *
     * @return string The YAML string representing the PHP array
     */
    private static function dumpArray(array $value, int $flags) : string
    {
        // array
        if (($value || \RectorPrefix20210708\Symfony\Component\Yaml\Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE & $flags) && !self::isHash($value)) {
            $output = [];
            foreach ($value as $val) {
                $output[] = self::dump($val, $flags);
            }
            return \sprintf('[%s]', \implode(', ', $output));
        }
        // hash
        $output = [];
        foreach ($value as $key => $val) {
            $output[] = \sprintf('%s: %s', self::dump($key, $flags), self::dump($val, $flags));
        }
        return \sprintf('{ %s }', \implode(', ', $output));
    }
    private static function dumpNull(int $flags) : string
    {
        if (\RectorPrefix20210708\Symfony\Component\Yaml\Yaml::DUMP_NULL_AS_TILDE & $flags) {
            return '~';
        }
        return 'null';
    }
    /**
     * Parses a YAML scalar.
     *
     * @return mixed
     *
     * @throws ParseException When malformed inline YAML string is parsed
     * @param string $scalar
     * @param int $flags
     * @param mixed[]|null $delimiters
     * @param int $i
     * @param bool $evaluate
     * @param mixed[] $references
     */
    public static function parseScalar($scalar, $flags = 0, $delimiters = null, &$i = 0, $evaluate = \true, &$references = [])
    {
        if (\in_array($scalar[$i], ['"', "'"], \true)) {
            // quoted scalar
            $output = self::parseQuotedScalar($scalar, $i);
            if (null !== $delimiters) {
                $tmp = \ltrim(\substr($scalar, $i), " \n");
                if ('' === $tmp) {
                    throw new \RectorPrefix20210708\Symfony\Component\Yaml\Exception\ParseException(\sprintf('Unexpected end of line, expected one of "%s".', \implode('', $delimiters)), self::$parsedLineNumber + 1, $scalar, self::$parsedFilename);
                }
                if (!\in_array($tmp[0], $delimiters)) {
                    throw new \RectorPrefix20210708\Symfony\Component\Yaml\Exception\ParseException(\sprintf('Unexpected characters (%s).', \substr($scalar, $i)), self::$parsedLineNumber + 1, $scalar, self::$parsedFilename);
                }
            }
        } else {
            // "normal" string
            if (!$delimiters) {
                $output = \substr($scalar, $i);
                $i += \strlen($output);
                // remove comments
                if (\RectorPrefix20210708\Symfony\Component\Yaml\Parser::preg_match('/[ \\t]+#/', $output, $match, \PREG_OFFSET_CAPTURE)) {
                    $output = \substr($output, 0, $match[0][1]);
                }
            } elseif (\RectorPrefix20210708\Symfony\Component\Yaml\Parser::preg_match('/^(.*?)(' . \implode('|', $delimiters) . ')/', \substr($scalar, $i), $match)) {
                $output = $match[1];
                $i += \strlen($output);
                $output = \trim($output);
            } else {
                throw new \RectorPrefix20210708\Symfony\Component\Yaml\Exception\ParseException(\sprintf('Malformed inline YAML string: "%s".', $scalar), self::$parsedLineNumber + 1, null, self::$parsedFilename);
            }
            // a non-quoted string cannot start with @ or ` (reserved) nor with a scalar indicator (| or >)
            if ($output && ('@' === $output[0] || '`' === $output[0] || '|' === $output[0] || '>' === $output[0] || '%' === $output[0])) {
                throw new \RectorPrefix20210708\Symfony\Component\Yaml\Exception\ParseException(\sprintf('The reserved indicator "%s" cannot start a plain scalar; you need to quote the scalar.', $output[0]), self::$parsedLineNumber + 1, $output, self::$parsedFilename);
            }
            if ($evaluate) {
                $output = self::evaluateScalar($output, $flags, $references);
            }
        }
        return $output;
    }
    /**
     * Parses a YAML quoted scalar.
     *
     * @throws ParseException When malformed inline YAML string is parsed
     */
    private static function parseQuotedScalar(string $scalar, int &$i) : string
    {
        if (!\RectorPrefix20210708\Symfony\Component\Yaml\Parser::preg_match('/' . self::REGEX_QUOTED_STRING . '/Au', \substr($scalar, $i), $match)) {
            throw new \RectorPrefix20210708\Symfony\Component\Yaml\Exception\ParseException(\sprintf('Malformed inline YAML string: "%s".', \substr($scalar, $i)), self::$parsedLineNumber + 1, $scalar, self::$parsedFilename);
        }
        $output = \substr($match[0], 1, -1);
        $unescaper = new \RectorPrefix20210708\Symfony\Component\Yaml\Unescaper();
        if ('"' == $scalar[$i]) {
            $output = $unescaper->unescapeDoubleQuotedString($output);
        } else {
            $output = $unescaper->unescapeSingleQuotedString($output);
        }
        $i += \strlen($match[0]);
        return $output;
    }
    /**
     * Parses a YAML sequence.
     *
     * @throws ParseException When malformed inline YAML string is parsed
     */
    private static function parseSequence(string $sequence, int $flags, int &$i = 0, array &$references = []) : array
    {
        $output = [];
        $len = \strlen($sequence);
        ++$i;
        // [foo, bar, ...]
        while ($i < $len) {
            if (']' === $sequence[$i]) {
                return $output;
            }
            if (',' === $sequence[$i] || ' ' === $sequence[$i]) {
                ++$i;
                continue;
            }
            $tag = self::parseTag($sequence, $i, $flags);
            switch ($sequence[$i]) {
                case '[':
                    // nested sequence
                    $value = self::parseSequence($sequence, $flags, $i, $references);
                    break;
                case '{':
                    // nested mapping
                    $value = self::parseMapping($sequence, $flags, $i, $references);
                    break;
                default:
                    $isQuoted = \in_array($sequence[$i], ['"', "'"], \true);
                    $value = self::parseScalar($sequence, $flags, [',', ']'], $i, null === $tag, $references);
                    // the value can be an array if a reference has been resolved to an array var
                    if (\is_string($value) && !$isQuoted && \false !== \strpos($value, ': ')) {
                        // embedded mapping?
                        try {
                            $pos = 0;
                            $value = self::parseMapping('{' . $value . '}', $flags, $pos, $references);
                        } catch (\InvalidArgumentException $e) {
                            // no, it's not
                        }
                    }
                    if (\is_string($value) && '' !== $value && '&' === $value[0] && \RectorPrefix20210708\Symfony\Component\Yaml\Parser::preg_match(\RectorPrefix20210708\Symfony\Component\Yaml\Parser::REFERENCE_PATTERN, $value, $matches)) {
                        $references[$matches['ref']] = $matches['value'];
                        $value = $matches['value'];
                    }
                    --$i;
            }
            if (null !== $tag && '' !== $tag) {
                $value = new \RectorPrefix20210708\Symfony\Component\Yaml\Tag\TaggedValue($tag, $value);
            }
            $output[] = $value;
            ++$i;
        }
        throw new \RectorPrefix20210708\Symfony\Component\Yaml\Exception\ParseException(\sprintf('Malformed inline YAML string: "%s".', $sequence), self::$parsedLineNumber + 1, null, self::$parsedFilename);
    }
    /**
     * Parses a YAML mapping.
     *
     * @return array|\stdClass
     *
     * @throws ParseException When malformed inline YAML string is parsed
     */
    private static function parseMapping(string $mapping, int $flags, int &$i = 0, array &$references = [])
    {
        $output = [];
        $len = \strlen($mapping);
        ++$i;
        $allowOverwrite = \false;
        // {foo: bar, bar:foo, ...}
        while ($i < $len) {
            switch ($mapping[$i]) {
                case ' ':
                case ',':
                case "\n":
                    ++$i;
                    continue 2;
                case '}':
                    if (self::$objectForMap) {
                        return (object) $output;
                    }
                    return $output;
            }
            // key
            $offsetBeforeKeyParsing = $i;
            $isKeyQuoted = \in_array($mapping[$i], ['"', "'"], \true);
            $key = self::parseScalar($mapping, $flags, [':', ' '], $i, \false);
            if ($offsetBeforeKeyParsing === $i) {
                throw new \RectorPrefix20210708\Symfony\Component\Yaml\Exception\ParseException('Missing mapping key.', self::$parsedLineNumber + 1, $mapping);
            }
            if ('!php/const' === $key) {
                $key .= ' ' . self::parseScalar($mapping, $flags, [':'], $i, \false);
                $key = self::evaluateScalar($key, $flags);
            }
            if (\false === ($i = \strpos($mapping, ':', $i))) {
                break;
            }
            if (!$isKeyQuoted) {
                $evaluatedKey = self::evaluateScalar($key, $flags, $references);
                if ('' !== $key && $evaluatedKey !== $key && !\is_string($evaluatedKey) && !\is_int($evaluatedKey)) {
                    throw new \RectorPrefix20210708\Symfony\Component\Yaml\Exception\ParseException('Implicit casting of incompatible mapping keys to strings is not supported. Quote your evaluable mapping keys instead.', self::$parsedLineNumber + 1, $mapping);
                }
            }
            if (!$isKeyQuoted && (!isset($mapping[$i + 1]) || !\in_array($mapping[$i + 1], [' ', ',', '[', ']', '{', '}', "\n"], \true))) {
                throw new \RectorPrefix20210708\Symfony\Component\Yaml\Exception\ParseException('Colons must be followed by a space or an indication character (i.e. " ", ",", "[", "]", "{", "}").', self::$parsedLineNumber + 1, $mapping);
            }
            if ('<<' === $key) {
                $allowOverwrite = \true;
            }
            while ($i < $len) {
                if (':' === $mapping[$i] || ' ' === $mapping[$i] || "\n" === $mapping[$i]) {
                    ++$i;
                    continue;
                }
                $tag = self::parseTag($mapping, $i, $flags);
                switch ($mapping[$i]) {
                    case '[':
                        // nested sequence
                        $value = self::parseSequence($mapping, $flags, $i, $references);
                        // Spec: Keys MUST be unique; first one wins.
                        // Parser cannot abort this mapping earlier, since lines
                        // are processed sequentially.
                        // But overwriting is allowed when a merge node is used in current block.
                        if ('<<' === $key) {
                            foreach ($value as $parsedValue) {
                                $output += $parsedValue;
                            }
                        } elseif ($allowOverwrite || !isset($output[$key])) {
                            if (null !== $tag) {
                                $output[$key] = new \RectorPrefix20210708\Symfony\Component\Yaml\Tag\TaggedValue($tag, $value);
                            } else {
                                $output[$key] = $value;
                            }
                        } elseif (isset($output[$key])) {
                            throw new \RectorPrefix20210708\Symfony\Component\Yaml\Exception\ParseException(\sprintf('Duplicate key "%s" detected.', $key), self::$parsedLineNumber + 1, $mapping);
                        }
                        break;
                    case '{':
                        // nested mapping
                        $value = self::parseMapping($mapping, $flags, $i, $references);
                        // Spec: Keys MUST be unique; first one wins.
                        // Parser cannot abort this mapping earlier, since lines
                        // are processed sequentially.
                        // But overwriting is allowed when a merge node is used in current block.
                        if ('<<' === $key) {
                            $output += $value;
                        } elseif ($allowOverwrite || !isset($output[$key])) {
                            if (null !== $tag) {
                                $output[$key] = new \RectorPrefix20210708\Symfony\Component\Yaml\Tag\TaggedValue($tag, $value);
                            } else {
                                $output[$key] = $value;
                            }
                        } elseif (isset($output[$key])) {
                            throw new \RectorPrefix20210708\Symfony\Component\Yaml\Exception\ParseException(\sprintf('Duplicate key "%s" detected.', $key), self::$parsedLineNumber + 1, $mapping);
                        }
                        break;
                    default:
                        $value = self::parseScalar($mapping, $flags, [',', '}', "\n"], $i, null === $tag, $references);
                        // Spec: Keys MUST be unique; first one wins.
                        // Parser cannot abort this mapping earlier, since lines
                        // are processed sequentially.
                        // But overwriting is allowed when a merge node is used in current block.
                        if ('<<' === $key) {
                            $output += $value;
                        } elseif ($allowOverwrite || !isset($output[$key])) {
                            if (\is_string($value) && '' !== $value && '&' === $value[0] && \RectorPrefix20210708\Symfony\Component\Yaml\Parser::preg_match(\RectorPrefix20210708\Symfony\Component\Yaml\Parser::REFERENCE_PATTERN, $value, $matches)) {
                                $references[$matches['ref']] = $matches['value'];
                                $value = $matches['value'];
                            }
                            if (null !== $tag) {
                                $output[$key] = new \RectorPrefix20210708\Symfony\Component\Yaml\Tag\TaggedValue($tag, $value);
                            } else {
                                $output[$key] = $value;
                            }
                        } elseif (isset($output[$key])) {
                            throw new \RectorPrefix20210708\Symfony\Component\Yaml\Exception\ParseException(\sprintf('Duplicate key "%s" detected.', $key), self::$parsedLineNumber + 1, $mapping);
                        }
                        --$i;
                }
                ++$i;
                continue 2;
            }
        }
        throw new \RectorPrefix20210708\Symfony\Component\Yaml\Exception\ParseException(\sprintf('Malformed inline YAML string: "%s".', $mapping), self::$parsedLineNumber + 1, null, self::$parsedFilename);
    }
    /**
     * Evaluates scalars and replaces magic values.
     *
     * @return mixed The evaluated YAML string
     *
     * @throws ParseException when object parsing support was disabled and the parser detected a PHP object or when a reference could not be resolved
     */
    private static function evaluateScalar(string $scalar, int $flags, array &$references = [])
    {
        $scalar = \trim($scalar);
        if ('*' === ($scalar[0] ?? '')) {
            if (\false !== ($pos = \strpos($scalar, '#'))) {
                $value = \substr($scalar, 1, $pos - 2);
            } else {
                $value = \substr($scalar, 1);
            }
            // an unquoted *
            if (\false === $value || '' === $value) {
                throw new \RectorPrefix20210708\Symfony\Component\Yaml\Exception\ParseException('A reference must contain at least one character.', self::$parsedLineNumber + 1, $value, self::$parsedFilename);
            }
            if (!\array_key_exists($value, $references)) {
                throw new \RectorPrefix20210708\Symfony\Component\Yaml\Exception\ParseException(\sprintf('Reference "%s" does not exist.', $value), self::$parsedLineNumber + 1, $value, self::$parsedFilename);
            }
            return $references[$value];
        }
        $scalarLower = \strtolower($scalar);
        switch (\true) {
            case 'null' === $scalarLower:
            case '' === $scalar:
            case '~' === $scalar:
                return null;
            case 'true' === $scalarLower:
                return \true;
            case 'false' === $scalarLower:
                return \false;
            case '!' === $scalar[0]:
                switch (\true) {
                    case 0 === \strncmp($scalar, '!!str ', 6):
                        return (string) \substr($scalar, 6);
                    case 0 === \strncmp($scalar, '! ', 2):
                        return \substr($scalar, 2);
                    case 0 === \strncmp($scalar, '!php/object', 11):
                        if (self::$objectSupport) {
                            if (!isset($scalar[12])) {
                                trigger_deprecation('symfony/yaml', '5.1', 'Using the !php/object tag without a value is deprecated.');
                                return \false;
                            }
                            return \unserialize(self::parseScalar(\substr($scalar, 12)));
                        }
                        if (self::$exceptionOnInvalidType) {
                            throw new \RectorPrefix20210708\Symfony\Component\Yaml\Exception\ParseException('Object support when parsing a YAML file has been disabled.', self::$parsedLineNumber + 1, $scalar, self::$parsedFilename);
                        }
                        return null;
                    case 0 === \strncmp($scalar, '!php/const', 10):
                        if (self::$constantSupport) {
                            if (!isset($scalar[11])) {
                                trigger_deprecation('symfony/yaml', '5.1', 'Using the !php/const tag without a value is deprecated.');
                                return '';
                            }
                            $i = 0;
                            if (\defined($const = self::parseScalar(\substr($scalar, 11), 0, null, $i, \false))) {
                                return \constant($const);
                            }
                            throw new \RectorPrefix20210708\Symfony\Component\Yaml\Exception\ParseException(\sprintf('The constant "%s" is not defined.', $const), self::$parsedLineNumber + 1, $scalar, self::$parsedFilename);
                        }
                        if (self::$exceptionOnInvalidType) {
                            throw new \RectorPrefix20210708\Symfony\Component\Yaml\Exception\ParseException(\sprintf('The string "%s" could not be parsed as a constant. Did you forget to pass the "Yaml::PARSE_CONSTANT" flag to the parser?', $scalar), self::$parsedLineNumber + 1, $scalar, self::$parsedFilename);
                        }
                        return null;
                    case 0 === \strncmp($scalar, '!!float ', 8):
                        return (float) \substr($scalar, 8);
                    case 0 === \strncmp($scalar, '!!binary ', 9):
                        return self::evaluateBinaryScalar(\substr($scalar, 9));
                    default:
                        throw new \RectorPrefix20210708\Symfony\Component\Yaml\Exception\ParseException(\sprintf('The string "%s" could not be parsed as it uses an unsupported built-in tag.', $scalar), self::$parsedLineNumber, $scalar, self::$parsedFilename);
                }
            // no break
            case \preg_match('/^(?:\\+|-)?0o(?P<value>[0-7_]++)$/', $scalar, $matches):
                $value = \str_replace('_', '', $matches['value']);
                if ('-' === $scalar[0]) {
                    return -\octdec($value);
                } else {
                    return \octdec($value);
                }
            // Optimize for returning strings.
            // no break
            case \in_array($scalar[0], ['+', '-', '.'], \true) || \is_numeric($scalar[0]):
                if (\RectorPrefix20210708\Symfony\Component\Yaml\Parser::preg_match('{^[+-]?[0-9][0-9_]*$}', $scalar)) {
                    $scalar = \str_replace('_', '', (string) $scalar);
                }
                switch (\true) {
                    case \ctype_digit($scalar):
                        if (\preg_match('/^0[0-7]+$/', $scalar)) {
                            trigger_deprecation('symfony/yaml', '5.1', 'Support for parsing numbers prefixed with 0 as octal numbers. They will be parsed as strings as of 6.0.');
                            return \octdec($scalar);
                        }
                        $cast = (int) $scalar;
                        return $scalar === (string) $cast ? $cast : $scalar;
                    case '-' === $scalar[0] && \ctype_digit(\substr($scalar, 1)):
                        if (\preg_match('/^-0[0-7]+$/', $scalar)) {
                            trigger_deprecation('symfony/yaml', '5.1', 'Support for parsing numbers prefixed with 0 as octal numbers. They will be parsed as strings as of 6.0.');
                            return -\octdec(\substr($scalar, 1));
                        }
                        $cast = (int) $scalar;
                        return $scalar === (string) $cast ? $cast : $scalar;
                    case \is_numeric($scalar):
                    case \RectorPrefix20210708\Symfony\Component\Yaml\Parser::preg_match(self::getHexRegex(), $scalar):
                        $scalar = \str_replace('_', '', $scalar);
                        return '0x' === $scalar[0] . $scalar[1] ? \hexdec($scalar) : (float) $scalar;
                    case '.inf' === $scalarLower:
                    case '.nan' === $scalarLower:
                        return -\log(0);
                    case '-.inf' === $scalarLower:
                        return \log(0);
                    case \RectorPrefix20210708\Symfony\Component\Yaml\Parser::preg_match('/^(-|\\+)?[0-9][0-9_]*(\\.[0-9_]+)?$/', $scalar):
                        return (float) \str_replace('_', '', $scalar);
                    case \RectorPrefix20210708\Symfony\Component\Yaml\Parser::preg_match(self::getTimestampRegex(), $scalar):
                        // When no timezone is provided in the parsed date, YAML spec says we must assume UTC.
                        $time = new \DateTime($scalar, new \DateTimeZone('UTC'));
                        if (\RectorPrefix20210708\Symfony\Component\Yaml\Yaml::PARSE_DATETIME & $flags) {
                            return $time;
                        }
                        try {
                            if (\false !== ($scalar = $time->getTimestamp())) {
                                return $scalar;
                            }
                        } catch (\ValueError $e) {
                            // no-op
                        }
                        return $time->format('U');
                }
        }
        return (string) $scalar;
    }
    private static function parseTag(string $value, int &$i, int $flags) : ?string
    {
        if ('!' !== $value[$i]) {
            return null;
        }
        $tagLength = \strcspn($value, " \t\n[]{},", $i + 1);
        $tag = \substr($value, $i + 1, $tagLength);
        $nextOffset = $i + $tagLength + 1;
        $nextOffset += \strspn($value, ' ', $nextOffset);
        if ('' === $tag && (!isset($value[$nextOffset]) || \in_array($value[$nextOffset], [']', '}', ','], \true))) {
            throw new \RectorPrefix20210708\Symfony\Component\Yaml\Exception\ParseException(\sprintf('Using the unquoted scalar value "!" is not supported. You must quote it.', $value), self::$parsedLineNumber + 1, $value, self::$parsedFilename);
        }
        // Is followed by a scalar and is a built-in tag
        if ('' !== $tag && (!isset($value[$nextOffset]) || !\in_array($value[$nextOffset], ['[', '{'], \true)) && ('!' === $tag[0] || 'str' === $tag || 'php/const' === $tag || 'php/object' === $tag)) {
            // Manage in {@link self::evaluateScalar()}
            return null;
        }
        $i = $nextOffset;
        // Built-in tags
        if ('' !== $tag && '!' === $tag[0]) {
            throw new \RectorPrefix20210708\Symfony\Component\Yaml\Exception\ParseException(\sprintf('The built-in tag "!%s" is not implemented.', $tag), self::$parsedLineNumber + 1, $value, self::$parsedFilename);
        }
        if ('' !== $tag && !isset($value[$i])) {
            throw new \RectorPrefix20210708\Symfony\Component\Yaml\Exception\ParseException(\sprintf('Missing value for tag "%s".', $tag), self::$parsedLineNumber + 1, $value, self::$parsedFilename);
        }
        if ('' === $tag || \RectorPrefix20210708\Symfony\Component\Yaml\Yaml::PARSE_CUSTOM_TAGS & $flags) {
            return $tag;
        }
        throw new \RectorPrefix20210708\Symfony\Component\Yaml\Exception\ParseException(\sprintf('Tags support is not enabled. Enable the "Yaml::PARSE_CUSTOM_TAGS" flag to use "!%s".', $tag), self::$parsedLineNumber + 1, $value, self::$parsedFilename);
    }
    /**
     * @param string $scalar
     */
    public static function evaluateBinaryScalar($scalar) : string
    {
        $parsedBinaryData = self::parseScalar(\preg_replace('/\\s/', '', $scalar));
        if (0 !== \strlen($parsedBinaryData) % 4) {
            throw new \RectorPrefix20210708\Symfony\Component\Yaml\Exception\ParseException(\sprintf('The normalized base64 encoded data (data without whitespace characters) length must be a multiple of four (%d bytes given).', \strlen($parsedBinaryData)), self::$parsedLineNumber + 1, $scalar, self::$parsedFilename);
        }
        if (!\RectorPrefix20210708\Symfony\Component\Yaml\Parser::preg_match('#^[A-Z0-9+/]+={0,2}$#i', $parsedBinaryData)) {
            throw new \RectorPrefix20210708\Symfony\Component\Yaml\Exception\ParseException(\sprintf('The base64 encoded data (%s) contains invalid characters.', $parsedBinaryData), self::$parsedLineNumber + 1, $scalar, self::$parsedFilename);
        }
        return \base64_decode($parsedBinaryData, \true);
    }
    private static function isBinaryString(string $value) : bool
    {
        return !\preg_match('//u', $value) || \preg_match('/[^\\x00\\x07-\\x0d\\x1B\\x20-\\xff]/', $value);
    }
    /**
     * Gets a regex that matches a YAML date.
     *
     * @return string The regular expression
     *
     * @see http://www.yaml.org/spec/1.2/spec.html#id2761573
     */
    private static function getTimestampRegex() : string
    {
        return <<<EOF
        ~^
        (?P<year>[0-9][0-9][0-9][0-9])
        -(?P<month>[0-9][0-9]?)
        -(?P<day>[0-9][0-9]?)
        (?:(?:[Tt]|[ \t]+)
        (?P<hour>[0-9][0-9]?)
        :(?P<minute>[0-9][0-9])
        :(?P<second>[0-9][0-9])
        (?:\\.(?P<fraction>[0-9]*))?
        (?:[ \t]*(?P<tz>Z|(?P<tz_sign>[-+])(?P<tz_hour>[0-9][0-9]?)
        (?::(?P<tz_minute>[0-9][0-9]))?))?)?
        \$~x
EOF;
    }
    /**
     * Gets a regex that matches a YAML number in hexadecimal notation.
     */
    private static function getHexRegex() : string
    {
        return '~^0x[0-9a-f_]++$~i';
    }
}