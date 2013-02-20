#!/usr/bin/php
<?php
/**
 * Simple input filter for using Doxygen with PHP code
 *
 * It escapes backslashes in docblock comments, and appends variable names to
 * \@var commands.
 *
 * @see http://www.doxygen.org/
 * @see http://www.stack.nl/~dimitri/doxygen/config.html#cfg_input_filter
 *
 * Copyright (C) 2012 Tamas Imrei <tamas.imrei@gmail.com>
 *                         http://virtualtee.blogspot.com/
 *
 * Modified by David Simon <david.mike.simon@gmail.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
*/

function process_php($source)
{
    // TODO This will have terrible results if I want to do inline
    // code examples that include "use" or "extends" or "implements"
    $source = str_replace("AC\\Kalinka\\", "", $source);

    $tokens = token_get_all($source);
    $buffer = null;
    foreach ($tokens as $token) {
        if (is_string($token)) {
            if ((! empty($buffer)) && ($token == ';')) {
                echo $buffer;
                unset($buffer);
            }
            echo $token;
        } else {
            list($id, $text) = $token;
            switch ($id) {
            case T_DOC_COMMENT :
                $text = preg_replace('/@ORM[^\n\r]+(\n\r?)/', '\\1', $text);
                $text = preg_replace(
                    '/([^\s.-]+)\\\\([^\s.-]+)/',
                    '[$1\\\\\\\\$2](@ref $1::$2)',
                    $text
                );
                if (preg_match('#@var\s+[^\$]*\*/#ms', $text)) {
                    $buffer = preg_replace('#(@var\s+[^\n\r]+)(\n\r?.*\*/)#ms',
                        '$1 \$\$\$$2', $text);
                } else {
                    echo $text;
                }
                break;

            case T_VARIABLE :
                if ((! empty($buffer))) {
                    echo str_replace('$$$', $text, $buffer);
                    unset($buffer);
                }
                echo $text;
                break;

            default:
                if ((! empty($buffer))) {
                    $buffer .= $text;
                } else {
                    echo $text;
                }
                break;
            }
        }
    }
}

function process_markdown($t)
{
    // Replace the original leader section with a link to GitHub
    $t = preg_replace("/^.+?##/s", "##", $t, 1);
    $t = "(This file is also available as `README.md` in the repository)\n\n$t";
    $t = "**Get downloads and source** at the [GitHub Project Page](http://github.com/americancouncils/kalinka).\n\n$t";

    // Convert from GitHub markdown to Doxygen markdown
    $t = "\mainpage\n$t";
    $t = str_replace("```", "~~~", $t);

    // TODO Link references from DavidMikeSimon\FiendishBundle\X to X


    echo($t);
}

$path = $_SERVER['argv'][1];
$source = file_get_contents($path);

if (preg_match("/\\.php$/", $path)) {
    process_php($source);
} else {
    process_markdown($source);
}
