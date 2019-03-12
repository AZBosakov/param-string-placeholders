<?php
namespace AZBosakov\ParamString;

use PHPUnit\Framework\TestCase;

class PlaceholdersTest extends TestCase
{
    public function randomString(int $maxChars = 5): string
    {
        $asciiMin = 32;
        $asciiMax = 126;
        
        if ($maxChars < 1) {
            throw new \DomainException("\$maxChars must be at least 1, $maxChars given");
        }
        $chars = rand(1, $maxChars);
        $str = '';
        for ($i = 0; $i < $chars; $i++) {
            $str .= chr(rand($asciiMin, $asciiMax));
        }
        return $str;
    }
    
    public function testPlaceholderDelimiters()
    {
        $delimsSet = [
            ['{', '}', '!'],
            ['${', '}', '$'],
            ['%', '%', '%'],
            ['<', '>', '<'],
            ['{', '}', '{'],
            ['${', '}', '!'],
            ['open', 'close', 'escape'],
            ['x', 'y', 'z'],
            ['$', '$', '$'],
            ['-', '-', '\\'],
            ['{{', '}}', '\\']
        ];
        
        foreach ($delimsSet as $delims) {
            [$o, $c, $e] = $delims;
            $tpl = "aaa{$o}_P1_{$c}bbb{$e}{$e}{$e}{$o}_P2_{$c}{$e}{$e}{$e}{$e}{$o}_P3_{$c}";
            $exp = "aaa42bbb{$e}{$o}_P2_{$c}{$e}{$e}137";
            
            $ph = new Placeholders($tpl, $o, $c, $e);
            @$ph = $ph->withParams(['_P1_'=>42, '_P3_'=>137]);
            $this->assertEquals("$ph", $exp);
        }
    }
    
    
}
