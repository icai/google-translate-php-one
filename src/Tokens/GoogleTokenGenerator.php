<?php

namespace Stichoza\GoogleTranslate\Tokens;

/**
 * Google Token Generator.
 *
 * Thanks to @helen5106 and @tehmaestro and few other cool guys
 * at https://github.com/Stichoza/google-translate-php/issues/32
 */
function now() {
    return floor(microtime(true) * 1000);
};

class GoogleTokenGenerator implements TokenProviderInterface
{

    /**
     * @var array
     */
    protected $win = [ "TKK" => "0" ];
    /**
     * Generate and return a token.
     *
     * @param string $source Source language
     * @param string $target Target language
     * @param string $text Text to translate
     * @return string Token
     */
    public function generateToken(string $source, string $target, string $text) : string
    {
        return $this->TL($text);
    }

    /**
     * Generate a valid Google Translate request token.
     *
     * @param string $a text to translate
     *
     * @return string
     */
    private function TL($a)
    {
        $tkk = $this->updateTTK();
        $b = $tkk[0] ? $tkk[0] + 0 : 0;
        for ($d = [], $e = 0, $f = 0; $f < $this->JS_length($a); $f++) {
            $g = $this->JS_charCodeAt($a, $f);
            if (128 > $g) {
                $d[$e++] = $g;
            } else {
                if (2048 > $g) {
                    $d[$e++] = $g >> 6 | 192;
                } else {
                    if (55296 == ($g & 64512) && $f + 1 < $this->JS_length($a) && 56320 == ($this->JS_charCodeAt($a, $f + 1) & 64512)) {
                        $g = 65536 + (($g & 1023) << 10) + ($this->JS_charCodeAt($a, ++$f) & 1023);
                        $d[$e++] = $g >> 18 | 240;
                        $d[$e++] = $g >> 12 & 63 | 128;
                    } else {
                        $d[$e++] = $g >> 12 | 224;
                    }
                    $d[$e++] = $g >> 6 & 63 | 128;
                }
                $d[$e++] = $g & 63 | 128;
            }
        }
        $a = $b;
        for ($e = 0; $e < count($d); $e++) {
            $a += $d[$e];
            $a = $this->RL($a, '+-a^+6');
        }
        $a = $this->RL($a, '+-3^+b+-f');
        $a ^= $tkk[1] ? $tkk[1] + 0 : 0;
        if (0 > $a) {
            $a = ($a & 2147483647) + 2147483648;
        }
        $a = fmod($a, pow(10, 6));

        return $a.'.'.($a ^ $b);
    }

    private function updateTTK($opts = ["tld" => 'cn']) {

        $now = (float)floor(now() / 3600000);
        $cnow = explode(".", $this->win["TKK"])[0];

        if ((float)$cnow !== $now) {
            $url =  'https://translate.google.'. $opts["tld"];

            $ch = curl_init();
            // Set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_URL, $url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.70 Safari/537.36");
            // Execute post
            $result = curl_exec($ch);

            preg_match("/tkk:\s?'(.+?)'/i", $result, $matches);
            if ($matches) {
                $this->win["TKK"] = $matches[1];
            }
             // Close connection
             curl_close($ch);
             return explode(".", $this->win["TKK"]);
        } else {
            return explode(".", $this->win["TKK"]);
        }

    }

    /**
     * @return array
     */
    private function TKK()
    {
        return [now(), (561666268 + 1526272306)];
    }

    /**
     * Process token data by applying multiple operations.
     * (Params are safe, no need for multibyte functions)
     *
     * @param int $a
     * @param string $b
     *
     * @return int
     */
    private function RL($a, $b)
    {
        for ($c = 0; $c < strlen($b) - 2; $c += 3) {
            $d = $b[$c + 2];
            $d = 'a' <= $d ? ord($d[0]) - 87 : intval($d);
            $d = '+' == $b[$c + 1] ? $this->unsignedRightShift($a, $d) : $a << $d;
            $a = '+' == $b[$c] ? ($a + $d & 4294967295) : $a ^ $d;
        }

        return $a;
    }

    /**
     * Unsigned right shift implementation
     * https://msdn.microsoft.com/en-us/library/342xfs5s(v=vs.94).aspx
     * http://stackoverflow.com/a/43359819/2953830
     *
     * @param $a
     * @param $b
     *
     * @return number
     */
    private function unsignedRightShift($a, $b)
    {
        if ($b >= 32 || $b < -32) {
            $m = (int)($b / 32);
            $b = $b - ($m * 32);
        }

        if ($b < 0) {
            $b = 32 + $b;
        }

        if ($b == 0) {
            return (($a >> 1) & 0x7fffffff) * 2 + (($a >> $b) & 1);
        }

        if ($a < 0) {
            $a = ($a >> 1);
            $a &= 2147483647;
            $a |= 0x40000000;
            $a = ($a >> ($b - 1));
        } else {
            $a = ($a >> $b);
        }

        return $a;
    }

    /**
     * Get JS charCodeAt equivalent result with UTF-16 encoding
     *
     * @param string $str
     * @param int    $index
     *
     * @return number
     */
    private function JS_charCodeAt($str, $index) {
        $utf16 = mb_convert_encoding($str, 'UTF-16LE', 'UTF-8');
        return ord($utf16[$index*2]) + (ord($utf16[$index*2+1]) << 8);
    }

    /**
     * Get JS equivalent string length with UTF-16 encoding
     *
     * @param string $str
     *
     * @return number
     */
    private function JS_length($str) {
        $utf16 = mb_convert_encoding($str, 'UTF-16LE', 'UTF-8');
        return strlen($utf16)/2;
    }
}
