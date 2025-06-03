# Overview
A simple language generator for finite languages specified with a limited regular expression language. 
Written in PHP. Written as a simple learning aid re the [Interpreter Pattern](https://en.wikipedia.org/wiki/Interpreter_pattern). 
See example below.

# Usage

    php generator.php "[Pp]([Aa][Ss]{1,2}){0,1}[Ww]([oO0][rR]){0,1}[dD]s{0,1}\d{0,1}"

# Language Definition:

    expression  -> ( ( literal | alternation | group | class | escape ) [ repeatition ] )*
    alternation -> "[" literal | class | escape | group "]"
    repeatition -> "{" alpha [ "," alpha ] "}"
    group       -> "(" expression ")"
    special     -> "{" "}" "[" "]" "(" ")" "\"
    escape      -> "\" special
