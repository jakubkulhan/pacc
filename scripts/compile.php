#!/usr/bin/php
<?php
$libs = '';
foreach (glob(dirname(__FILE__) . '/../lib/*.php') as $lib) {
    $libs .= implode('', array_slice(file($lib), 1));
}

list($head, $tail) = preg_split(
    '~/\*\s*LIBS\s*\*/.*/\*\s*ENDLIBS\s*\*/~s', 
    file_get_contents(dirname(__FILE__) . '/../bin/pacc')
);

$output = shrink($head . $libs . $tail);

if (!isset($_SERVER['argv'][1]) || in_array('-h', $_SERVER['argv'])) {
    die($_SERVER['argv'][0] . " [ -h ] <output>\n");
}

if ($_SERVER['argv'][1] === '-') { $_SERVER['argv'][1] = 'php://stdout'; }

file_put_contents($_SERVER['argv'][1], $output);

/**
 * DGX's PHP shrinker
 * @copyright David Grudl
 * @author David Grudl
 */
function shrink($input)
{
    if (!defined('T_DOC_COMMENT')) { define ('T_DOC_COMMENT', -1); }
    if (!defined('T_ML_COMMENT')) { define ('T_ML_COMMENT', -1); }

    $space = $output = '';
    $set = '!"#$&\'()*+,-./:;<=>?@[\]^`{|}';
    $set = array_flip(preg_split('//',$set));

    foreach (token_get_all($input) as $token)  {
        if (!is_array($token)) { $token = array(0, $token); }
        switch ($token[0]) {
            case T_COMMENT:
            case T_ML_COMMENT:
            case T_DOC_COMMENT:
            case T_WHITESPACE:
                $space = ' ';
            break;

            default:
                if (isset($set[substr($output, -1)]) || isset($set[$token[1]{0}])) { $space = ''; }
                $output .= $space . $token[1] . ($token[0] === T_END_HEREDOC ? PHP_EOL : '');
                $space = '';
        }
    }
    return $output;
}
