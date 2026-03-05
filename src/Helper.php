<?php
declare(strict_types=1);

namespace Mohamedsaleh077\Lno;

interface DatabaseInterface {
    public static function Fetch(string $sql, array $params = [], bool $all = false): array | bool;
    public static function beginTransaction(): bool;
    public static function commit(): bool;
    public static function rollback(): bool;
}

Trait QueryBuilderHelper{
    private bool $showWarnings = false;

    const ERROR_CODES = [
        "1000" => " Using * to be aliased is ILLEGAL BRO! you can not say AS thing, that is INSANE!",
        "1001" => " Using table.column.your_mom as a column address is ILLEGAL, use Table.Column.",
        "1002" => " How can you Alias a group of columns? you want to send table.* AS alias to your database? THAT IS INSANE!",
        "1003" => " Hmmm, YOU MUST FOLLOW THE SYNTAX! WHEN YOU OPEN A BOX YOU SHOULD ADD 3 COMPONENTS ONLY!",
        "1004" => " You can only use Values or Select with Insert!",
        "1005" => " Must Pick either select or values with insert",
        "1006" => " Must have where when you are using select with insert",
        "1007" => " Must include where when you use update",
        "1008" => " You want to DELETE without WHERE!!??",
        "1009" => " Error with DB, details: ",
        "1010" => " You must create at lease one query!",
        "1011" => " Queries count must be the same as params arraies, if there is not params, add empty array []"
    ];

    const WARNING_CODES = [
        "5000" => " When using {CODECODE} you are responsible for what is going to your DB, I won't touch it, 
        be careful! this warning is safe and no need to take actions!",
        "5001" => " We Found a RESERVED WORD and we will treat it as an SQL command!"
    ];

    public function enableWarnings(bool $enable) : void
    {
        $this->showWarnings = $enable;
    }

    private function errorHandler(int $errorCode, string $code): void
    {
        $code = "\nError here:\n" . $code . "\n";
        throw new \Exception($errorCode . self::ERROR_CODES[$errorCode] . $code, $errorCode);
    }

    private function warningHandler(int $warningCode, string $code): void
    {
        if ($this->showWarnings){
            $code = "\nWarning here:\n" . $code . "\n";
            trigger_error($warningCode . self::WARNING_CODES[$warningCode] . $code, E_USER_WARNING);   
        }
    }

    private function dotSplitter(string $string): string
    {
        $string = trim($string, " ");
        $result = "";
        $split = explode(".", $string);
        $length = count($split);
        switch ($length) {
            case 1:
                $result = $this->starProcess($string);
                break;
            case 2:
                $part1 = "`" . $split[0] . "`";
                $part2 = $this->starProcess($split[1]);
                $result = $part1 . "." . $part2;
                break;
        }
        return $result;
    }

    private function starProcess(string $string): string
    {
        if ($string === "*") {
            return $string;
        }
        return "`" . $string . "`";
    }
}