<?php
namespace AZBosakov\ParamString;

/**
 * @implements ParamStringInterface
 */
class Placeholders implements ParamStringInterface
{
    protected static $defaults = [
        'open' => '{',
        'close' => '}',
        'escape' => '!',
    ];
    
    protected $delims = [
        'open' => null,
        'close' => null,
        'escape' => null,
    ];
    
    protected $template = '';
    
    /**
     * @var array $params The placeholder replacement values (['param' => 'value', ...])
     */
    protected $params = [];
    /**
     * @var array $pieces The template string split at placeholder boundary
     */
    protected $pieces = [];
    /**
     * @var array $paramLink Map between the index in $pieces and the corresponding param name in $params
     */
    protected $paramLink = [];
    
    // Clear on 'clone'!
    protected $toString_cache = null;
    
    /**
     * Parse a template string.
     * 
     * If 'open', 'close' and/or 'escape' are NULL, use the corresponding default value.
     * None of the delimiters can be empty string.
     * 
     * @param  string $template   The template
     * @param ?string $open       The string to start a placeholder declaration
     * @param ?string $close      The string to end a placeholder declaration
     * @param ?string $escape     The string to escape a placeholder declaration
     */
    public function __construct(
         string $template,
        ?string $open = null,
        ?string $close = null,
        ?string $escape = null
    ) {
        $this->template = $template;
        $this->delims = static::validateDelimiters(
            $open ?? static::$defaults['open'],
            $close ?? static::$defaults['close'],
            $escape ?? static::$defaults['escape']
        );
        $this->parse();
    }
    
    /**
     * @return string The template string passed to the constructor
     */
    public function getTemplate() : string
    {
        return $this->template;
    }
    
    /**
     * Get the parsed template pieces
     * 
     * @return array The split template, with param names prefixed with '@'
     */
    public function getPieces(): array
    {
        $pieces = $this->pieces;
        foreach ($this->paramLink as $i => $p) {
            $pieces[$i] = "@ $p";
        }
        return $pieces;
    }
    
    /**
     * Get the placeholder delimiters.
     * 
     * @return array ['open'=>..., 'close'=>..., 'escape'=>...]
     */
    public function getDelimiters() : array
    {
        return $this->delims;
    }
    
    /**
     * Set the default 'open', 'close' and 'escape'. NULL leaves the corresponding delimiter unchanged.
     * 
     * @param ?string $open       The string to start a placeholder declaration
     * @param ?string $close      The string to end a placeholder declaration
     * @param ?string $escape     The string to escape a placeholder declaration
     */
    public static function setDefaultDelimiters(
        ?string $open = null,
        ?string $close = null,
        ?string $escape = null
    ) : void
    {
        static::$defaults = static::validateDelimiters(
            $open ?? static::$defaults['open'],
            $close ?? static::$defaults['close'],
            $escape ?? static::$defaults['escape']
        );
    }
    
    /**
     * Get the default placeholder delimiters.
     * 
     * @return array ['open'=>..., 'close'=>..., 'escape'=>...]
     */
    public static function getDefaultDelimiters() : array
    {
        return static::$defaults;
    }
    
    protected static function validateDelimiters(string $open, string $close, string $escape) : array
    {
        if (! (strlen($open) and strlen($close) and strlen($escape))) {
            throw new \InvalidArgumentException(
                "Empty string given for 'open', 'close' or 'escape', O:'{$open}' C:'{$close}' E:'{$escape}'"
            );
        }
        return ['open' => $open, 'close' => $close, 'escape' => $escape];
    }
    
    /**
     * Analyze the template string and split it into the $this->pieces
     */
    protected function parse() : void
    {
        $this->pieces = [];
        $d = '/';
        $o = preg_quote($this->delims['open'], $d);
        $c = preg_quote($this->delims['close'], $d);
        $e = preg_quote($this->delims['escape'], $d);
        $notParsed = $this->template;
        $m = [];
        $accNoPH = ''; // Accumulate non-placeholder parts in a sinle strings between the placeholders
        // Extract up to first possible param placeholder
        while (preg_match("$d^(.*?$o.*?(?<!$o)$c)(.*){$d}su", $notParsed, $m)) {
            $notParsed = $m[2];
            $part_esc_plhold = $m[1];
            // Extract the closest open/close pair
            preg_match("/^(.*)($o(.+)$c)$/su", $part_esc_plhold, $m);
            $part_esc = $m[1];
            $plhold = $m[2];
            $pName = $m[3];
            // Extract escapes if any.
            preg_match("/^(.*?)(($e)*)$/su", $part_esc, $m);
            $part = $m[1];
            $escRepLen = strlen($m[2]);
            $escCount = 0;
            if ($escRepLen) {
                $esc = $m[3];
                $escCount = $escRepLen / strlen($esc);
                $part .= str_repeat($esc, $escCount >> 1); // Double escapes to string of single ones
            }
            $accNoPH .= $part;
            if ($escCount & 1) {
                // Odd number of escapes - add placeholder literaly
                $accNoPH .= $plhold;
            } else {
                // Even number or no escapes - add as param
                if (strlen($accNoPH)) {
                    $this->pieces[] = $accNoPH;
                    $accNoPH = '';
                }
                $this->pieces[] = null;
                end($this->pieces);
                $this->paramLink[key($this->pieces)] = $pName;
                $this->params[$pName] = null;
            }
        }
        $accNoPH .= $notParsed;
        if (strlen($accNoPH)) {
            $this->pieces[] = $accNoPH;
        }
    }
    
    public function __toString() : string
    {
        // first time
        if (! isset($this->toString_cache)) {
            foreach ($this->paramLink as $i => $p) {
                $this->pieces[$i] = $this->params[$p];
            }
            $this->toString_cache = implode('', $this->pieces);
        }
        return $this->toString_cache;
    }
    
    /**
     * Clone the object and set the named param value
     * 
     * @param string $name  The param name
     * @param string $value The param value
     * 
     * @return self A clone of the object with the param value set
     */
    public function withParam(string $name, $value) : ParamStringInterface
    {
        $psh = clone $this;
        if (! array_key_exists($name, $this->params)) {
            trigger_error("Invalid param: $name");
            return $this;
        }
        $psh->params[$name] = (string)$value;
        $psh->toString_cache = null;
        return $psh;
    }
    
    /**
     * Get the value of a named param
     * 
     * @param string $name The param name
     * 
     * @return mixed $the param value
     */
    public function getParam(string $name)
    {
        return $this->params[$name] ?? null;
    }
    
    /**
     * Clone the object and set the corresponding parameters
     * 
     * @param array $params An array of ['paramName' => 'paramValue', ...]
     * 
     * @return self A clone of the object with the param value set
     */
    public function withParams(array $params) : ParamStringInterface
    {
        $psh = clone $this;
        $psh->toString_cache = null;
        foreach ($params as $p => $v) {
            if (array_key_exists($p, $psh->params)) {
                $psh->params[$p] = (string)$v;
            } else {
                trigger_error("Invalid param: $p");
            }
        }
        return $psh;
    }
    
    /**
     * Get the template parameters
     * 
     * @return array An array of ['paramName' => 'paramValue', ...]
     */
    public function getParams() : array
    {
        return $this->params;
    }
}
