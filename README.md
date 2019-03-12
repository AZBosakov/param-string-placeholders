# Class description

A template string with a configurable placeholder delimiters. Can take forms like, eg.:

- 'aaa{PARAM}bbb'    O:'{', C:'}'
- 'aaa{{PARAM}}bbb'  O:'{{', C:'}}'
- 'aaa${PARAM}bbb'   O:'${', C:'}'
- 'aaa%PARAM%bbb'    O:'%', C:'%'
- 'aaa<PARAM>bbb'    O:'<', C:'>'


The placeholder structure is: E* O P C, where:
- O: Opening delimiter
- P: Parameter name
- C: Closing delimiter
- E: Escape string

None of the O,C,E can be empty ''.

The placeholders are passed as a constructor arguments: ($template, $open, $close, $escape). If any of $open, $close or $escape is **null**, the default value for the class is used (class-wide defaults can be set with ::setDefaultDelimiters($open, $close, $escape)).

The string is scanned for the closest non-empty pairs of OPEN/CLOSE.

OPEN directly followed by CLOSE is not treated like a placeholder, but taken verbatim.

The string between the O/C pair is the PARAM name. It can contain anything, except, of cource, OPEN or CLOSE strings.

If the placeholder is directly preceded with 1 or more ESCAPE strings, any two of them are converted to single one. If the E string count is odd, the placeholder is escaped and taken as part of the string in the form OPC, without the last ESC.

# Examples
## With O:'{', C:'}', E:'!'
- {}       : String '{}'
- {P}      : Parameter with name 'P'
- !{P}     : String '{P}'
- !!{P}    : Double escape, string '!' + param 'P'
- !!!!!{P} : Two double escapes and a leftover one, escaping the placeholder: strings '!!' + '{P}'

So the template

'aaa{}{{P1}bbb!!!{P2}ccc!!{P3}ddd'

is parsed as:

'aaa{}{', @ P1, 'bbb!{P2}ccc!', @ P3, 'ddd'

## With O:'%', C:'%', E:'%'
- '%%%%%P%%%' is 2 double escapes (%%+%%) -> string '%%' + param P + string '%%'

# Methods

## public function __construct(string $template, ?string $open = null, ?string $close = null, ?string $escape = null)
If any of $open, $close or $escape is **null**, the default value for the class is used.

## public function getPieces(): array
A debugging function - returns the pieces, to which the template string is split, with the placeholder pieces marked as '@ PHName'.

## public function getDelimiters() : array
Returns array ['open'=>..., 'close'=>..., 'escape'=>...]

## public static function getDefaultDelimiters() : array
Returns array - the default delimiters for the class: ['open'=>..., 'close'=>..., 'escape'=>...]

## public static function setDefaultDelimiters(?string $open = null, ?string $close = null, ?string $escape = null) : void
Set the default delimiters for the entire class. If any of them is **null**, use the hardcoded default.

## public function getTemplate() : string (ParamStringInterface)
Get the template string passed to the constructor

## public function withParam(string $name, $value) : self (ParamStringInterface)
Clone the object and set the named param value.

## public function getParam(string $name) (ParamStringInterface)
Get a parameter by name

## public function withParams(array $params) : self (ParamStringInterface)
Clone the object and set multiple params at once.

## public function getParams() : array (ParamStringInterface)
Get a snapshot of the parameters

## public function __toString() : string (ParamStringInterface)

# Complete example

```
$ph = new Placeholders(
    'The answer is <ans>, !<not a param>, !!<a param>, !!!<not a param>, !!!!<also param>',
    '<', '>', '!'
);

$ph = $ph->withParams(['ans'=>42, 'a param'=>'XXX', 'also param'=>'YYY']);

"$ph" == 'The answer is 42, <not a param>, !XXX, !<not a param>, !!YYY';
```

