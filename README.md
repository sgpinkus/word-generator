# Overview
A simple language generator for finite languages specified with a limited regular expression language. Written in PHP. Written as a simple exploration of the "Interpreter Pattern" as espoused by Design Patterns. See example below, and [Symbols.php](include/Symbols.php) for the syntax of legal expressions.

# Usage

    php generator.php "[Pp]([Aa][Ss]{1,2}){0,1}[Ww]([oO0][rR]){0,1}[dD]s{0,1}\d{0,1}"
