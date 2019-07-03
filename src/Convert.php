<?php declare(strict_types=1);

namespace Jawira\CaseConverter;

use Jawira\CaseConverter\Glue\AdaCase;
use Jawira\CaseConverter\Glue\CamelCase;
use Jawira\CaseConverter\Glue\CobolCase;
use Jawira\CaseConverter\Glue\DashGluer;
use Jawira\CaseConverter\Glue\Gluer;
use Jawira\CaseConverter\Glue\KebabCase;
use Jawira\CaseConverter\Glue\LowerCase;
use Jawira\CaseConverter\Glue\MacroCase;
use Jawira\CaseConverter\Glue\PascalCase;
use Jawira\CaseConverter\Glue\SentenceCase;
use Jawira\CaseConverter\Glue\SnakeCase;
use Jawira\CaseConverter\Glue\SpaceGluer;
use Jawira\CaseConverter\Glue\TitleCase;
use Jawira\CaseConverter\Glue\TrainCase;
use Jawira\CaseConverter\Glue\UnderscoreGluer;
use Jawira\CaseConverter\Glue\UpperCase;
use Jawira\CaseConverter\Split\DashSplitter;
use Jawira\CaseConverter\Split\SpaceSplitter;
use Jawira\CaseConverter\Split\Splitter;
use Jawira\CaseConverter\Split\UnderscoreSplitter;
use Jawira\CaseConverter\Split\UppercaseSplitter;
use function is_subclass_of;
use function mb_strpos;
use function preg_match;
use const MB_CASE_LOWER;
use const MB_CASE_TITLE;
use const MB_CASE_UPPER;

/**
 * Convert string between different naming conventions.
 *
 * Handled formats:
 *
 * - Ada case
 * - Camel case
 * - Cobol case
 * - Kebab case
 * - Lower case
 * - Macro case
 * - Pascal case
 * - Sentence case
 * - Snake case
 * - Title case
 * - Train case
 * - Upper case
 *
 * @method self fromAda() Treat input string as _Ada case_
 * @method self fromCamel() Treat input string as _Camel case_
 * @method self fromCobol() Treat input string as _Cobol case_
 * @method self fromKebab() Treat input string as _Kebab case_
 * @method self fromLower() Treat input string as _Lower case_
 * @method self fromMacro() Treat input string as _Macro case_
 * @method self fromPascal() Treat input string as _Pascal case_
 * @method self fromSentence() Treat input string as _Sentence case_
 * @method self fromSnake() Treat input string as _Snake case_
 * @method self fromTitle() Treat input string as _Title case_
 * @method self fromTrain() Treat input string as _Train case_
 * @method self fromUpper() Treat input string as _Upper case_
 *
 * @method string toAda() Return string in _Ada case_ format
 * @method string toCamel() Return string in _Camel case_ format
 * @method string toCobol() Return string in _Cobol case_ format
 * @method string toKebab() Return string in _Kebab case_ format
 * @method string toLower() Return string in _Lower case_ format
 * @method string toMacro() Return string in _Macro case_ format
 * @method string toPascal() Return string in _Pascal case_ format
 * @method string toSentence() Return string in _Sentence case_ format
 * @method string toSnake() Return string in _Snake case_ format
 * @method string toTitle() Return string in _Title case_ format
 * @method string toTrain() Return string in _Train case_ format
 * @method string toUpper() Return string in _Upper case_ format
 *
 * @see     https://softwareengineering.stackexchange.com/questions/322413/bothered-by-an-unknown-letter-case-name
 * @see     http://www.unicode.org/charts/case/
 * @package Jawira\CaseConverter
 * @author  Jawira Portugal <dev@tugal.be>
 */
class Convert
{
    /**
     * @var string Input string to convert
     */
    protected $originalString;

    /**
     * @var string[] Words extracted from input string
     */
    protected $words;

    /**
     * @var int
     */
    protected $mbCaseLower;

    /**
     * @var int
     */
    protected $mbCaseUpper;

    /**
     * @var int
     */
    protected $mbCaseTitle;

    /**
     * Constructor method
     *
     * @param string $input String to convert
     *
     * @throws \Jawira\CaseConverter\CaseConverterException
     */
    public function __construct(string $input)
    {
        $this->originalString = $input;
        $this->fromAuto();
    }

    /**
     * Auto-detect naming convention
     *
     * @return \Jawira\CaseConverter\Convert
     * @throws \Jawira\CaseConverter\CaseConverterException
     */
    public function fromAuto(): self
    {
        $strategy = $this->analyse($this->originalString);
        $this->extractWords($strategy);

        return $this;
    }

    /**
     * Detects word separator of $input string and tells you what strategy you should use.
     *
     * @param string $input String to be analysed
     *
     * @return \Jawira\CaseConverter\Split\Splitter
     * @throws \Jawira\CaseConverter\CaseConverterException
     */
    protected function analyse(string $input): Splitter
    {
        if (mb_strpos($input, UnderscoreGluer::DELIMITER)) {
            $strategy = new UnderscoreSplitter($input);
        } elseif (mb_strpos($input, DashGluer::DELIMITER)) {
            $strategy = new DashSplitter($input);
        } elseif (mb_strpos($input, SpaceGluer::DELIMITER)) {
            $strategy = new SpaceSplitter($input);
        } elseif ($this->isUppercaseWord($input)) {
            $strategy = new UnderscoreSplitter($input);
        } else {
            $strategy = new UppercaseSplitter($input);
        }

        return $strategy;
    }

    /**
     * Returns true if $input string is a single word composed only by uppercase characters.
     *
     * ```
     * isUppercaseWord('BRUSSELS'); // true
     * isUppercaseWord('Brussels'); // false
     * ```
     *
     * @see     https://www.regular-expressions.info/unicode.html#category
     *
     * @param string $input String to be tested.
     *
     * @return bool
     * @throws \Jawira\CaseConverter\CaseConverterException
     */
    protected function isUppercaseWord(string $input): bool
    {
        $match = preg_match('#^\p{Lu}+$#u', $input);

        if (false === $match) {
            throw new CaseConverterException('Error executing regex'); // @codeCoverageIgnore
        }

        return 1 === $match;
    }

    /**
     * Main function, receives input string and then it stores extracted words into an array.
     *
     * @param \Jawira\CaseConverter\Split\Splitter $splitter
     *
     * @return $this
     */
    protected function extractWords(Splitter $splitter): self
    {
        $this->words = $splitter->split();

        return $this;
    }

    /**
     * Since PHP 7.3, new constants are used to specify _simple case mapping_. This method handles these new constants.
     *
     * Usually you would use:
     *
     * - MB_CASE_LOWER
     * - MB_CASE_UPPER
     * - MB_CASE_TITLE
     *
     * But PHP 7.3 introduced new constants:
     *
     * - MB_CASE_LOWER_SIMPLE
     * - MB_CASE_UPPER_SIMPLE
     * - MB_CASE_TITLE_SIMPLE
     *
     * @see https://www.php.net/manual/en/migration73.constants.php#migration73.constants.mbstring
     * @see https://www.php.net/manual/en/migration73.new-features.php#migration73.new-features.mbstring.case-mapping-folding
     */
    public function useSimpleMapping()
    {
        $lowerSimple = '\MB_CASE_LOWER_SIMPLE';
        $upperSimple = '\MB_CASE_UPPER_SIMPLE';
        $titleSimple = '\MB_CASE_TITLE_SIMPLE';

        $this->mbCaseLower = defined($lowerSimple) ? constant($lowerSimple) : MB_CASE_LOWER;
        $this->mbCaseUpper = defined($upperSimple) ? constant($upperSimple) : MB_CASE_UPPER;
        $this->mbCaseTitle = defined($titleSimple) ? constant($titleSimple) : MB_CASE_TITLE;

        return $this;
    }

    /**
     * Handle `to*` methods and `from*` methods
     *
     * @param string $methodName
     * @param array  $arguments
     *
     * @return string|\Jawira\CaseConverter\Convert
     * @throws \Jawira\CaseConverter\CaseConverterException
     */
    public function __call($methodName, $arguments)
    {
        if (0 === mb_strpos($methodName, 'from')) {
            $result = $this->handleSplitterMethod($methodName);
        } elseif (0 === mb_strpos($methodName, 'to')) {
            $result = $this->handleGluerMethod($methodName);
        } else {
            throw new CaseConverterException("Unknown method: $methodName");
        }

        return $result;
    }

    /**
     * Methods to explicitly define naming conventions for input string
     *
     * @param string $methodName
     *
     * @return $this
     * @throws \Jawira\CaseConverter\CaseConverterException
     */
    protected function handleSplitterMethod(string $methodName): self
    {
        switch ($methodName) {
            case 'fromCamel':
            case 'fromPascal':
                $strategy = new UppercaseSplitter($this->originalString);
                break;
            case 'fromSnake':
            case 'fromAda':
            case 'fromMacro':
                $strategy = new UnderscoreSplitter($this->originalString);
                break;
            case 'fromKebab':
            case 'fromTrain':
            case 'fromCobol':
                $strategy = new DashSplitter($this->originalString);
                break;
            case 'fromLower':
            case 'fromUpper':
            case 'fromTitle':
            case 'fromSentence':
                $strategy = new SpaceSplitter($this->originalString);
                break;
            default:
                throw new CaseConverterException("Unknown method: $methodName");
                break;
        }

        $this->extractWords($strategy);

        return $this;
    }

    /**
     * Handles all methods starting by `to*`
     *
     * @param string $methodName
     *
     * @return string
     * @throws \Jawira\CaseConverter\CaseConverterException
     */
    protected function handleGluerMethod(string $methodName): string
    {
        switch ($methodName) {
            case 'toAda':
                $className = AdaCase::class;
                break;
            case 'toCamel':
                $className = CamelCase::class;
                break;
            case 'toCobol':
                $className = CobolCase::class;
                break;
            case 'toKebab':
                $className = KebabCase::class;
                break;
            case 'toLower':
                $className = LowerCase::class;
                break;
            case 'toMacro':
                $className = MacroCase::class;
                break;
            case 'toPascal':
                $className = PascalCase::class;
                break;
            case 'toSentence':
                $className = SentenceCase::class;
                break;
            case 'toSnake':
                $className = SnakeCase::class;
                break;
            case 'toTitle':
                $className = TitleCase::class;
                break;
            case 'toTrain':
                $className = TrainCase::class;
                break;
            case 'toUpper':
                $className = UpperCase::class;
                break;
            default:
                throw new CaseConverterException("Unknown method: $methodName");
                break;
        }
        assert(is_subclass_of($className, Gluer::class));
        $namingConvention = new $className($this->words);

        /** @var \Jawira\CaseConverter\Glue\Gluer $namingConvention Subclass of Gluer (abstract) */
        return $namingConvention->glue();
    }

    /**
     * Detected words extracted from original string.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->words;
    }
}
