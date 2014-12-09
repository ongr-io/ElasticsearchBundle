<?php
/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Service;

/**
 * Class JsonFormatter.
 */
class JsonFormatter
{
    /**
     * Pretty prints json.
     *
     * @param string $json
     *
     * @return string
     */
    public static function prettify($json)
    {
        $result = '';
        $pos = 0;
        $strLen = strlen($json);
        $indentStr = '    ';
        $newLine = "\n";
        $prevChar = '';
        $outOfQuotes = true;

        for ($i = 0; $i <= $strLen; $i++) {
            $char = substr($json, $i, 1);

            if ($char == '"' && $prevChar != '\\') {
                $outOfQuotes = !$outOfQuotes;
            } elseif (($char == '}' || $char == ']') && $outOfQuotes) {
                $result .= $newLine;
                $pos --;
                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }
            $result .= $char;

            if ($prevChar == '"' && $char == ':') {
                $result .= ' ';
            }

            if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
                $nextChar = (isset($json[$i + 1]) ? $json[$i + 1] : '');

                if ($nextChar != ']' && $nextChar != '}') {
                    $result .= $newLine;
                } else {
                    $result .= $nextChar;
                    $i++;
                    continue;
                }
                if ($char == '{' || $char == '[') {
                    $pos++;
                }
                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }
            $prevChar = $char;
        }

        return $result;
    }
}
