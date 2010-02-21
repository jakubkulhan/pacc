# pacc – PHP yACC

Parser generator (currently generates recursive descent parser and canonical LR(1) parser) for PHP.

## Get ready

There is executable `bin/pacc`. However it is dependant on its location in filesystem (because of libraries in `lib/` directory), and therefore there is script `scripts/compile.php`, which compiles all needed libraries and executable into one file. Example usage of script:

    $ ./scripts/compile.php pacc
    $ chmod +x pacc
    # mv pacc /usr/bin

## Write parsers

Files consumed by `pacc` are structured like this:

    grammar <<parser_name>>;

    option <<option_name>> = <<option_value>>;

    @<<code>> {
        <<php code>>
    }

    <<rules>>

Rules are compiled into PHP parser code, header and footer are left as they are.

`pacc` uses YACC/Bison syntax for rules. Each rule constist of its name, `:`, body, and `;`. Name has to match regular expression `[a-z][a-z_]*`. Body consists of expressions separated by vertical bar – `|`. Each expression can have some attached PHP code. For example:

    numerical_operation
        : number '+' number { $$ = $1 + $3; /* $1 is first number, $2 is plus sign, and $3 is second number */ }
        | number '-' number { $$ = $1 - $3; }
        ;

In PHP code, you can use special variables like `$$`, `$1`, `$2`, `$3`,  etc. In `$$` is saved result of expression. Through numerical variables you get result of subexpressions.

Look for inspiration in `examples/` directory.

## License

The MIT license

    Copyright (c) 2009-2010 Jakub Kulhan <jakub.kulhan@gmail.com>

    Permission is hereby granted, free of charge, to any person
    obtaining a copy of this software and associated documentation
    files (the "Software"), to deal in the Software without
    restriction, including without limitation the rights to use,
    copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the
    Software is furnished to do so, subject to the following
    conditions:

    The above copyright notice and this permission notice shall be
    included in all copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
    EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
    OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
    NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
    HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
    WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
    FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
    OTHER DEALINGS IN THE SOFTWARE.
